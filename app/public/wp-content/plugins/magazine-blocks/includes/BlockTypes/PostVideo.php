<?php
/**
 * Post Video block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\PostRenderer;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Post Video block class.
 */
class PostVideo extends Block {

	use PostRenderer;

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'post-video';
	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		if ( magazine_blocks_is_rest_request() ) {
			return $content;
		}

		$attrs = $this->extract_attributes( $attributes );
		$query = $this->build_query( $attrs );

		if ( $query->have_posts() ) {
			return $this->render_block( $query, $attrs );
		}

		return '';
	}

	/**
	 * Extract and process block attributes.
	 *
	 * @param array $attributes Raw block attributes.
	 * @return array Processed attributes.
	 */
	protected function extract_attributes( $attributes ) {
		$client_id = magazine_blocks_array_get( $attributes, 'clientId', '' );

		return array(
			// General attributes.
			'client_id'                      => $client_id,
			'presets'                        => magazine_blocks_array_get( $attributes, 'presets', '' ),
			'column'                         => magazine_blocks_array_get( $attributes, 'column', '' ),

			// Query parameters.
			'category'                       => magazine_blocks_array_get( $attributes, 'category', '' ),
			'tag'                            => magazine_blocks_array_get( $attributes, 'tag', '' ),
			'post_count'                     => magazine_blocks_array_get( $attributes, 'postCount', '' ),
			'post_type'                      => magazine_blocks_array_get( $attributes, 'postType', 'post' ),
			'offset'                         => magazine_blocks_array_get( $attributes, 'offset', 0 ),

			// Layout settings.
			'layout4_top_row_count'          => magazine_blocks_array_get( $attributes, 'layout4TopRowCount', 2 ),
			'layout4_bottom_row_count'       => magazine_blocks_array_get( $attributes, 'layout4BottomRowCount', 3 ),

			// Content settings.
			'enable_title'                   => magazine_blocks_array_get( $attributes, 'enableTitle', false ),
			'post_title_markup'              => magazine_blocks_array_get( $attributes, 'postTitleMarkup', 'h3' ),
			'enable_date'                    => magazine_blocks_array_get( $attributes, 'enableDate', false ),
			'enable_excerpt'                 => magazine_blocks_array_get( $attributes, 'enableExcerpt', false ),
			'excerpt_limit'                  => magazine_blocks_array_get( $attributes, 'excerptLimit', 20 ),
			'enable_readmore'                => magazine_blocks_array_get( $attributes, 'enableReadMore', false ),
			'read_more_text'                 => magazine_blocks_array_get( $attributes, 'readMoreText', 'Read More' ),
			'enable_category'                => magazine_blocks_array_get( $attributes, 'enableCategory', false ),
			'enable_highlight_post_category' => magazine_blocks_array_get( $attributes, 'enableCategoryForHighlightPost', false ),
			'enable_author'                  => magazine_blocks_array_get( $attributes, 'enableAuthor', false ),

			// Meta settings.
			'meta_position'                  => magazine_blocks_array_get( $attributes, 'metaPosition', '' ),
			'enable_icon'                    => magazine_blocks_array_get( $attributes, 'enableIcon', '' ),
			'enable_meta_separator'          => magazine_blocks_array_get( $attributes, 'enableMetaSeparator', '' ),
			'meta_separator'                 => magazine_blocks_array_get( $attributes, 'separatorType', 'none' ),

			// Theme override.
			'enable_override_category_color' => get_theme_mod( 'colormag_enable_override_category_color', false ),
		);
	}

	/**
	 * Build WP_Query based on attributes.
	 *
	 * @param array $attrs Processed attributes.
	 * @return WP_Query
	 */
	protected function build_query( $attrs ) {
		$args = array(
			'post_type'           => $attrs['post_type'],
			'posts_per_page'      => $attrs['post_count'],
			'post_status'         => 'publish',
			'cat'                 => $attrs['category'],
			'tag_id'              => $attrs['tag'],
			'ignore_sticky_posts' => 1,
			'tax_query'           => array(
				array(
					'taxonomy' => 'post_format',
					'field'    => 'slug',
					'terms'    => array( 'post-format-video' ),
				),
			),
			'offset'              => $attrs['offset'],
		);

		// Handle template context queries.
		$type = get_query_var( 'mzb_template_type' );
		if ( in_array( $type, array( 'archive', 'search', 'single', 'front' ), true ) ) {
			$args['post_type'] = 'post';
			unset( $args['cat'], $args['tag_id'], $args['orderby'], $args['order'], $args['author'], $args['category__not_in'], $args['ignore_sticky_posts'], $args['paged'], $args['offset'] );

			switch ( $type ) {
				case 'archive':
					if ( is_archive() ) {
						if ( is_category() ) {
							$args['category_name'] = get_query_var( 'category_name' );
						} elseif ( is_tag() ) {
							$args['tag'] = get_query_var( 'tag' );
						} elseif ( is_author() ) {
							$args['author'] = get_query_var( 'author' );
						}
					}
					break;
				case 'search':
					$args['s'] = get_search_query();
					break;
			}
		}

		/**
		 * Filters the WP_Query args for the Post Video block.
		 *
		 * Allows overriding the post count above the editor's slider limit.
		 *
		 * @param array $args  WP_Query arguments.
		 * @param array $attrs Processed block attributes.
		 */
		$args = apply_filters( 'magazine_blocks_post_video_query_args', $args, $attrs );

		return new WP_Query( $args );
	}

	/**
	 * Render the main block HTML structure.
	 *
	 * @param WP_Query $query WP_Query object.
	 * @param array    $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	protected function render_block( $query, $attributes ) {
		$classes = array(
			'mzb-post-video',
			'mzb-post-video-' . $attributes['client_id'],
			$attributes['presets'] ? 'mzb-preset-' . $attributes['presets'] : '',
		);

		// Add layout-4 specific classes.
		if ( 'style-4' === $attributes['presets'] ) {
			$classes[] = 'mzb-layout-4-top-row-' . $attributes['layout4_top_row_count'];
			$classes[] = 'mzb-layout-4-bottom-row-' . $attributes['layout4_bottom_row_count'];
		}

		$html  = sprintf( '<div class="%s">', esc_attr( implode( ' ', array_filter( $classes ) ) ) );
		$html .= $this->render_posts_container( $query, $attributes );
		$html .= '</div>';

		wp_reset_postdata();
		return $html;
	}

	/**
	 * Render posts container.
	 *
	 * @param WP_Query $query WP_Query object.
	 * @param array    $attributes Block attributes.
	 * @return string Posts container HTML.
	 */
	protected function render_posts_container( $query, $attributes ) {
		$classes = array(
			'mzb-posts',
			'mzb-post-col--' . $attributes['column'],
		);

		// Add full width class for single post in style-3.
		if ( 'style-3' === $attributes['presets'] && 1 === $attributes['post_count'] ) {
			$classes[] = 'mzb-post-col--full';
		}

		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', array_filter( $classes ) ) ) );

		// Render posts.
		$index      = 1;
		$first_post = false;

		while ( $query->have_posts() ) {
			$query->the_post();
			$is_first_post = ! $first_post;
			if ( $is_first_post ) {
				$first_post = true;
			}

			$html .= $this->render_post( get_the_ID(), $attributes, $index, $is_first_post );
			++$index;
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render individual post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @param int   $index Post index.
	 * @param bool  $is_first_post Whether this is the first post.
	 * @return string Post HTML.
	 */
	protected function render_post( $post_id, $attributes, $index, $is_first_post ) {
		$classes = array( 'mzb-post' );

		// Add highlight class for first post in certain layouts.
		if ( $attributes['column'] && 1 === $index && 'style-4' !== $attributes['presets'] ) {
			$classes[] = 'mzb-first-video--highlight';
		}

		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', array_filter( $classes ) ) ) );

		// Render video section.
		$html .= $this->render_video_section( $post_id, $attributes );

		// Render content section.
		$html .= $this->render_content_section( $post_id, $attributes, $is_first_post );

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render video section.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Video HTML.
	 */
	protected function render_video_section( $post_id, $attributes ) {
		$video_url = get_post_meta( $post_id, 'video_url', true );
		$src       = get_the_post_thumbnail_url( $post_id ) ? get_the_post_thumbnail_url( $post_id ) : MAGAZINE_BLOCKS_ASSETS_DIR_URL . '/images/default_thumbnail.png';

		$html = '<a class="mzb-video" href="' . esc_url( get_the_permalink( $post_id ) ) . '">';

		if ( $video_url ) {
			$html .= '<video class="mzb-video-player" src="' . esc_url( $video_url ) . '" controls poster="' . esc_url( $src ) . '"></video>';
		} else {
			$html .= '<img class="mzb-featured-image" src="' . esc_url( $src ) . '" alt="' . esc_attr( get_the_title( $post_id ) ) . '"/>';
		}

		$html .= '<div class="mzb-image-overlay"></div>';
		$html .= '<div class="mzb-custom-embed-play" role="button">
					<svg viewBox="0 0 18 21" xmlns="http://www.w3.org/2000/svg">
						<path d="M17.6602 10.9341L0.339646 20.9341L0.339647 0.934081L17.6602 10.9341Z" />
					</svg>
				</div>';
		$html .= '</a>';

		return $html;
	}

	/**
	 * Render content section.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @param bool  $is_first_post Whether this is the first post.
	 * @return string Content HTML.
	 */
	protected function render_content_section( $post_id, $attributes, $is_first_post ) {
		$html = '<div class="mzb-post-content">';

		// Render category meta.
		$html .= $this->render_category_meta( $post_id, $attributes, $is_first_post );

		// Render top meta (author and date).
		if ( ( 'style-3' !== $attributes['presets'] || $is_first_post ) && ( $attributes['enable_author'] || $attributes['enable_date'] ) && 'top' === $attributes['meta_position'] ) {
			$html .= $this->render_post_meta_info( $post_id, $attributes );
		}

		// Render title.
		if ( $attributes['enable_title'] ) {
			$html .= $this->render_post_title( $post_id, $attributes['post_title_markup'] );
		}

		// Render bottom meta (author and date).
		if ( ( 'style-3' !== $attributes['presets'] || $is_first_post ) && ( $attributes['enable_author'] || $attributes['enable_date'] ) && 'bottom' === $attributes['meta_position'] ) {
			$html .= $this->render_post_meta_info( $post_id, $attributes );
		}

		// Render excerpt and read more.
		if ( $attributes['enable_excerpt'] || $attributes['enable_readmore'] ) {
			$html .= $this->render_excerpt_and_read_more( $post_id, $attributes );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render category meta.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @param bool  $is_first_post Whether this is the first post.
	 * @return string Category HTML.
	 */
	protected function render_category_meta( $post_id, $attributes, $is_first_post ) {
		if ( ( $attributes['enable_highlight_post_category'] && $is_first_post ) || ( $attributes['enable_category'] && ! $is_first_post ) ) {
			return '<div class="mzb-post-meta">' . $this->render_categories( $post_id, $attributes['enable_override_category_color'] ) . '</div>';
		}
		return '';
	}

	/**
	 * Render categories with optional color override.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $enable_override_color Whether to enable color override.
	 * @return string Categories HTML.
	 */
	protected function render_categories( $post_id, $enable_override_color = false ) {
		$categories = get_the_category_list( ' ', '', $post_id );
		if ( ! $categories ) {
			return '';
		}

		// Add category link classes for color override.
		if ( $enable_override_color ) {
			$categories = preg_replace_callback(
				'/<a(.+?)href="([^"]+)"(.+?)>([^<]+)<\/a>/',
				function ( $matches ) {
					$cat_id = get_cat_ID( $matches[4] );
					$color  = function_exists( 'colormag_category_color' ) ? colormag_category_color( $cat_id ) : '';
					return sprintf(
						'<a%shref="%s"%s style="%s">%s</a>',
						$matches[1],
						$matches[2],
						$matches[3],
						$color ? 'background-color:' . esc_attr( $color ) . ';' : '',
						$matches[4]
					);
				},
				$categories
			);
		}

		return '<span class="mzb-post-categories">' . $categories . '</span>';
	}

	/**
	 * Render post meta information.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Post meta HTML.
	 */
	protected function render_post_meta_info( $post_id, $attributes ) {
		if (
			! $attributes['enable_author'] && ! $attributes['enable_date']
		) {
			return '';
		}

		$classes = array( 'mzb-post-entry-meta' );
		if ( $attributes['enable_meta_separator'] && $attributes['meta_separator'] ) {
			$classes[] = 'mzb-meta-separator--' . $attributes['meta_separator'];
		}

		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', $classes ) ) );

		if ( $attributes['enable_author'] ) {
			$html .= $this->render_author( $post_id, $attributes['enable_icon'] );
		}

		if ( $attributes['enable_date'] ) {
			$html .= $this->render_date( $post_id, $attributes['enable_icon'] );
		}

		$html .= '</div>';

		return $html;
	}
}
