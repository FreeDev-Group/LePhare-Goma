<?php
/**
 * Core helper functions.
 *
 * @since x.x.x
 * @package Magazine Blocks
 */

/**
 * Get the direct filesystem object.
 *
 * @return \WP_Filesystem_Direct|null
 */
function magazine_blocks_get_filesystem() {
	/**
	 * WP_FIlesystem_Direct instance.
	 *
	 *  @var \WP_Filesystem_Direct $wp_filesystem WP_FIlesystem_Direct instance.
	 */
	global $wp_filesystem;

	if ( ! $wp_filesystem || 'direct' !== $wp_filesystem->method ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		$credentials = request_filesystem_credentials( '', 'direct' );
		WP_Filesystem( $credentials );
	}

	return $wp_filesystem;
}

/**
 * Append content to file.
 *
 * If file does not exists, it will create new file.
 *
 * @param string           $filepath Filepath to append.
 * @param Closure|callable $append_callback Appender.
 *
 * @return bool True on success and false on failure.
 */
function magazine_blocks_filesystem_append_to_file( $filepath, $append_callback ) {
	$filesystem = magazine_blocks_get_filesystem();

	if ( ! $filesystem ) {
		return false;
	}

	if ( ! $filesystem->exists( $filepath ) ) {
		$filesystem->touch( $filepath, FS_CHMOD_FILE );
	}

	$current     = $filesystem->get_contents( $filepath );
	$new_content = call_user_func_array( $append_callback, array( $current, $filesystem, $filepath ) );

	if ( ! isset( $new_content ) || ! is_string( $new_content ) ) {
		_doing_it_wrong( __FUNCTION__, 'Invalid new content.', '1.0.0' );
		return false;
	}

	return $filesystem->put_contents( $filepath, $new_content );
}

/**
 * Is magazine blocks block.
 *
 * @since x.x.x
 * @param array $block Block data.
 * @return bool
 */
function magazine_blocks_is_magazine_blocks_block( array $block ): bool {
	if ( empty( $block ) || empty( $block['blockName'] ) ) {
		return false;
	}

	$namespace = $block['blockName'];

	if ( 0 === strpos( $namespace, 'magazine-blocks/' ) ) {
		return true;
	}

	return false;
}

/**
 * Get WP_Query args to order posts by view count, treating unviewed posts as zero.
 *
 * Uses a query-var marker consumed by `magazine_blocks_join_popular_order_meta()`
 * and `magazine_blocks_orderby_popular_order_meta()` rather than `meta_key`/`meta_query`,
 * since an `EXISTS` meta clause builds an INNER JOIN that would silently exclude every
 * post without a recorded view before a `NOT EXISTS` OR clause ever gets a chance to
 * include it.
 *
 * @since x.x.x
 * @return array
 */
function magazine_blocks_get_popular_order_args(): array {
	return array(
		'mzb_order_by_popular' => true,
	);
}

/**
 * LEFT JOIN the view-count meta table when ordering by popularity.
 *
 * @since x.x.x
 * @param string    $join  SQL JOIN clause.
 * @param \WP_Query $query The query.
 * @return string
 */
function magazine_blocks_join_popular_order_meta( $join, $query ) {
	global $wpdb;

	if ( ! $query->get( 'mzb_order_by_popular' ) ) {
		return $join;
	}

	$join .= " LEFT JOIN {$wpdb->postmeta} AS mzb_popular_meta ON ( {$wpdb->posts}.ID = mzb_popular_meta.post_id AND mzb_popular_meta.meta_key = '_mzb_post_view_count' )"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	return $join;
}

/**
 * Order by view count, treating posts with no recorded views as zero.
 *
 * @since x.x.x
 * @param string    $orderby SQL ORDER BY clause.
 * @param \WP_Query $query   The query.
 * @return string
 */
function magazine_blocks_orderby_popular_order_meta( $orderby, $query ) {
	if ( ! $query->get( 'mzb_order_by_popular' ) ) {
		return $orderby;
	}

	$order = 'ASC' === strtoupper( (string) $query->get( 'order' ) ) ? 'ASC' : 'DESC';

	return "CAST( COALESCE( mzb_popular_meta.meta_value, 0 ) AS UNSIGNED ) {$order}";
}

/**
 * Get public post types.
 *
 * @return array
 */
function magazine_blocks_get_post_types(): array {
	return array_filter(
		get_post_types(
			array(
				'public'       => true,
				'show_in_rest' => true,
			),
			'objects'
		),
		function ( $post_type ) {
			return ! in_array( $post_type->name, array( 'revision', 'nav_menu_item', 'attachment' ), true );
		}
	);
}

/**
 * Get icon.
 *
 * @param string $name Icon name.
 * @param bool   $echo Echo or return.
 * @param array  $args Icon args.
 *
 * @return string
 */
function magazine_blocks_get_icon( $name, $echo = false, $args = array() ) {

	if ( ! is_string( $name ) ) {
		$name = (string) $name;
	}
	$icon = \MagazineBlocks\Icon::init()->get( $name, $args );
	if ( $echo ) {
		echo wp_kses( $icon, magazine_blocks_get_allowed_svg_elements() );
	}
	return $icon;
}

/**
 * Get the ID.
 *
 * @return false|int|string
 */
function magazine_blocks_get_the_id() {
	$id = false;

	if ( ! magazine_blocks_is_block_theme() ) {
		if ( is_singular() ) {
			$id = get_the_ID();
		}
		return $id;
	}

	if ( is_front_page() && is_home() ) {
		$id = 'home';
	} elseif ( is_front_page() && ! is_home() ) {
		$id = 'front_page';
	} elseif ( is_home() && ! is_front_page() ) {
		$id = 'blog';
	} elseif ( is_archive() ) {
		if ( is_category() ) {
			$id = 'category';
		} elseif ( is_tag() ) {
			$id = 'tag';
		} elseif ( is_author() ) {
			$id = 'author';
		} elseif ( is_date() ) {
			$id = 'date';
		} elseif ( is_post_type_archive() ) {
			$id = 'post_type_archive';
		} elseif ( is_tax() ) {
			$id = 'taxonomy';
		}
	} elseif ( is_search() ) {
		$id = 'search';
	} elseif ( is_404() ) {
		$id = '404';
	} elseif ( is_singular() ) {
		$id = get_the_ID();
	}

	return $id;
}

/**
 * Is preview.
 *
 * @return bool
 */
function magazine_blocks_is_preview(): bool {
	return isset( $_GET['preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Is block theme.
 *
 * @return bool
 */
function magazine_blocks_is_block_theme(): bool {
	return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
}

/**
 * Process blocks.
 *
 * Get only magazine blocks, flatten inner blocks and process reusable blocks.
 *
 * @param array $blocks Array of blocks.
 *
 * @return array
 */
function magazine_blocks_process_blocks( &$blocks ): array {
	$processed_blocks = array();
	$refs             = array();

	foreach ( $blocks as &$block ) {
		if ( magazine_blocks_is_magazine_blocks_block( $block ) ) {
			$processed_blocks[] = &$block;
		}
		if ( 'core/block' === $block['blockName'] ) {
			$ref = magazine_blocks_array_get( $block, 'attrs.ref' );
			if ( $ref && ! isset( $refs[ $ref ] ) ) {
				$refs[ $ref ]  = true;
				$reusable_post = get_post( $ref );

				if ( $reusable_post && $reusable_post instanceof WP_Post ) {
					$ref_blocks       = parse_blocks( $reusable_post->post_content );
					$ref_blocks       = magazine_blocks_process_blocks( $ref_blocks );
					$processed_blocks = array_merge( $processed_blocks, $ref_blocks );
				}
			}
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$inner_blocks     = magazine_blocks_process_blocks( $block['innerBlocks'] );
			$processed_blocks = array_merge( $processed_blocks, $inner_blocks );

			if ( 'magazine-blocks/button' !== $block['blockName'] ) {
				magazine_blocks_array_forget( $block, 'innerBlocks' );
			}
		}
		magazine_blocks_array_forget( $block, array( 'innerHTML', 'innerContent' ) );
	}

	return $processed_blocks;
}


/**
 * Generate block styles.
 *
 * @param array           $blocks Block data.
 * @param null|int|string $id Current page or template id.
 * @param bool            $force_generate Force generate styles.
 *
 * @return \MagazineBlocks\BlockStyles
 */
function magazine_blocks_generate_blocks_styles( array &$blocks, $id = null, bool $force_generate = false ) {
	return new \MagazineBlocks\BlockStyles( $blocks, $id, $force_generate );
}


/**
 * The function retrieves widget blocks and parses them into an array.
 *
 * @return array Array of blocks.
 */
function magazine_blocks_get_widget_blocks() {
	$callback = function ( $acc, $curr ) {
		if ( ! empty( $curr['content'] ) ) {
			$acc .= $curr['content'];
		}
		return $acc;
	};
	return parse_blocks(
		array_reduce(
			get_option( 'widget_block', array() ),
			$callback,
			''
		)
	);
}

/**
 * Get allowed svg elements.
 *
 * @return array
 */
function magazine_blocks_get_allowed_svg_elements() {
	return array(
		'svg'     => array(
			'class'           => true,
			'xmlns'           => true,
			'width'           => true,
			'height'          => true,
			'viewbox'         => true,
			'aria-hidden'     => true,
			'role'            => true,
			'focusable'       => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
		),
		'g'       => array( 'fill' => true ),
		'title'   => array( 'title' => true ),
		'path'    => array(
			'fill'      => true,
			'fill-rule' => true,
			'd'         => true,
			'transform' => true,
		),
		'circle'  => array(
			'cx' => true,
			'cy' => true,
			'r'  => true,
		),
		'polygon' => array(
			'fill'      => true,
			'fill-rule' => true,
			'points'    => true,
			'transform' => true,
			'focusable' => true,
		),
		'line'    => array(
			'x1' => true,
			'y1' => true,
			'x2' => true,
			'y2' => true,
		),
	);
}

/**
 * String to kebab case.
 *
 * @param string $str The string.
 * @return string
 */
function magazine_blocks_string_to_kebab( $str ) {
	$str = str_replace( ' ', '-', strtolower( $str ) );
	$str = preg_replace( '/[^a-z0-9-]/', '', $str );
	$str = preg_replace( '/-+/', '-', $str );
	$str = trim( $str, '-' );

	return $str;
}

/**
 * Build html attributes from array.
 *
 * @param array   $attributes The attributes list.
 * @param boolean $echo_attributes The echo the html or not.
 */
function magazine_blocks_build_html_attrs( $attributes = array(), $echo_attributes = false ) {
	$index  = 0;
	$attrs  = '';
	$length = count( $attributes );

	foreach ( $attributes as $key => $value ) {
		if ( isset( $value ) ) {
			if ( is_array( $value ) ) {
				$value = implode( ' ', array_filter( $value ) );
			}
			$value = strval( $value );
			if ( '' === $value ) {
				continue;
			}
			$esc_func = 'src' === $key ? 'esc_url' : 'esc_attr';

			if ( $echo_attributes ) {
				echo ' ' . esc_attr( $key ) . '="' . call_user_func_array( $esc_func, [ $value ] ) . '"' . ( $length === $index + 1 ? ' ' : '' ); // phpcs:ignore -- see: L:348
			} else {
				$attrs .= ' ' . esc_attr( $key ) . '="' . call_user_func_array( $esc_func, array( $value ) ) . '"' . ( $length === $index + 1 ? ' ' : '' );
			}
		}
		++$index;
	}

	return $attrs;
}

/**
 * Is rest request.
 *
 * @return boolean
 */
function magazine_blocks_is_rest_request() {
	return defined( 'REST_REQUEST' ) && REST_REQUEST;
}

/**
 * Is rest request.
 *
 * @return boolean
 */
function magazine_blocks_is_development() {
	return defined( 'MAGAZINE_BLOCKS_DEVELOPMENT' ) && MAGAZINE_BLOCKS_DEVELOPMENT;
}


/**
 * Get setting object.
 *
 * @param string $key The key.
 * @param mixed  $default_value The default value.
 */
function magazine_blocks_get_setting( $key = '', $default_value = null ) {
	if ( ! $key ) {
		return \MagazineBlocks\Setting::all();
	}
	return \MagazineBlocks\Setting::get( $key, $default_value );
}

/**
 * Strung to bool.
 *
 * @param string $str The string.
 * @return bool
 */
function magazine_blocks_string_to_bool( $str ) {
	if ( is_bool( $str ) ) {
		return $str;
	}

	$str = strtolower( $str );

	return ( 'yes' === $str || 1 === $str || 'true' === $str || '1' === $str );
}

/**
 * Bool to string.
 *
 * @param bool $value The bool value.
 * @return string
 */
function magazine_blocks_bool_to_string( $value ) {
	if ( ! is_bool( $value ) ) {
		$value = magazine_blocks_string_to_bool( $value );
	}
	return true === $value ? 'yes' : 'no';
}

/**
 * Get webfont url.
 *
 * @param string  $url Remote webfont url.
 * @param string  $format Font format.
 * @param boolean $preload Preload font.
 */
function magazine_blocks_get_webfont_url( $url, $format = 'woff2', $preload = false ) {
	$font = new \MagazineBlocks\WebFontLoader( $url, $preload );
	$font->set_font_format( $format );
	return $font->get_url();
}

/**
 * Get global styles.
 *
 * @return array
 */
function magazine_blocks_get_global_styles() {
	$global_styles = magazine_blocks_get_setting( 'global-styles' );

	try {
		$global_styles = json_decode( $global_styles, true );
	} catch ( Exception $e ) {
		$global_styles = array();
	}

	return $global_styles;
}

/**
 * Generate global styles.
 *
 * @return \BlockArt\GlobalStyles
 */
function magazine_blocks_generate_global_styles() {
	return new \MagazineBlocks\GlobalStyles();
}


/**
 * Get allowed html tags.
 *
 * @param mixed $tag The tag.
 * @param mixed $replace The replace.
 * @param mixed $allowed_tags The allow tags list.
 * @return string
 */
function magazine_blocks_sanitize_html_tag( $tag, $replace = null, $allowed_tags = array() ) {
	$allowed_tags = empty( $allowed_tags ) ? array(
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
	) : $allowed_tags;
	return in_array( $tag, $allowed_tags, true ) ? $tag : ( $replace ?? '' );
}

/**
 * Calculate the read time.
 *
 * @param [type] $post_id The post id.
 */
function magazine_calculate_read_time( $post_id ) {
	$words_per_minute = 200;
	$content          = get_post_field( 'post_content', $post_id );
	$word_count       = str_word_count( wp_strip_all_tags( $content ) );
	$read_time        = ceil( $word_count / $words_per_minute );
	return $read_time;
}

/**
 * Convert layout name to style attribute key (e.g., 'layout-1' -> 'layout1AdvancedStyle')
 *
 * @param mixed $layout The layout type.
 */
function magazine_get_style_key( $layout ) {
	return str_replace( '-', '', $layout ) . 'AdvancedStyle';
}

/**
 * Convert heading layout name to style attribute key (e.g., 'heading-layout-1' -> 'headingLayout1AdvancedStyle')
 *
 * @param mixed $heading_layout The heading layout style.
 */
function magazine_get_heading_style_key( $heading_layout ) {
	$base = str_replace( 'heading-', '', $heading_layout );
	$base = str_replace( '-', '', $base );

	return 'heading' . ucfirst( $base ) . 'AdvancedStyle';
}

/**
 * Custom pagination function to generate numbered pagination links.
 *
 * @param int $max_num_pages The total number of pages.
 * @param int $current_page  The current page number.
 * @param int $client_id The client id.
 * @param int $range The range.
 * @return string The HTML for numbered pagination links.
 */
function mzb_numbered_pagination( $max_num_pages, $current_page, $client_id, $range = 2 ) {
	$pagination = '';

	if ( $max_num_pages > 1 ) {
		$pagination .= '<ul class="mzb-pagination-numbers">';

		// Previous Arrow.
		if ( $current_page > 1 ) {
			$prev_page   = $current_page - 1;
			$pagination .= '<li class="page-item prev"><a class="page-link" href="' . esc_url( add_query_arg( 'block_id_' . $client_id, $prev_page ) ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="17" viewBox="0 0 16 17" fill="none">
			<path d="M14.0002 7.9622H3.60683L8.4735 3.1022C8.53566 3.04004 8.58496 2.96625 8.6186 2.88503C8.65224 2.80382 8.66956 2.71677 8.66956 2.62887C8.66956 2.54096 8.65224 2.45392 8.6186 2.3727C8.58496 2.29149 8.53566 2.21769 8.4735 2.15553C8.41134 2.09337 8.33754 2.04407 8.25633 2.01043C8.17511 1.97679 8.08807 1.95947 8.00016 1.95947C7.82263 1.95947 7.65237 2.03 7.52683 2.15553L1.52683 8.15553C1.46509 8.21811 1.41737 8.2931 1.38683 8.37553C1.35267 8.45568 1.33455 8.54175 1.3335 8.62887C1.33516 8.71807 1.35324 8.80621 1.38683 8.88887C1.42086 8.96552 1.46827 9.0355 1.52683 9.09553L7.52683 15.0955C7.5888 15.158 7.66254 15.2076 7.74378 15.2415C7.82502 15.2753 7.91216 15.2927 8.00016 15.2927C8.08817 15.2927 8.17531 15.2753 8.25655 15.2415C8.33779 15.2076 8.41152 15.158 8.4735 15.0955C8.53598 15.0336 8.58558 14.9598 8.61942 14.8786C8.65327 14.7973 8.6707 14.7102 8.6707 14.6222C8.6707 14.5342 8.65327 14.4471 8.61942 14.3658C8.58558 14.2846 8.53598 14.2108 8.4735 14.1489L3.60683 9.29553H14.0002C14.177 9.29553 14.3465 9.2253 14.4716 9.10027C14.5966 8.97525 14.6668 8.80568 14.6668 8.62887C14.6668 8.45206 14.5966 8.28249 14.4716 8.15746C14.3465 8.03244 14.177 7.9622 14.0002 7.9622Z" fill="#3F3F46"/>
		  </svg></a></li>';
		}

		// Always display the first page.
		$pagination .= '<li class="page-item' . ( 1 === $current_page ? ' current' : '' ) . '"><a class="page-link" href="' . esc_url( add_query_arg( 'block_id_' . $client_id, 1 ) ) . '">1</a></li>';

		// Start ellipsis.
		if ( $current_page > ( $range + 2 ) ) {
			$pagination .= '<li class="page-item ellipsis">...</li>';
		}

		for ( $i = max( 2, $current_page - $range ); $i <= min( $max_num_pages - 1, $current_page + $range ); $i++ ) {
			$class       = ( $i === $current_page ) ? ' current' : '';
			$pagination .= '<li class="page-item' . $class . '"><a class="page-link" href="' . esc_url( add_query_arg( 'block_id_' . $client_id, $i ) ) . '">' . $i . '</a></li>';
		}

		// End ellipsis.
		if ( $current_page < ( $max_num_pages - $range - 1 ) ) {
			$pagination .= '<li class="page-item ellipsis">...</li>';
		}

		// Always display the last page.
		$pagination .= '<li class="page-item' . ( $current_page === $max_num_pages ? ' current' : '' ) . '"><a class="page-link" href="' . esc_url( add_query_arg( 'block_id_' . $client_id, $max_num_pages ) ) . '">' . $max_num_pages . '</a></li>';

		// Next Arrow.
		if ( $current_page < $max_num_pages ) {
			$next_page   = $current_page + 1;
			$pagination .= '<li class="page-item next"><a class="page-link" href="' . esc_url( add_query_arg( 'block_id_' . $client_id, $next_page ) ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="17" viewBox="0 0 16 17" fill="none">
			<path d="M2.00016 7.9622H12.3935L7.52683 3.1022C7.40129 2.97666 7.33077 2.8064 7.33077 2.62887C7.33077 2.54096 7.34808 2.45392 7.38172 2.3727C7.41536 2.29149 7.46467 2.21769 7.52683 2.15553C7.58899 2.09337 7.66278 2.04407 7.744 2.01043C7.82521 1.97679 7.91226 1.95947 8.00016 1.95947C8.1777 1.95947 8.34796 2.03 8.4735 2.15553L14.4735 8.15553C14.5352 8.21811 14.583 8.2931 14.6135 8.37553C14.6477 8.45568 14.6658 8.54175 14.6668 8.62887C14.6652 8.71807 14.6471 8.80621 14.6135 8.88887C14.5795 8.96552 14.5321 9.0355 14.4735 9.09553L8.4735 15.0955C8.41152 15.158 8.33779 15.2076 8.25655 15.2415C8.17531 15.2753 8.08817 15.2927 8.00016 15.2927C7.91216 15.2927 7.82502 15.2753 7.74378 15.2415C7.66254 15.2076 7.5888 15.158 7.52683 15.0955C7.46434 15.0336 7.41475 14.9598 7.3809 14.8786C7.34706 14.7973 7.32963 14.7102 7.32963 14.6222C7.32963 14.5342 7.34706 14.4471 7.3809 14.3658C7.41475 14.2846 7.46434 14.2108 7.52683 14.1489L12.3935 9.29553H2.00016C1.82335 9.29553 1.65378 9.2253 1.52876 9.10027C1.40373 8.97525 1.3335 8.80568 1.3335 8.62887C1.3335 8.45206 1.40373 8.28249 1.52876 8.15746C1.65378 8.03244 1.82335 7.9622 2.00016 7.9622Z" fill="#3F3F46"/>
		  </svg></a></li>';
		}

		$pagination .= '</ul>';
	}

	return $pagination;
}
