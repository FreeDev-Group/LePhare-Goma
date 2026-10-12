<?php
/**
 * Register and enqueue scripts for plugin.
 *
 * @since 1.0.0
 * @package Magazine Blocks
 */

namespace MagazineBlocks;

defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Traits\Singleton;
use JsonMachine\Items;
use MagazineBlocks\Services\OpenWeather;

/**
 * Register and enqueue scripts for plugin.
 *
 * @since 1.0.0
 */
class ScriptStyle {

	use Singleton;

	/**
	 * Scripts.
	 *
	 * @var array
	 */
	private $scripts = array();

	/**
	 * Styles.
	 *
	 * @var array
	 */
	private $styles = array();

	/**
	 * Localized scripts.
	 *
	 * @var array
	 */
	private $localized_scripts = array();

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'after_wp_init' ) );
		add_action( 'init', array( $this, 'register_scripts_styles' ), 11 );
		add_filter(
			'wp_handle_upload',
			function ( $upload ) {
				delete_transient( '_magazine_blocks_media_items' );
				return $upload;
			}
		);
		add_action(
			'wp_head',
			function () {
				printf( '<script>window._MAGAZINE_BLOCKS_WEBPACK_PUBLIC_PATH_ = "%s"</script>', esc_url( MAGAZINE_BLOCKS_DIST_DIR_URL . '/' ) );
			}
		);
		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_block_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'localize_admin_scripts' ) );
	}

	/**
	 * Get asset url.
	 *
	 * @param string  $filename Asset filename.
	 * @param boolean $dev Has dev url.
	 * @return string
	 */
	private function get_asset_url( $filename, $dev = true ) {
		$path = plugins_url( 'dist/', MAGAZINE_BLOCKS_PLUGIN_FILE );

		if ( $dev && magazine_blocks_is_development() ) {
			$path = 'http://localhost:5432/dist/';
		}

		return $path . $filename;
	}

	/**
	 * After WP init.
	 *
	 * @return void
	 */
	public function after_wp_init() {
		$blocks_asset    = $this->get_asset_file( 'blocks' );
		$dashboard_asset = $this->get_asset_file( 'dashboard' );
		$is_wp62         = version_compare( get_bloginfo( 'version' ), '6.2', '>=' );

		$this->scripts = array(
			'blocks'          => array(
				'src'     => $this->get_asset_url( $is_wp62 ? 'blocks.js' : 'blocks-17.js' ),
				'deps'    => $blocks_asset['dependencies'],
				'version' => $blocks_asset['version'],
				'i18n'    => true,
			),
			'admin'           => array(
				'src'     => $this->get_asset_url( 'dashboard.js' ),
				'deps'    => $dashboard_asset['dependencies'],
				'version' => $dashboard_asset['version'],
				'i18n'    => true,
			),
			'frontend-utils'  => array(
				'src'     => $this->get_asset_url( 'frontend-utils.js', false ),
				'deps'    => array(),
				'version' => MAGAZINE_BLOCKS_VERSION,
			),
			'news-ticker'     => array(
				'src'      => MAGAZINE_BLOCKS_PLUGIN_DIR_URL . 'assets/js/news-ticker.js',
				'deps'     => array( 'jquery' ),
				'version'  => MAGAZINE_BLOCKS_VERSION,
				// Signals Pro's `magazine_blocks_scripts` filter to keep loading this script from Free, not swap in its own copy.
				'override' => false,
			),
		);

		foreach ( array(
			'slider',
			'news-ticker',
			'tab-post',
			'modal',
		) as $view_script ) {
			$this->scripts[ "frontend-$view_script" ] = array(
				'src'     => $this->get_asset_url( "$view_script.js", false ),
				'deps'    => array_merge( 'slider' === $view_script ? array( 'wp-dom-ready' ) : ( 'news-ticker' === $view_script ? array( 'jquery' ) : array() ), array( 'magazine-blocks-frontend-utils' ) ),
				'version' => MAGAZINE_BLOCKS_VERSION,
			);
		}

		$this->styles = array(
			'blocks'        => array(
				'src'     => $this->get_asset_url( 'style-blocks.css', false ),
				'version' => $blocks_asset['version'],
				'deps'    => array(),
			),
			'blocks-editor' => array(
				'src'     => $this->get_asset_url( 'blocks.css', false ),
				'version' => $blocks_asset['version'],
				'deps'    => array(),
			),
		);

		$this->scripts = apply_filters( 'magazine_blocks_scripts', $this->scripts );
		$this->styles  = apply_filters( 'magazine_blocks_scripts', $this->styles );
	}

	/**
	 * Get all media items.
	 *
	 * @return array
	 */
	private function get_media_items() {
		$media_items = get_transient( '_magazine_blocks_media_items' );
		if ( empty( $media_items ) ) {
			$media_items = array_map(
				function ( $item ) {
					$item                  = (array) $item;
					$item['media_details'] = wp_get_attachment_metadata( $item['ID'] );
					$item['alt_text']      = get_post_meta( $item['ID'], '_wp_attachment_image_alt', true );
					$item['mime_type']     = $item['post_mime_type'];
					$item['source_url']    = wp_get_attachment_url( $item['ID'] );
					return $item;
				},
				get_posts(
					array(
						'post_type'      => 'attachment',
						'post_status'    => 'inherit',
						'posts_per_page' => -1,
						'orderby'        => 'title',
						'order'          => 'ASC',
					)
				)
			);
			set_transient( '_magazine_blocks_media_items', $media_items, DAY_IN_SECONDS );
		}
		return $media_items;
	}

	/**
	 * Register scripts.
	 *
	 * @return void
	 */
	public function register_scripts() {
		foreach ( $this->scripts as $handle => $script ) {
			if ( empty( $script['callback'] ) ) {
				wp_register_script( "magazine-blocks-$handle", $script['src'], $script['deps'], $script['version'], true );
			} elseif ( is_callable( $script['callback'] ) && call_user_func_array( $script['callback'], array() ) ) {
				wp_register_script( "magazine-blocks-$handle", $script['src'], $script['deps'], $script['version'], true );
			}
			if ( isset( $script['i18n'] ) && $script['i18n'] ) {
				wp_set_script_translations( "magazine-blocks-$handle", 'magazine-blocks', MAGAZINE_BLOCKS_LANGUAGES );
			}
		}
	}

	/**
	 * Register styles.
	 *
	 * @return void
	 */
	public function register_styles() {
		foreach ( $this->styles as $handle => $style ) {
			wp_register_style( "magazine-blocks-$handle", $style['src'], $style['deps'], $style['version'] );
		}
	}

	/**
	 * Register scripts and styles for plugin.
	 *
	 * @since 1.0.0
	 */
	public function register_scripts_styles() {
		$this->register_scripts();
		$this->register_styles();
	}

	/**
	 * Get asset file
	 *
	 * @param string $prefix Filename prefix.
	 * @return array|mixed
	 */
	private function get_asset_file( string $prefix ) {
		$asset_file = dirname( MAGAZINE_BLOCKS_PLUGIN_FILE ) . "/dist/$prefix.asset.php";

		return file_exists( $asset_file )
			? include $asset_file
			: array(
				'dependencies' => array(),
				'version'      => MAGAZINE_BLOCKS_VERSION,
			);
	}

	/**
	 * Localize block scripts.
	 *
	 * @return void
	 */
	public function localize_admin_scripts() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}
		$installed_plugin_slugs = array_keys( get_plugins() );
		$allowed_plugin_slugs   = array(
			'everest-forms/everest-forms.php',
			'user-registration/user-registration.php',
			'learning-management-system/lms.php',
			'magazine-blocks/magazine-blocks.php',
		);

		$installed_theme_slugs = array_keys( wp_get_themes() );
		$current_theme         = get_stylesheet();

		$localized_scripts = apply_filters(
			'magazine_blocks_localize_admin_scripts',
			array(
				'name' => '_MAGAZINE_BLOCKS_DASHBOARD_',
				'data' => array(
					'version'         => MAGAZINE_BLOCKS_VERSION,
					'plugins'         => array_reduce(
						$allowed_plugin_slugs,
						function ( $acc, $curr ) use ( $installed_plugin_slugs ) {
							if ( in_array( $curr, $installed_plugin_slugs, true ) ) {
								if ( is_plugin_active( $curr ) ) {
									$acc[ $curr ] = 'active';
								} else {
									$acc[ $curr ] = 'inactive';
								}
							} else {
								$acc[ $curr ] = 'not-installed';
							}
							return $acc;
						},
						array()
					),
					'themes'          => array(
						'zakra'    => strpos( $current_theme, 'zakra' ) !== false ? 'active' : (
						in_array( 'zakra', $installed_theme_slugs, true ) ? 'inactive' : 'not-installed'
						),
						'colormag' => strpos( $current_theme, 'colormag' ) !== false || strpos( $current_theme, 'colormag-pro' ) !== false ? 'active' : (
						in_array( 'colormag', $installed_theme_slugs, true ) || in_array( 'colormag-pro', $installed_theme_slugs, true ) ? 'inactive' : 'not-installed'
						),
					),
					'adminUrl'        => admin_url(),
					'googleFonts'     => $this->get_google_fonts(),
					'templateEditUrl' => admin_url( 'post.php?post=%d&action=edit' ),
				),
			)
		);
		wp_localize_script( 'magazine-blocks-admin', $localized_scripts['name'], $localized_scripts['data'] );
	}

	/**
	 * Localize block scripts.
	 *
	 * @return void
	 */
	public function localize_block_scripts() {
		global $pagenow;

		$font_awesome_icons    = iterator_to_array( Items::fromFile( Icon::FONT_AWESOME_ICONS_PATH ) );
		$magazine_blocks_icons = iterator_to_array( Items::fromFile( Icon::MAGAZINE_BLOCKS_ICONS_PATH ) );
		$google_fonts          = Items::fromFile( MAGAZINE_BLOCKS_PLUGIN_DIR . '/assets/json/google-fonts.json' );
		$open_weather          = OpenWeather::init();

		$localized_scripts = apply_filters(
			'magazine_blocks_localize_block_scripts',
			array(
				'name' => '_MAGAZINE_BLOCKS_',
				'data' => array(
					'isNotPostEditor' => 'widgets.php' === $pagenow || 'customize.php' === $pagenow,
					'isWP59OrAbove'   => is_wp_version_compatible( '5.9' ),
					'apiStatus'       => $open_weather->is_api_connected(),
					'temperature'     => $open_weather->show_temp(),
					'weather'         => $open_weather->show_weather(),
					'location'        => $open_weather->show_location(),
					'apiKey'          => get_option( 'dateWeatherApiKey' ),
					'postalCode'      => get_option( 'dateWeatherZipCode' ),
					'latitude'        => get_option( 'dateWeatherLatitude' ),
					'longitude'       => get_option( 'dateWeatherLongitude' ),
					'unit'            => magazine_blocks_get_setting( 'integrations.dateWeatherUnit', 'imperial' ),
					'nonce'           => wp_create_nonce( '_magazine_blocks_nonce' ),
					'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
					'mediaItems'      => $this->get_media_items(),
					'configs'         => magazine_blocks_array_except(
						magazine_blocks_get_setting(),
						array(
							'asset-generation',
							'performance.local-google-fonts',
							'performance.preload-local-fonts',
							'editor.responsive-breakpoints',
							'global-styles',
						)
					) + array(
						'global-styles' => json_decode( magazine_blocks_get_setting( 'global-styles' ) ),
					),
					'googleFonts'     => iterator_to_array( $google_fonts ),
					'icons'           => array(
						'font-awesome'    => array_values( ( $font_awesome_icons ) ),
						'magazine-blocks' => array_values( $magazine_blocks_icons ),
						'all'             => array_merge( ( $font_awesome_icons ), ( $magazine_blocks_icons ) ),
					),
				),
			)
		);
		wp_localize_script( 'magazine-blocks-blocks', $localized_scripts['name'], $localized_scripts['data'] );
	}

	/**
	 * Get google fonts.
	 *
	 * @return array
	 */
	protected function get_google_fonts() {
		$google_fonts_json = MAGAZINE_BLOCKS_PLUGIN_DIR . '/assets/json/google-fonts.json';
		if ( ! file_exists( $google_fonts_json ) ) {
			return array();
		}

		ob_start();
		include $google_fonts_json;
		$google_fonts = json_decode( ob_get_clean(), true );

		return $google_fonts;
	}
}
