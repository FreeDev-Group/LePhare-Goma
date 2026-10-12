<?php
/**
 * Abstract styles.
 *
 * @package MagazineBlocks\Abstract.
 */

namespace MagazineBlocks\Abstracts;

/**
 * Abstract styles.
 */
abstract class Styles {

	protected $tablet_breakpoint = 992;
	protected $mobile_breakpoint = 768;
	protected $devices           = array(
		'desktop',
		'tablet',
		'mobile',
	);

	/**9
	 * Id
	 *
	 * @var string|number
	 */
	protected $id;

	/**
	 * Stylesheet filename.
	 *
	 * @var string Stylesheet filename.
	 */
	protected $filename;

	/**
	 * Generated styles.
	 *
	 * @var string Generated styles.
	 */
	protected $styles;

	/**
	 * Generated css data.
	 *
	 * @var array Generated css.
	 */
	protected $css = array();

	/**
	 * Used fonts.
	 *
	 * @var array Used fonts.
	 */
	protected $fonts = array();

	/**
	 * Force generate.
	 *
	 * @var boolean
	 */
	protected $force_generate = false;

	/**
	 * Constructor.
	 */
	public function __construct( $id ) {
		$this->id = $id;

		$this->maybe_generate();
	}

	/**
	 * Maybe generate.
	 *
	 * @return void
	 */
	protected function maybe_generate() {
		$saved = $this->get_saved_styles();

		if ( ! $this->force_generate && ! empty( $saved ) && ! magazine_blocks_is_preview() ) {
			$this->styles   = magazine_blocks_array_get( $saved, 'stylesheet', '' );
			$this->filename = magazine_blocks_array_get( $saved, 'filename', '' );
			$this->fonts    = magazine_blocks_array_get( $saved, 'fonts', array() );
			return;
		}

		try {
			$this->create_style_file();
			$this->generate();
			$this->make_styles();
			$this->update_styles();
			$this->write();
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Do nothing.
		}
	}

	protected function get_style_enqueue_handle() {
		return "magazine-blocks-blocks-css-$this->id";
	}

	protected function get_font_enqueue_handle() {
		return "magazine-blocks-blocks-css-$this->id";
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

		$inline = magazine_blocks_is_preview() || ! ( magazine_blocks_get_setting( 'asset-generation.external-file', false ) && file_exists( MAGAZINE_BLOCKS_UPLOAD_DIR . "/$this->filename" ) );

		if ( ! $inline ) {
			wp_enqueue_style(
				$this->get_style_enqueue_handle(),
				MAGAZINE_BLOCKS_UPLOAD_DIR_URL . "/$this->filename",
				array(),
				$version // Version is always false, on every build filename is different.
			);
			return;
		}

		wp_register_style( $this->get_style_enqueue_handle(), false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( $this->get_style_enqueue_handle() );
		wp_add_inline_style( $this->get_style_enqueue_handle(), $this->styles );
		unset( $this->styles );
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
	 * Make styles.
	 *
	 * @return void
	 */
	abstract protected function make_styles();

	/**
	 * Update styles.
	 *
	 * @return void
	 */
	abstract protected function update_styles();

	/**
	 * Get saved styles.
	 *
	 * @return void
	 */
	abstract protected function get_saved_styles();

	/**
	 * Generate
	 *
	 * @return void
	 */
	abstract protected function generate();

	/**
	 * Get id.
	 *
	 * @return int|string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get filename.
	 *
	 * @return string
	 */
	public function get_filename() {
		return $this->filename;
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
	 * Create style file.
	 *
	 * @return void
	 */
	protected function create_style_file() {
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

		$files    = $filesystem->dirlist( MAGAZINE_BLOCKS_UPLOAD_DIR );
		$filename = $this->get_filename_prefix() . $this->id . '-';

		if ( $files ) {
			foreach ( $files as $file ) {
				if ( false !== strpos( $file['name'], $filename ) ) {
					$filesystem->delete( MAGAZINE_BLOCKS_UPLOAD_DIR . '/' . $file['name'] );
				}
			}
		}

		$this->filename = $filename . time() . '.css';

		return $filesystem->touch( MAGAZINE_BLOCKS_UPLOAD_DIR . '/' . $this->filename );
	}

	/**
	 * Get filename prefix.
	 *
	 * @return void
	 */
	protected function get_filename_prefix() {
		return 'ba-style-';
	}

	/**
	 * Write styles to file.
	 *
	 * @return void
	 */
	protected function write() {
		$filesystem = magazine_blocks_get_filesystem();
		if ( $filesystem ) {
			$filesystem->put_contents( MAGAZINE_BLOCKS_UPLOAD_DIR . '/' . $this->filename, $this->styles );
		}
	}

	/**
	 * Get tablet breakpoint.
	 *
	 * @return string
	 */
	protected function get_tablet_breakpoint() {
		return magazine_blocks_get_setting( 'editor.responsive-breakpoints.tablet', $this->tablet_breakpoint ) . 'px';
	}

	/**
	 * Get mobile breakpoint.
	 *
	 * @return string
	 */
	protected function get_mobile_breakpoint() {
		return magazine_blocks_get_setting( 'editor.responsive-breakpoints.mobile', $this->mobile_breakpoint ) . 'px';
	}
}
