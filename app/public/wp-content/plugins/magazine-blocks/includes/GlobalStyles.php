<?php
/**
 * Global styles.
 *
 * @package BlockArt
 */

namespace MagazineBlocks;

use MagazineBlocks\Abstracts\Styles;

/**
 * Global styles.
 */
class GlobalStyles extends Styles {

	/**
	 * Global colors
	 *
	 * @var array
	 */
	protected $colors = array();

	/**
	 * Global typographies.
	 *
	 * @var array
	 */
	protected $typographies = array();

	/**
	 * Global tablet typographies.
	 *
	 * @var array
	 */
	protected $typographies_tablet = array();

	/**
	 * Global mobile typographies.
	 *
	 * @var array
	 */
	protected $typographies_mobile = array();

	/**
	 * Raw styles
	 *
	 * @var array
	 */
	protected $raw_styles = array();

	/**
	 * Typography css.
	 *
	 * @var string
	 */
	protected $typography_css = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->raw_styles    = magazine_blocks_get_global_styles();
		$generated_timestamp = $this->get_styles_generated_timestamp();

		if (
			isset( $this->raw_styles['_modified'] ) && (
				! $generated_timestamp || $generated_timestamp < strtotime( $this->raw_styles['_modified'] )
			)
		) {
			$this->force_generate = true;
		}

		$this->force_generate = true;

		parent::__construct( 'magazine-blocks-global-styles' );
	}

	/**
	 * Generate styles
	 *
	 * @return void
	 */
	protected function generate() {
		$this->process_colors();
		$this->process_typographies();
	}

	/**
	 * Make css.
	 *
	 * @return void
	 */
	protected function make_styles() {
		$styles  = implode( "\n", $this->colors );
		$styles .= "\n";
		$styles .= implode( "\n", $this->typographies );
		$styles  = ":root {{$styles}}";

		$tablet_breakpoint = $this->get_tablet_breakpoint();
		$mobile_breakpoint = $this->get_mobile_breakpoint();
		if ( $this->typographies_tablet ) {
			$tablet_css = implode( "\n", $this->typographies_tablet );
			$styles    .= "\n@media (max-width: $tablet_breakpoint) {:root{{$tablet_css}}}";
		}
		if ( $this->typographies_mobile ) {
			$mobile_css = implode( "\n", $this->typographies_mobile );
			$styles    .= "\n@media (max-width: $mobile_breakpoint) {:root{{$mobile_css}}}";
		}
		$styles .= $this->typography_css;

		$this->styles = $styles;
		$this->set_styles_generated_timestamp();
	}

	/**
	 * Get style enqueue handle.
	 *
	 * @return string
	 */
	protected function get_style_enqueue_handle() {
		return 'magazine-blocks-global-styles';
	}

	/**
	 * Get font enqueue handle.
	 *
	 * @return string
	 */
	protected function get_font_enqueue_handle() {
		return 'magazine-blocks-global-fonts';
	}

	/**
	 * Process colors
	 *
	 * @return void
	 */
	protected function process_colors() {
		if ( empty( $this->raw_styles['colors'] ) ) {
			return;
		}
		foreach ( $this->raw_styles['colors'] as $color ) {
			if ( empty( $color['value'] ) ) {
				continue;
			}
			$this->colors[] = "--mzb-colors-{$color['id']}: {$color['value']};";
		}
	}

	/**
	 * Process typographies.
	 *
	 * @return void
	 */
	protected function process_typographies() {
		if ( empty( $this->raw_styles['typographies'] ) ) {
			return;
		}
		foreach ( $this->raw_styles['typographies'] as $typography ) {
			if ( empty( $typography['value'] ) ) {
				continue;
			}
			$family         = $typography['value']['family'] ?? '';
			$weight         = $typography['value']['weight'] ?? 400;
			$size           = $typography['value']['size'] ?? null;
			$line_height    = $typography['value']['lineHeight'] ?? null;
			$transform      = $typography['value']['transform'] ?? null;
			$letter_spacing = $typography['value']['letterSpacing'] ?? null;
			$font_style     = $typography['value']['fontStyle'] ?? null;
			$decoration     = $typography['value']['decoration'] ?? null;
			$css            = '';

			if ( $family && 'Default' !== $family ) {
				if ( ! isset( $this->fonts[ $family ] ) ) {
					$this->fonts[ $family ] = array();
				}
				if ( ! in_array( $weight, $this->fonts[ $family ], true ) ) {
					$this->fonts[ $family ][] = $weight;
				}
				$this->typographies[] = "--mzb-font-families-{$typography['id']}: {$family};";
				$css                 .= "font-family: var(--mzb-font-families-{$typography['id']}) !important;";
			}
			if ( $weight ) {
				$this->typographies[] = "--mzb-font-weights-{$typography['id']}: {$weight};";
				$css                 .= "font-weight: var(--mzb-font-weights-{$typography['id']}) !important;";
			}

			if ( ! empty( $transform ) && 'default' !== $transform ) {
				$this->typographies[] = "--mzb-font-transforms-{$typography['id']}: {$transform};";
				$css                 .= "text-transform: var(--mzb-font-transforms-{$typography['id']}) !important;";
			}

			if ( ! empty( $decoration ) && 'default' !== $decoration ) {
				$this->typographies[] = "--mzb-font-decorations-{$typography['id']}: {$decoration};";
				$css                 .= "text-decoration: var(--mzb-font-decorations-{$typography['id']}) !important;";
			}

			if ( ! empty( $font_style ) && 'default' !== $font_style ) {
				$this->typographies[] = "--mzb-font-styles-{$typography['id']}: {$font_style};";
				$css                 .= "font-style: var(--mzb-font-styles-{$typography['id']}) !important;";
			}

			if ( $size ) {
				$this->process_responsive_typography( $size, "--mzb-font-sizes-{$typography['id']}" );
				$css .= "font-size: var(--mzb-font-sizes-{$typography['id']}) !important;";
			}
			if ( $letter_spacing ) {
				$this->process_responsive_typography( $letter_spacing, "--mzb-font-letter-spacings-{$typography['id']}" );
				$css .= "letter-spacing: var(--mzb-font-letter-spacings-{$typography['id']}) !important;";
			}
			if ( $line_height ) {
				$this->process_responsive_typography( $line_height, "--mzb-font-line-heights-{$typography['id']}" );
				$css .= "line-height: var(--mzb-font-line-heights-{$typography['id']}) !important;";
			}
			if ( ! empty( $css ) ) {
				$this->typography_css .= ".mzb-typography-{$typography['id']} {{$css}}";
			}
		}
	}

	/**
	 * Process responsive typography.
	 *
	 * @param array  $values
	 * @param string $css_prop_name
	 * @return void
	 */
	protected function process_responsive_typography( $values, $css_prop_name ) {
		if ( isset( $values['desktop']['value'] ) ) {
			$unit                 = $values['desktop']['unit'] ?? 'px';
			$this->typographies[] = "{$css_prop_name}: {$values['desktop']['value']}{$unit};";
		}

		if ( isset( $values['tablet']['value'] ) ) {
			$unit                        = $values['tablet']['unit'] ?? 'px';
			$this->typographies_tablet[] = "{$css_prop_name}: {$values['tablet']['value']}{$unit};";
		}

		if ( isset( $values['mobile']['value'] ) ) {
			$unit                        = $values['mobile']['unit'] ?? 'px';
			$this->typographies_mobile[] = "{$css_prop_name}: {$values['mobile']['value']}{$unit};";
		}
	}

	/**
	 * Get styles generated timestamp.
	 *
	 * @return false|int
	 */
	protected function get_styles_generated_timestamp() {
		return get_option( '_magazine_blocks_global_styles_generated_timestamp' );
	}

	/**
	 * Set styles generated timestamp.
	 *
	 * @return void
	 */
	protected function set_styles_generated_timestamp() {
		update_option( '_magazine_blocks_global_styles_generated_timestamp', time() );
	}

	/**
	 * Get saved styles.
	 *
	 * @return array|false
	 */
	protected function get_saved_styles() {
		return get_option( '_magazine_blocks_blocks_css', array() )[ $this->id ] ?? array();
	}

	/**
	 * Get filename prefix.
	 *
	 * @return string
	 */
	protected function get_filename_prefix() {
		return '';
	}

	/**
	 * Update styles.
	 *
	 * @return void
	 */
	protected function update_styles() {
		$styles_data = array(
			'filename'   => $this->filename,
			'fonts'      => $this->fonts,
			'stylesheet' => $this->styles,
		);

		$saved = get_option( '_magazine_blocks_blocks_css', array() );
		magazine_blocks_array_set( $saved, $this->id, $styles_data );

		update_option( '_magazine_blocks_blocks_css', $saved );
	}
}
