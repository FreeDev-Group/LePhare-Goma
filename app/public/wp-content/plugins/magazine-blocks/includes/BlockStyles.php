<?php
/**
 * BlockStyles.
 *
 * @since x.x.x
 * @package Magazine Blocks
 */

namespace MagazineBlocks;

defined( 'ABSPATH' ) || exit;

/**
 * Class BlockStyles.
 */
class BlockStyles {

	const TABLET_BREAKPOINT = '62em';
	const MOBILE_BREAKPOINT = '48em';
	const DEVICES           = array(
		'desktop',
		'tablet',
		'mobile',
	);

	/**
	 * Array of blocks data.
	 *
	 * @var array Array of blocks data.
	 */
	private $blocks;

	/**
	 * Current page or template id.
	 *
	 * @var int|string Current page or template id.
	 */
	private $id;

	/**
	 * Force generate.
	 *
	 * @var bool Force generate.
	 */
	private $force_generate;

	/**
	 * Has old button markup.
	 *
	 * @var bool Has old button markup.
	 */
	private $has_old_button_markup;

	/**
	 * Stylesheet filename.
	 *
	 * @var string Stylesheet filename.
	 */
	private $filename;

	/**
	 * Generated styles.
	 *
	 * @var string Generated styles.
	 */
	private $styles;

	/**
	 * Generated css data.
	 *
	 * @var array Generated css.
	 */
	private $css = array();

	/**
	 * Used fonts.
	 *
	 * @var array Used fonts.
	 */
	private $fonts = array();

	/**
	 * Whether a magazine block was found unregistered during generation.
	 *
	 * @var bool Whether a magazine block was found unregistered during generation.
	 */
	private $has_registration_gap = false;

	/**
	 * Settings.
	 *
	 * @var \MagazineBlocks\Setting
	 */
	private $setting = null;

	/**
	 * Constructor.
	 *
	 * @param array           $blocks Block data.
	 * @param null|int|string $id Current page or template id.
	 * @param bool            $force_generate Force generate styles.
	 */
	public function __construct( array &$blocks, $id = null, bool $force_generate = false ) {
		$this->id             = $id ?? magazine_blocks_get_the_id();
		$this->id             = apply_filters(
			'magazine_blocks_block_styles_id',
			$this->id,
			$blocks
		);
		$this->force_generate = $force_generate;
		$this->blocks         = &$blocks;

		if ( empty( $this->blocks ) || empty( $this->id ) ) {
			return;
		}

		$this->has_old_button_markup = $this->has_old_button_markup();

		$this->maybe_generate();
	}

	/**
	 * Enqueue.
	 *
	 * @param bool|string $version Version.
	 * @return void
	 */
	public function enqueue( $version = false ) {
		if ( empty( $this->styles ) ) {
			return;
		}

		$inline = true;

		if ( ! magazine_blocks_is_preview() ) {
			$inline = ! ( magazine_blocks_get_setting( 'asset-generation.external-file', false ) && file_exists( MAGAZINE_BLOCKS_UPLOAD_DIR . "/$this->filename" ) );
		}

		if ( ! $inline ) {
			wp_enqueue_style(
				$this->get_style_enqueue_handle(),
				MAGAZINE_BLOCKS_UPLOAD_DIR_URL . "/$this->filename",
				array( 'magazine-blocks-blocks' ),
				$version // Version is always false, on every build filename is different.
			);
			return;
		}

		wp_register_style( $this->get_style_enqueue_handle(), false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( $this->get_style_enqueue_handle() );
		wp_add_inline_style( $this->get_style_enqueue_handle(), $this->styles );
		unset( $this->styles );
	}

	protected function get_style_enqueue_handle() {
		return "magazine-blocks-blocks-css-$this->id";
	}

	protected function get_font_enqueue_handle() {
		return "magazine-blocks-fonts-css-$this->id";
	}

	/**
	 * Enqueue Google fonts.
	 *
	 * @param array $additional_fonts Additional fonts.
	 *
	 * @return void
	 */
	public function enqueue_fonts( $additional_fonts = array() ) {
		if ( ! empty( $additional_fonts ) ) {
			foreach ( $additional_fonts as $family => $weight ) {
				if ( isset( $this->fonts[ $family ] ) ) {
					$this->fonts[ $family ] = array_unique( array_merge( $this->fonts[ $family ], $weight ) );
				} else {
					$this->fonts[ $family ] = $weight;
				}
			}
		}

		if ( empty( $this->fonts ) ) {
			return;
		}

		array_walk(
			$this->fonts,
			function ( &$value, $key ) {
				$value = trim( $key ) . ':' . trim( rawurlencode( implode( ',', array_unique( $value ) ) ) );
			}
		);

		$google_fonts_url = add_query_arg(
			array(
				'family' => implode( '|', array_values( $this->fonts ) ),
			),
			'https://fonts.googleapis.com/css'
		);

		if ( magazine_blocks_get_setting( 'performance.local-google-fonts', false ) ) {
			$preload          = magazine_blocks_get_setting( 'performance.preload-local-fonts', false );
			$google_fonts_url = magazine_blocks_get_webfont_url( $google_fonts_url, 'woff2', $preload );
		}

		wp_enqueue_style(
			$this->get_font_enqueue_handle(),
			add_query_arg( 'display', 'swap', $google_fonts_url ),
			array(),
			MAGAZINE_BLOCKS_VERSION
		);
	}

	/**
	 * Get styles.
	 *
	 * @return string
	 */
	public function get_styles() {
		return $this->styles;
	}

	/**
	 * Get fonts.
	 *
	 * @return array
	 */
	public function get_fonts() {
		return $this->fonts;
	}

	/**
	 * Get style filename.
	 *
	 * @return string
	 */
	public function get_filename() {
		return $this->filename;
	}

	/**
	 * Maybe generate.
	 *
	 * @return void
	 */
	private function maybe_generate() {
		$saved = $this->get_saved_styles();

		if ( ! $this->force_generate && ! empty( $saved ) && ! magazine_blocks_is_preview() ) {
			$this->styles   = magazine_blocks_array_get( $saved, 'stylesheet', '' );
			$this->filename = magazine_blocks_array_get( $saved, 'filename', '' );
			$this->fonts    = magazine_blocks_array_get( $saved, 'fonts', array() );
			unset( $this->blocks );
			return;
		}

		try {
			$this->create_style_file();
			$this->generate();
			$this->make_styles();

			// Don't cache an incomplete run so the next request retries.
			if ( $this->has_registration_gap ) {
				return;
			}

			$this->update_styles();
			$this->write();
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Do nothing.
		}
	}

	/**
	 * Make styles.
	 *
	 * @return void
	 */
	private function make_styles() {
		if ( empty( $this->css ) ) {
			return;
		}

		$styles            = $this->make_all_device_styles();
		$tablet_breakpoint = magazine_blocks_get_setting( 'editor.responsive-breakpoints.tablet', self::TABLET_BREAKPOINT ) . 'px';
		$mobile_breakpoint = magazine_blocks_get_setting( 'editor.responsive-breakpoints.mobile', self::MOBILE_BREAKPOINT ) . 'px';

		$this->styles  = $styles['desktop'];
		$this->styles .= $this->make_media_query_styles( $styles['tablet'], "(max-width: $tablet_breakpoint)" );
		$this->styles .= $this->make_media_query_styles( $styles['mobile'], "(max-width: $mobile_breakpoint)" );
		$this->styles .= $this->make_media_query_styles(
			$styles['tablet_only'],
			"(min-width: $mobile_breakpoint) and (max-width: $tablet_breakpoint)"
		);
		$this->styles .= $this->make_media_query_styles( $styles['desktop_only'], "(min-width: $tablet_breakpoint)" );

		unset( $this->css );
	}

	/**
	 * Make all device styles.
	 *
	 * @return array
	 */
	private function make_all_device_styles() {
		$styles = array(
			'desktop'      => '',
			'tablet'       => '',
			'mobile'       => '',
			'tablet_only'  => '',
			'desktop_only' => '',
		);

		foreach ( $this->css as $device => $css ) {
			$css    = magazine_blocks_array_combine_keys( $css );
			$result = '';

			array_walk(
				$css,
				function ( $declarations, $selector ) use ( &$result ) {
					$styles = '';
					foreach ( $declarations as $property => $value ) {
						$styles .= "$property:$value;";
					}
					$result .= "$selector{{$styles}}";
				}
			);

			$styles[ $device ] .= $result;
		}

		return $styles;
	}

	/**
	 * Make media query styles.
	 *
	 * @param string $styles CSS declarations.
	 * @param string $media_query Media query.
	 *
	 * @return string
	 */
	private function make_media_query_styles( string $styles, string $media_query ) {
		if ( empty( $styles ) ) {
			return '';
		}
		return "@media $media_query{{$styles}}";
	}

	/**
	 * Generate.
	 *
	 * @return void
	 */
	private function generate() {
		foreach ( $this->blocks as $block ) {
			$namespace = $block['blockName'];
			$attrs     = $block['attrs'];

			if ( $this->has_old_button_markup ) {
				$namespace = 'magazine-blocks/button' === $namespace ? 'magazine-blocks/button' : $namespace;
			}

			$name = explode( '/', $namespace )[1];

			$attrs_def = $this->get_attribute_def( $namespace );

			if ( ! $attrs_def ) {
				$this->has_registration_gap = true;
				continue;
			}

			if ( ! isset( $attrs['clientId'] ) ) {
				continue;
			}

			if ( ! $this->has_old_button_markup ) {
				$name = 'button' === $name ? 'button' : ( 'button' === $name ? 'buttons' : $name );
			}

			// Selector, not a declaration value, so sanitize_value() below never sees it - strip here too.
			$wrapper_class = $this->sanitize_value( '.mzb-' . $name . '-' . $attrs['clientId'] );

			foreach ( $attrs_def as $setting_id => $data ) {
				$styles_def = $data['style'] ?? false;

				if ( empty( $styles_def ) ) {
					continue;
				}

				$value = $this->get_setting_value( $data, $attrs[ $setting_id ] ?? null );

				if ( empty( $value ) ) {
					continue;
				}

				$value = $this->sanitize_value( $value );

				$this->css = magazine_blocks_parse_args(
					$this->css,
					$this->generate_style_by_prop( $value, $styles_def, $attrs, $attrs_def, $wrapper_class )
				);
			}
		}
		unset( $this->blocks );
	}

	/**
	 * Get setting value.
	 *
	 * @param mixed $data Setting definition.
	 * @param mixed $value Setting value.
	 *
	 * @return mixed
	 */
	private function get_setting_value( $data, $value ) {
		$default = magazine_blocks_array_get( $data, 'default' );
		return $value ?? $default;
	}

	/**
	 * Generate style by prop.
	 *
	 * @param mixed  $value Setting value.
	 * @param array  $styles_def Styles definition.
	 * @param array  $attrs Block attributes.
	 * @param array  $attrs_def Block attributes def.
	 * @param string $wrapper_class Wrapper id.
	 *
	 * @return array
	 */
	private function generate_style_by_prop( $value, $styles_def, $attrs, $attrs_def, $wrapper_class ) {
		if ( isset( $value['border'] ) ) {
			unset( $value['border'] );
			$callback = array( $this, 'border' );
		} elseif ( isset( $value['background'] ) ) {
			unset( $value['background'] );
			$callback = array( $this, 'background' );
		} elseif ( isset( $value['typography'] ) ) {
			unset( $value['typography'] );
			$this->generate_fonts( $value );
			$callback = array( $this, 'typography' );
		} elseif ( isset( $value['dimension'] ) ) {
			unset( $value['dimension'] );
			$callback = array( $this, 'dimension' );
		} elseif ( isset( $value['boxShadow'] ) ) {
			unset( $value['boxShadow'] );
			$callback = array( $this, 'box_shadow' );
		} elseif ( isset( $value['topSeparator'] ) || isset( $value['bottomSeparator'] ) ) {
			unset( $value['topSeparator'] );
			unset( $value['bottomSeparator'] );
			$callback = array( $this, 'separator' );
		} else {
			$callback = array( $this, 'general' );
		}
		return call_user_func_array( $callback, array( $value, $styles_def, $attrs, $attrs_def, $wrapper_class ) );
	}

	/**
	 * Generate fonts from blocks.
	 *
	 * @param array $value Setting value.
	 *
	 * @return void
	 */
	private function generate_fonts( array $value ) {
		if ( ! isset( $value['family'] ) || 'default' === strtolower( $value['family'] ) ) {
			return;
		}

		$weight = (string) ( $value['weight'] ?? '400' );
		$family = $value['family'];

		if ( ! isset( $this->fonts[ $family ] ) ) {
			$this->fonts[ $family ] = array( $weight );
		} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
			if ( ! in_array( $weight, $this->fonts[ $family ], true ) ) {
				$this->fonts[ $family ][] = $weight;
			}
		}
	}

	/**
	 * Generate style by prop.
	 *
	 * @param mixed  $value Setting value.
	 * @param array  $styles_def Styles definition.
	 * @param array  $attrs Block attributes.
	 * @param array  $attrs_def Block attributes def.
	 * @param string $wrapper_class Wrapper id.
	 *
	 * @return array
	 */
	private function border( $value, $styles_def, $attrs, $attrs_def, $wrapper_class ) {
		$css = array();

		if ( empty( $value ) ) {
			return $css;
		}

		foreach ( $styles_def as $style_def ) {
			if ( ! $this->check_condition( $style_def['condition'] ?? array(), $attrs, $attrs_def ) ) {
				continue;
			}
			if ( ! isset( $style_def['selector'] ) ) {
				continue;
			}

			$selector = str_replace( '{{WRAPPER}}', $wrapper_class, $style_def['selector'] );
			$style    = magazine_blocks_array_get( $value, 'type', 'none' );

			if ( 'none' !== $style ) {
				$css['desktop'][ $selector ]['border-style'] = $style;

				if ( isset( $value['color'] ) ) {
					$color = is_string( $value['color'] ) ? trim( $value['color'] ) : $value['color'];
					if ( is_string( $color ) && strpos( $color, 'linear-gradient' ) === 0 ) {
						// Linear gradient: use border-image and border-image-slice
						$css['desktop'][ $selector ]['border-image']       = $color . ' 1';
						$css['desktop'][ $selector ]['border-image-slice'] = '1';
					} else {
						// Regular color
						$css['desktop'][ $selector ]['border-color'] = $color;
					}
				}

				$this->process_border_size_styles( $value, $selector, $css );
			}
			$this->process_border_radius_styles( $value, $selector, $css );
		}

		return $css;
	}

	/**
	 * Process border size styles.
	 *
	 * @param array  $value Setting value.
	 * @param string $selector CSS selector.
	 * @param array  $css CSS.
	 *
	 * @return void
	 */
	private function process_border_size_styles( $value, $selector, &$css ) {
		if ( ! isset( $value['size'] ) ) {
			return;
		}
		foreach ( self::DEVICES as $device ) {
			$device_val = magazine_blocks_array_get( $value, "size.$device", array() );
			$val        = $this->sides_style( $device_val );
			if ( false !== $val ) {
				$css[ $device ][ $selector ]['border-width'] = $val;
			}
		}
	}

	/**
	 * Process border radius styles.
	 *
	 * @param array  $value Setting value.
	 * @param string $selector CSS selector.
	 * @param array  $css CSS.
	 *
	 * @return void
	 */
	private function process_border_radius_styles( $value, $selector, &$css ) {
		if ( ! isset( $value['radius'] ) ) {
			return;
		}
		foreach ( self::DEVICES as $device ) {
			$device_val = magazine_blocks_array_get( $value, "radius.$device", array() );
			$val        = $this->sides_style( $device_val );
			if ( false !== $val ) {
				$css[ $device ][ $selector ]['border-radius'] = $val;
			}
		}
	}

	/**
	 * Generate style by prop.
	 *
	 * @param mixed  $value Setting value.
	 * @param array  $styles_def Styles definition.
	 * @param array  $attrs Block attributes.
	 * @param array  $attrs_def Block attributes def.
	 * @param string $wrapper_class Wrapper id.
	 *
	 * @return array
	 */
	private function dimension( $value, $styles_def, $attrs, $attrs_def, $wrapper_class ) {
		$css = array(
			'desktop' => array(),
			'tablet'  => array(),
			'mobile'  => array(),
		);

		if ( empty( $value ) ) {
			return $css;
		}

		foreach ( $styles_def as $style_def ) {
			if ( ! $this->check_condition( $style_def['condition'] ?? array(), $attrs, $attrs_def ) ) {
				continue;
			}
			if ( ! isset( $style_def['selector'] ) ) {
				continue;
			}

			$selector = str_replace( '{{WRAPPER}}', $wrapper_class, $style_def['selector'] );

			if ( magazine_blocks_array_has_any( $value, self::DEVICES ) ) {
				foreach ( self::DEVICES as $device ) {
					$val = $this->sides_style( $value[ $device ] ?? array() );
					if ( false === $val ) {
						continue;
					}
					$css[ $device ] = magazine_blocks_parse_args(
						$css[ $device ],
						$this->parse_css_string(
							str_replace( '{{VALUE}}', $val, $selector )
						)
					);
				}
			} else {
				$val = $this->sides_style( $value );
				if ( false !== $val ) {
					$css['desktop'] = magazine_blocks_parse_args(
						$css['desktop'],
						$this->parse_css_string(
							str_replace( '{{VALUE}}', $val, $selector )
						)
					);
				}
			}
		}

		return $css;
	}

	/**
	 * Generate style by prop.
	 *
	 * @param mixed  $value Setting value.
	 * @param array  $styles_def Styles definition.
	 * @param array  $attrs Block attributes.
	 * @param array  $attrs_def Block attributes def.
	 * @param string $wrapper_class Wrapper id.
	 *
	 * @return array
	 */
	private function background( $value, $styles_def, $attrs, $attrs_def, $wrapper_class ) {
		$css = array();

		if ( empty( $value ) ) {
			return $css;
		}

		$type = magazine_blocks_array_get( $value, 'type', 'color' );

		foreach ( $styles_def as $style_def ) {
			if ( ! $this->check_condition( $style_def['condition'] ?? array(), $attrs, $attrs_def ) ) {
				continue;
			}
			if ( ! isset( $style_def['selector'] ) ) {
				continue;
			}
			$selector = str_replace( '{{WRAPPER}}', $wrapper_class, $style_def['selector'] );

			if ( 'gradient' === $type && isset( $value['gradient'] ) ) {
				$css['desktop'][ $selector ]['background'] = $value['gradient'];
				continue;
			}

			if ( isset( $value['color'] ) ) {
				$css['desktop'][ $selector ]['background-color'] = $value['color'];
			}

			$url = magazine_blocks_array_get( $value, 'image.image.url' );
			$url = $url ?? magazine_blocks_array_get( $value, 'image.image.local' );
			$url = $url ?? magazine_blocks_array_get( $value, 'image.image.external' );

			if ( empty( $url ) ) {
				continue;
			}

			$css['desktop'][ $selector ]['background-image'] = "url($url)";

			$attachment = magazine_blocks_array_get( $value, 'image.attachment', 'default' );

			if ( 'default' !== $attachment ) {
				$css['desktop'][ $selector ]['background-attachment'] = $attachment;
			}

			$this->process_responsive_background_styles( $value, $selector, $css );
		}

		return $css;
	}

	/**
	 * Process responsive background styles.
	 *
	 * @param array  $value Setting value.
	 * @param string $selector CSS selector.
	 * @param array  $css CSS.
	 *
	 * @return void
	 */
	private function process_responsive_background_styles( $value, $selector, &$css ) {
		foreach ( self::DEVICES as $device ) {
			$position = magazine_blocks_array_get( $value, "image.position.$device", 'default' );
			$repeat   = magazine_blocks_array_get( $value, "image.repeat.$device", 'default' );
			$size     = magazine_blocks_array_get( $value, "image.size.$device", 'default' );
			if ( 'default' !== $position ) {
				$css[ $device ][ $selector ]['background-position'] = $position;
			}
			if ( 'default' !== $repeat ) {
				$css[ $device ][ $selector ]['background-repeat'] = $repeat;
			}
			if ( 'default' !== $size || 'custom' !== $size ) {
				$css[ $device ][ $selector ]['background-size'] = $size;
			}
			if ( 'custom' === $size ) {
				$key         = 'customSize' . ucfirst( $device );
				$custom_size = magazine_blocks_array_get( $value, "image.$key.value" );
				$custom_unit = magazine_blocks_array_get( $value, "image.$key.unit", 'px' );
				if ( $custom_size ) {
					$css[ $device ][ $selector ]['background-size'] = "$custom_size$custom_unit auto";
				}
			}
		}
	}

	/**
	 * Generate style by prop.
	 *
	 * @param mixed  $value Setting value.
	 * @param array  $styles_def Styles definition.
	 * @param array  $attrs Block attributes.
	 * @param array  $attrs_def Block attributes def.
	 * @param string $wrapper_class Wrapper id.
	 *
	 * @return array
	 */
	private function box_shadow( $value, $styles_def, $attrs, $attrs_def, $wrapper_class ) {
		$css = array();

		if ( ! isset( $value['enable'] ) || ! $value['enable'] ) {
			return $css;
		}

		foreach ( $styles_def as $style_def ) {
			if ( ! $this->check_condition( $style_def['condition'] ?? array(), $attrs, $attrs_def ) ) {
				continue;
			}
			if ( ! isset( $style_def['selector'] ) ) {
				continue;
			}

			$selector     = str_replace( '{{WRAPPER}}', $wrapper_class, $style_def['selector'] );
			$position     = magazine_blocks_array_get( $value, 'position', 'outline' );
			$horizontal_x = magazine_blocks_array_get( $value, 'horizontalX', '0' );
			$vertical_y   = magazine_blocks_array_get( $value, 'verticalY', '0' );
			$blur         = magazine_blocks_array_get( $value, 'blur', '10' );
			$spread       = magazine_blocks_array_get( $value, 'spread', '0' );
			$color        = magazine_blocks_array_get( $value, 'color', 'rgba(0,0,0, 0.5)' );

			$css['desktop'][ $selector ]['box-shadow'] = sprintf(
				'%s%spx %spx %spx %spx %s',
				'inset' === $position ? 'inset ' : '',
				$horizontal_x,
				$vertical_y,
				$blur,
				$spread,
				$color
			);
		}

		return $css;
	}

	/**
	 * Generate separator css.
	 *
	 * @param array  $value Separator value.
	 * @param array  $styles_def Styles definition.
	 * @param array  $attrs Saved attributes.
	 * @param array  $attrs_def Attributes definition.
	 * @param string $wrapper_class Wrapper class.
	 * @return void
	 */
	private function separator( $value, $styles_def, $attrs, $attrs_def, $wrapper_class ) {
		$css = array();

		if ( ! isset( $value['enable'] ) || ! $value['enable'] ) {
			return $css;
		}

		foreach ( $styles_def as $style_def ) {
			if ( ! $this->check_condition( $style_def['condition'] ?? array(), $attrs, $attrs_def ) ) {
				continue;
			}
			if ( ! isset( $style_def['selector'] ) ) {
				continue;
			}

			$selector     = str_replace( '{{WRAPPER}}', $wrapper_class, $style_def['selector'] );
			$fill         = magazine_blocks_array_get( $value, 'color', '#fff' );
			$height       = magazine_blocks_array_get( $value, 'height', 100 );
			$width        = magazine_blocks_array_get( $value, 'width', 1 );
			$shadow_color = magazine_blocks_array_get( $value, 'shadow_color', '#fff' );
			$horizontal_x = magazine_blocks_array_get( $value, 'horizontalX', 0 );
			$vertical_y   = magazine_blocks_array_get( $value, 'verticalY', 0 );
			$blur         = magazine_blocks_array_get( $value, 'blur', 0 );

			$css['desktop'][ $selector ]['fill']      = $fill;
			$css['desktop'][ $selector ]['height']    = $height . 'px';
			$css['desktop'][ $selector ]['transform'] = 'scaleX(' . $width . ')';
			$css['desktop'][ $selector ]['filter']    = 'drop-shadow(' . $horizontal_x . 'px ' . $vertical_y . 'px ' . $blur . 'px ' . $shadow_color . ')';
		}

		return $css;
	}

	/**
	 * Generate style by prop.
	 *
	 * @param mixed  $value Setting value.
	 * @param array  $styles_def Styles definition.
	 * @param array  $attrs Block attributes.
	 * @param array  $attrs_def Block attributes def.
	 * @param string $wrapper_class Wrapper id.
	 *
	 * @return array
	 */
	private function typography( $value, $styles_def, $attrs, $attrs_def, $wrapper_class ) {
		$css = array();
		if ( empty( $value ) ) {
			return $css;
		}
		foreach ( $styles_def as $style_def ) {
			if ( ! $this->check_condition( $style_def['condition'] ?? array(), $attrs, $attrs_def ) ) {
				continue;
			}

			if ( ! isset( $style_def['selector'] ) ) {
				continue;
			}

			$selector   = str_replace( '{{WRAPPER}}', $wrapper_class, $style_def['selector'] );
			$family     = magazine_blocks_array_get( $value, 'family', 'Default' );
			$weight     = magazine_blocks_array_get( $value, 'weight', 'default' );
			$transform  = magazine_blocks_array_get( $value, 'transform', 'default' );
			$decoration = magazine_blocks_array_get( $value, 'decoration', 'default' );
			$style      = magazine_blocks_array_get( $value, 'fontStyle', 'default' );

			if ( 'default' !== strtolower( $family ) ) {
				$css['desktop'][ $selector ]['font-family'] = $family;
			}
			if ( $weight && 'default' !== strtolower( $weight ) ) {
				$css['desktop'][ $selector ]['font-weight'] = $weight;
			}
			if ( 'default' !== strtolower( $transform ) ) {
				$css['desktop'][ $selector ]['text-transform'] = $transform;
			}
			if ( 'default' !== strtolower( $decoration ) ) {
				$css['desktop'][ $selector ]['text-decoration'] = $decoration;
			}
			if ( 'default' !== strtolower( $style ) ) {
				$css['desktop'][ $selector ]['font-style'] = $style;
			}

			$this->process_responsive_typography_styles( $value, $selector, $css );
		}
		return $css;
	}

	/**
	 * Process responsive typography styles.
	 *
	 * @param array  $value Setting value.
	 * @param string $selector CSS selector.
	 * @param array  $css CSS.
	 *
	 * @return void
	 */
	private function process_responsive_typography_styles( $value, $selector, &$css ) {
		foreach ( self::DEVICES as $device ) {
			$size                = magazine_blocks_array_get( $value, "size.$device.value", false );
			$size_unit           = magazine_blocks_array_get( $value, "size.$device.unit", 'px' );
			$line_height         = magazine_blocks_array_get( $value, "lineHeight.$device.value", false );
			$line_height_unit    = magazine_blocks_array_get( $value, "lineHeight.$device.unit", 'px' );
			$letter_spacing      = magazine_blocks_array_get( $value, "letterSpacing.$device.value", false );
			$letter_spacing_unit = magazine_blocks_array_get( $value, "letterSpacing.$device.unit", 'px' );
			if ( is_numeric( $size ) ) {
				$css[ $device ][ $selector ]['font-size'] = $size . $size_unit;
			}
			if ( is_numeric( $line_height ) ) {
				$css[ $device ][ $selector ]['line-height'] = $line_height . $line_height_unit;
			}
			if ( is_numeric( $letter_spacing ) ) {
				$css[ $device ][ $selector ]['letter-spacing'] = $letter_spacing . $letter_spacing_unit;
			}
		}
	}

	/**
	 * Generate style by prop.
	 *
	 * @param mixed  $value Setting value.
	 * @param array  $styles_def Styles definition.
	 * @param array  $attrs Block attributes.
	 * @param array  $attrs_def Block attributes def.
	 * @param string $wrapper_class Wrapper id.
	 *
	 * @return array
	 */
	private function general( $value, $styles_def, $attrs, $attrs_def, $wrapper_class ) {
		$css = array(
			'desktop'      => array(),
			'tablet'       => array(),
			'mobile'       => array(),
			'tablet_only'  => array(),
			'desktop_only' => array(),
		);

		if ( empty( $value ) ) {
			return $css;
		}
		foreach ( $styles_def as $style_def ) {
			if ( ! $this->check_condition( $style_def['condition'] ?? array(), $attrs, $attrs_def ) ) {
				continue;
			}

			if ( ! isset( $style_def['selector'] ) ) {
				continue;
			}

			$selector = str_replace( '{{WRAPPER}}', $wrapper_class, $style_def['selector'] );

			if ( false === strpos( $selector, '{{VALUE}}' ) ) {
				$this->process_non_value_selector( $selector, $css );
				continue;
			}

			if ( ! $value ) {
				continue;
			}

			$this->process_value_selector( $value, $selector, $css );
		}

		return $css;
	}

	/**
	 * Generate style by prop.
	 *
	 * @param mixed  $value Setting value.
	 * @param array  $styles_def Styles definition.
	 * @param array  $attrs Block attributes.
	 * @param array  $attrs_def Block attributes def.
	 * @param string $wrapper_class Wrapper id.
	 *
	 * @return array
	 */
	private function list_gap( $value, $styles_def, $attrs, $attrs_def, $wrapper_class ) {
		$css = array();

		if ( ! isset( $value['enable'] ) || ! $value['enable'] ) {
			return $css;
		}

		foreach ( $styles_def as $style_def ) {
			if ( ! $this->check_condition( $style_def['condition'] ?? array(), $attrs, $attrs_def ) ) {
				continue;
			}
			if ( ! isset( $style_def['selector'] ) ) {
				continue;
			}

			$selector = str_replace( '{{WRAPPER}}', $wrapper_class, $style_def['selector'] );

			// Retrieve the slider value for padding (divided by 2)
			$slider_value = magazine_blocks_array_get( $value, 'sliderValue', '0' );

			// Calculate the padding value based on the slider value
			$padding_value = $slider_value / 2;

			// Generate CSS rules for padding for the selector
			$css['desktop'][ $selector ]['padding-top']    = $padding_value . 'px';
			$css['desktop'][ $selector ]['padding-bottom'] = $padding_value . 'px';
		}

		return $css;
	}



	/**
	 * This function processes CSS selectors and assigns them to specific CSS arrays based on their media
	 * query properties.
	 *
	 * @param string $selector The CSS selector to be processed.
	 * @param array  $css  is an associative array that contains different CSS styles for different screen
	 * sizes. The keys of the array are 'mobile', 'tablet', 'desktop', 'tablet_only', and 'desktop_only'.
	 * The values of the array are CSS declarations in string format.
	 */
	private function process_non_value_selector( $selector, &$css ) {
		if ( false !== strpos( $selector, '@media' ) ) {
			$this->process_media_query_styles( $selector, $css );
		} else {
			$css['desktop'] = magazine_blocks_parse_args( $css['mobile'], $this->parse_css_string( $selector ) );
		}
	}

	/**
	 * Process media query styles.
	 *
	 * @param mixed $selector CSS selector.
	 * @param mixed $css Process css.
	 *
	 * @return void
	 */
	private function process_media_query_styles( $selector, &$css ) {
		preg_match( '/@media\s*\((.*?)\)\s*{(.*?)}/', $selector, $matches );

		$desktop_only = 'min-width:62em';
		$tablet_only  = 'min-width:48em) and (max-width:62em';
		$mobile       = 'max-width:48em';
		$tablet       = 'max-width:62em';

		if ( empty( $matches[1] ) || empty( $matches[2] ) ) {
			return;
		}

		$media_query  = $matches[1];
		$declarations = $matches[2];
		$declarations = substr( $declarations, -1 ) !== '}' ? $declarations . '}' : $declarations;
		$declarations = $this->parse_css_string( $declarations );

		if ( strpos( $media_query, $tablet_only ) !== false ) {
			$css['tablet_only'] = magazine_blocks_parse_args( $css['tablet_only'], $declarations );
			return;
		}
		if ( strpos( $media_query, $desktop_only ) !== false ) {
			$css['desktop_only'] = magazine_blocks_parse_args( $css['desktop_only'], $declarations );
			return;
		}
		if ( strpos( $media_query, $mobile ) !== false ) {
			$css['mobile'] = magazine_blocks_parse_args( $css['mobile'], $declarations );
			return;
		}
		if ( strpos( $media_query, $tablet ) !== false ) {
			$css['tablet'] = magazine_blocks_parse_args( $css['tablet'], $declarations );
		}
	}


	/**
	 * Normalize legacy "left"/"right"/"top"/"bottom" values into their CSS-valid align-items
	 * equivalents. Older UI controls (Advertisement's `alignment`, Section's `verticalAlignment`)
	 * used to save these keywords directly into an align-items declaration, which never accepted
	 * them in any browser; current controls only save "start"/"center"/"end"/"stretch". Existing
	 * saved content can still carry the old keywords, so remap them here rather than emitting
	 * invalid CSS for posts that predate the value change.
	 *
	 * @param string $selector CSS selector template containing the target declaration.
	 * @param mixed  $value Setting value, scalar or array of per-device values.
	 *
	 * @return mixed
	 */
	private function normalize_legacy_align_items_value( $selector, $value ) {
		if ( false === strpos( $selector, 'align-items' ) ) {
			return $value;
		}

		$map = array(
			'left'   => 'flex-start',
			'top'    => 'flex-start',
			'right'  => 'flex-end',
			'bottom' => 'flex-end',
		);

		if ( is_array( $value ) ) {
			return array_map(
				function ( $device_value ) use ( $map ) {
					return is_string( $device_value ) && isset( $map[ $device_value ] ) ? $map[ $device_value ] : $device_value;
				},
				$value
			);
		}

		return is_string( $value ) && isset( $map[ $value ] ) ? $map[ $value ] : $value;
	}

	/**
	 * The method processes a value selector for CSS styling, taking into account different devices and
	 * units.
	 *
	 * @param mixed  $value The value to be processed. It can be a string or an array containing values for different devices.
	 * @param string $selector The CSS selector to apply the value to.
	 * @param array  $css is a reference to an array that contains CSS styles for different devices (desktop,
	 * tablet, mobile). The function updates this array with new styles based on the value and selector
	 * parameters.
	 */
	private function process_value_selector( $value, $selector, &$css ) {
		$value = $this->normalize_legacy_align_items_value( $selector, $value );

		if ( is_array( $value ) ) {
			if ( count( array_intersect( array_keys( $value ), array( 'desktop', 'tablet', 'mobile' ) ) ) > 0 ) {
				foreach ( self::DEVICES as $device ) {
					if ( ! isset( $value[ $device ] ) || '' === $value[ $device ] ) {
						continue;
					}
					if ( is_array( $value[ $device ] ) ) {
						if ( isset( $value[ $device ]['value'] ) ) {
							$unit           = $value[ $device ]['unit'] ?? 'px';
							$temp           = str_replace( '{{VALUE}}', "{$value[ $device ]['value']}$unit", $selector );
							$css[ $device ] = magazine_blocks_parse_args( $css[ $device ], $this->parse_css_string( $temp ) );
						}
					} else {
						$css[ $device ] = magazine_blocks_parse_args(
							$css[ $device ],
							$this->parse_css_string( str_replace( '{{VALUE}}', $value[ $device ], $selector ) )
						);
					}
				}
			} elseif ( isset( $value['value'] ) ) {
				$unit           = $value['unit'] ?? 'px';
				$css['desktop'] = magazine_blocks_parse_args(
					$css['desktop'],
					$this->parse_css_string( str_replace( '{{VALUE}}', "{$value['value']}$unit", $selector ) )
				);
			}
		} else {
			$css['desktop'] = magazine_blocks_parse_args(
				$css['desktop'],
				$this->parse_css_string( str_replace( '{{VALUE}}', $value, $selector ) )
			);
		}
	}

	/**
	 * Parses a CSS string and returns an array of selectors and their corresponding
	 * properties.
	 *
	 * @param string $css_string A string containing CSS rules and declarations.
	 *
	 * @return array An array containing parsed CSS rules, where each selector is a key and its
	 * corresponding properties are stored as an array.
	 */
	private function parse_css_string( string $css_string ): array {
		$css   = array();
		$rules = explode( '}', $css_string );
		foreach ( $rules as $rule ) {
			$parts = explode( '{', $rule, 2 );
			if ( ! isset( $parts[1] ) ) {
				continue;
			}
			$selector   = trim( $parts[0] );
			$properties = array();
			$pairs      = explode( ';', trim( $parts[1] ) );
			foreach ( $pairs as $pair ) {
				$pos = strpos( $pair, ':' );
				if ( false === $pos ) {
					continue;
				}
				$prop                = trim( substr( $pair, 0, $pos ) );
				$val                 = trim( substr( $pair, $pos + 1 ) );
				$properties[ $prop ] = $val;
			}
			$css[ $selector ] = $properties;
		}
		return $css;
	}

	/**
	 * Strips characters that would let a block attribute value break out of the
	 * `<style>` tag the generated stylesheet is ultimately printed inside (see
	 * MagazineBlocks::render_template_to_string() and core's wp_add_inline_style()),
	 * neither of which escapes this stylesheet. Applied once at the point every
	 * attribute value enters style generation so every builder (border, background,
	 * typography, dimension, box_shadow, separator, general) receives safe input.
	 *
	 * @param mixed $value Setting value, scalar or (nested) array.
	 *
	 * @return mixed
	 */
	private function sanitize_value( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'sanitize_value' ), $value );
		}
		if ( is_string( $value ) ) {
			return str_replace( array( '<', '>', '"' ), '', $value );
		}
		return $value;
	}

	/**
	 * Side styles.
	 *
	 * @param mixed $value Setting value.
	 *
	 * @return string
	 */
	private function sides_style( $value ) {
		if ( magazine_blocks_array_has_any( $value, array( 'top', 'right', 'bottom', 'left' ) ) ) {
			$unit                = magazine_blocks_array_get( $value, 'unit', 'px' );
			$top                 = magazine_blocks_array_get( $value, 'top', 0 );
			$right               = magazine_blocks_array_get( $value, 'right', 0 );
			$bottom              = magazine_blocks_array_get( $value, 'bottom', 0 );
			$left                = magazine_blocks_array_get( $value, 'left', 0 );
			$is_equal            = $top === $right && $right === $bottom && $bottom === $left;
			$is_top_bottom_equal = $top === $bottom;
			$is_left_right_equal = $left === $right;

			if ( $is_equal ) {
				return "$top$unit";
			}

			if ( $is_top_bottom_equal && $is_left_right_equal ) {
				return "$top$unit $left$unit";
			}

			return "$top$unit $right$unit $bottom$unit $left$unit";
		}

		return false;
	}

	/**
	 * Get attribute definition.
	 *
	 * @param mixed $block_namespace Block namespace.
	 * @return array|bool
	 */
	private function get_attribute_def( $block_namespace ) {
		return \WP_Block_Type_Registry::get_instance()
			->get_all_registered()[ $block_namespace ]->attributes ?? false;
	}

	/**
	 * Get saved styles.
	 *
	 * @return array|false
	 */
	private function get_saved_styles() {
		return is_int( $this->id ) ?
			get_post_meta( $this->id, '_magazine_blocks_blocks_css', true ) :
			get_option( '_magazine_blocks_blocks_css', array() )[ $this->id ] ?? array();
	}

	/**
	 * Updates the styles data.
	 */
	private function update_styles() {
		$styles_data = array(
			'filename'   => $this->filename,
			'fonts'      => $this->fonts,
			'stylesheet' => $this->styles,
		);

		if ( is_int( $this->id ) ) {
			update_post_meta( $this->id, '_magazine_blocks_blocks_css', $styles_data );
			return;
		}

		$saved              = get_option( '_magazine_blocks_blocks_css', array() );
		$saved[ $this->id ] = $styles_data;

		update_option( '_magazine_blocks_blocks_css', $saved );
	}

	/**
	 * Check if has old button markup.
	 *
	 * @return bool
	 */
	private function has_old_button_markup() {
		foreach ( $this->blocks as $block ) {
			if ( 'magazine-blocks/button' === $block['blockName'] && empty( $block['innerBlocks'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * This function creates a CSS file with a unique filename and deletes any existing CSS files with a
	 * similar name.
	 *
	 * @return false|true false or the result of the `touch` method of the `` object.
	 */
	private function create_style_file() {
		$filesystem = magazine_blocks_get_filesystem();

		if ( ! $filesystem ) {
			return false;
		}

		if (
			! $filesystem->is_dir( MAGAZINE_BLOCKS_UPLOAD_DIR ) &&
			! $filesystem->mkdir( MAGAZINE_BLOCKS_UPLOAD_DIR, FS_CHMOD_DIR )
		) {
			return false;
		}

		$files = $filesystem->dirlist( MAGAZINE_BLOCKS_UPLOAD_DIR );

		if ( $files ) {
			foreach ( $files as $file ) {
				if ( false !== strpos( $file['name'], "mzb-style-$this->id-" ) ) {
					$filesystem->delete( MAGAZINE_BLOCKS_UPLOAD_DIR . '/' . $file['name'] );
				}
			}
		}

		$this->filename = "mzb-style-$this->id-" . time() . '.css';

		return $filesystem->touch( MAGAZINE_BLOCKS_UPLOAD_DIR . '/' . $this->filename );
	}


	/**
	 * This function writes the contents of a file to a specified directory using the Magazine Blocks filesystem.
	 */
	private function write() {
		$filesystem = magazine_blocks_get_filesystem();
		if ( $filesystem ) {
			$filesystem->put_contents( MAGAZINE_BLOCKS_UPLOAD_DIR . '/' . $this->filename, $this->styles );
		}
	}

	/**
	 * Check condition.
	 *
	 * @param array $conditions Conditions to check.
	 * @param array $attrs Block attributes.
	 * @param array $attrs_def Block attributes definition.
	 *
	 * @return boolean
	 */
	private function check_condition( $conditions, $attrs, $attrs_def ) {
		$result = true;

		if ( empty( $conditions ) ) {
			return $result;
		}

		foreach ( $conditions as $condition ) {
			$previous = $result;
			$key      = $condition['key'];
			$value    = magazine_blocks_array_get( $attrs, $key );
			$value    = $value ?? magazine_blocks_array_get( $attrs_def, "$key.default", false );

			if ( ! $value ) {
				continue;
			}

			$relation              = magazine_blocks_array_get( $condition, 'relation', '' );
			$condition_value       = magazine_blocks_array_get( $condition, 'value', '' );
			$is_equal_relation     = '==' === $relation || '===' === $relation;
			$is_non_equal_relation = '!=' === $relation || '!==' === $relation;

			if ( $is_equal_relation ) {
				if ( is_scalar( $condition_value ) ) {
					$result = $value === $condition_value;
				} else {
					$result = in_array( $value, $condition_value, true );
				}
			} elseif ( $is_non_equal_relation ) {
				if ( is_scalar( $condition_value ) ) {
					$result = $value !== $condition_value;
				} else {
					$result = ! in_array( $value, $condition_value, true );
				}
			}

			if ( ! $previous ) {
				$result = false;
			}
		}

		return $result;
	}
}
