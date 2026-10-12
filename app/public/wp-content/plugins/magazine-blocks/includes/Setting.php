<?php
/**
 * Setting API class.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks;

defined( 'ABSPATH' ) || exit;

/**
 * Setting class.
 */
class Setting {

	/**
	 * Default data.
	 *
	 * @var array
	 */
	private static $data = array(
		'blocks'           => array(
			'section'             => true,
			'heading'             => true,
			'paragraph'           => true,
			'image'               => true,
			'icon'                => true,
			'social-icons'        => true,
			'advertisement'       => true,
			'date-weather'        => true,
			'news-ticker'         => true,
			'modal'               => true,
			'banner-posts'        => true,
			'grid-module'         => true,
			'featured-posts'      => true,
			'post-video'          => true,
			'tab-post'            => true,
			'post-list'           => true,
			'slider'              => true,
			'featured-categories' => true,
			'category-list'       => true,
			'archive'             => true,
			'post-content'        => true,
			'post-meta'           => true,
			'breadcrumbs'         => true,
			'category'            => true,
			'post-image'          => true,
			'post-title'          => true,
			'button'              => true,
			'chart'               => true,
			'latest-posts'        => true,
			'split-content'       => true,
			'story'               => true,
			'video-scrolling'     => true,
			'video-popup'         => true,
			'tabs'                => true,
		),
		'editor'           => array(
			'section-width'          => 1170,
			'editor-blocks-spacing'  => 24,
			'design-library'         => true,
			'responsive-breakpoints' => array(
				'tablet' => 992,
				'mobile' => 768,
			),
			'copy-paste-styles'      => true,
			'auto-collapse-panels'   => true,
		),
		'performance'      => array(
			'local-google-fonts'        => false,
			'preload-local-fonts'       => false,
			'allow-only-selected-fonts' => false,
			'allowed-fonts'             => array(),
		),
		'asset-generation' => array(
			'external-file' => false,
		),
		'version-control'  => array(
			'beta-tester' => false,
		),
		'maintenance-mode' => array(
			'mode'             => 'none',
			'maintenance-page' => null,
		),
		'integrations'     => array(
			'dateWeatherApiKey'  => '',
			'dateWeatherZipCode' => '',
		),
		'global-styles'    => '',
	);

	/**
	 * Immutable snapshot of default values, captured once on first read() before any
	 * filter/stored-option merging mutates $data. Backs get_defaults().
	 *
	 * @var array|null
	 */
	private static $defaults = null;

	/**
	 * Sanitize callbacks.
	 *
	 * @var array
	 */
	private static $sanitize_callbacks = array(
		'blocks'           => array(
			'section'             => 'magazine_blocks_string_to_bool',
			'heading'             => 'magazine_blocks_string_to_bool',
			'archive'             => 'magazine_blocks_string_to_bool',
			'icon'                => 'magazine_blocks_string_to_bool',
			'image'               => 'magazine_blocks_string_to_bool',
			'advertisement'       => 'magazine_blocks_string_to_bool',
			'banner-posts'        => 'magazine_blocks_string_to_bool',
			'grid-module'         => 'magazine_blocks_string_to_bool',
			'featured-posts'      => 'magazine_blocks_string_to_bool',
			'featured-categories' => 'magazine_blocks_string_to_bool',
			'tab-post'            => 'magazine_blocks_string_to_bool',
			'post-content'        => 'magazine_blocks_string_to_bool',
			'post-title'          => 'magazine_blocks_string_to_bool',
			'button'              => 'magazine_blocks_string_to_bool',
			'post-image'          => 'magazine_blocks_string_to_bool',
			'post-list'           => 'magazine_blocks_string_to_bool',
			'post-video'          => 'magazine_blocks_string_to_bool',
			'category-list'       => 'magazine_blocks_string_to_bool',
			'news-ticker'         => 'magazine_blocks_string_to_bool',
			'date-weather'        => 'magazine_blocks_string_to_bool',
			'social-icons'        => 'magazine_blocks_string_to_bool',
			'slider'              => 'magazine_blocks_string_to_bool',
			'modal'               => 'magazine_blocks_string_to_bool',
			'latest-posts'        => 'magazine_blocks_string_to_bool',
			'video-scrolling'     => 'magazine_blocks_string_to_bool',
			'video-popup'         => 'magazine_blocks_string_to_bool',
			'split-content'       => 'magazine_blocks_string_to_bool',
		),
		'editor'           => array(
			'section-width'          => 'absint',
			'editor-blocks-spacing'  => 'absint',
			'design-library'         => 'magazine_blocks_string_to_bool',
			'copy-paste-styles'      => 'magazine_blocks_string_to_bool',
			'auto-collapse-panels'   => 'magazine_blocks_string_to_bool',
			'responsive-breakpoints' => array(
				'tablet' => 'absint',
				'mobile' => 'absint',
			),
		),
		'performance'      => array(
			'local-google-fonts'        => 'magazine_blocks_string_to_bool',
			'preload-local-fonts'       => 'magazine_blocks_string_to_bool',
			'allow-only-selected-fonts' => 'magazine_blocks_string_to_bool',
		),
		'asset-generation' => array(
			'external-file' => 'magazine_blocks_string_to_bool',
		),
		'version-control'  => array(

			'beta-tester' => 'magazine_blocks_string_to_bool',
		),
		'integrations'     => array(
			'google-maps-embed-api-key' => 'sanitize_text_field',
		),
		'maintenance-mode' => array(
			'mode'             => 'sanitize_text_field',
			'maintenance-page' => array(
				'id'    => 'absint',
				'title' => 'sanitize_text_field',
			),
		),
	);

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public static function read() {
		self::set_default_global_styles();
		if ( null === self::$defaults ) {
			self::$defaults = self::$data;
		}
		self::$data = apply_filters( 'magazine_blocks_default_settings', self::$data );
		$settings   = get_option( '_magazine_blocks_settings', self::$data );
		self::$data = magazine_blocks_parse_args( $settings, self::$data );
		return self::$data;
	}

	/**
	 * Get all data.
	 *
	 * @return array
	 */
	public static function all() {
		return self::read();
	}

	/**
	 * Get the default settings values, unaffected by whatever is currently stored.
	 *
	 * Used to diff a site's live settings against defaults (e.g. for anonymized usage
	 * telemetry reporting which settings were touched, never their values).
	 *
	 * @return array
	 */
	public static function get_defaults() {
		self::read();
		return apply_filters( 'magazine_blocks_default_settings', self::$defaults );
	}

	/**
	 * Get setting.
	 *
	 * @param string $key Dot-notated setting key.
	 * @param mixed  $default_value Value to return if the key isn't set.
	 * @return mixed
	 */
	public static function get( $key, $default_value = null ) {
		self::read();
		return magazine_blocks_array_get( self::$data, $key, $default_value );
	}

	/**
	 * Set multiple data.
	 *
	 * @param array $data Nested settings data to merge in.
	 * @return void
	 */
	public static function set_data( $data ) {
		$data = magazine_blocks_array_dot( $data );
		array_walk(
			$data,
			function ( &$value, $key ) {
				$value = self::sanitize( $key, $value );
			}
		);
		$data       = magazine_blocks_array_undot( $data );
		self::$data = magazine_blocks_parse_args( $data, self::$data );
	}

	/**
	 * Set default global styles.
	 *
	 * @return void
	 */
	private static function set_default_global_styles() {
		$styles                      = array(
			'colors'           => array(
				array(
					'id'    => 'primary',
					'name'  => 'Primary',
					'value' => '#690aa0',
				),
				array(
					'id'    => 'secondary',
					'name'  => 'Secondary',
					'value' => '#54595F',
				),
				array(
					'id'    => 'text',
					'name'  => 'Text',
					'value' => '#7A7A7A',
				),
				array(
					'id'    => 'accent',
					'name'  => 'Accent',
					'value' => '#61CE70',
				),
			),
			'typographies'     => array(
				array(
					'id'    => 'primary',
					'name'  => 'Primary',
					'value' => array(
						'fontFamily' => 'Default',
						'weight'     => 600,
					),
				),
				array(
					'id'    => 'secondary',
					'name'  => 'Secondary',
					'value' => array(
						'fontFamily' => 'Default',
						'weight'     => 400,
					),
				),
				array(
					'id'    => 'text',
					'name'  => 'Text',
					'value' => array(
						'fontFamily' => 'Default',
						'weight'     => 600,
					),
				),
				array(
					'id'    => 'accent',
					'name'  => 'Accent',
					'value' => array(
						'fontFamily' => 'Default',
						'weight'     => 500,
					),
				),
			),
			'categories_color' => array(),
		);
		self::$data['global-styles'] = wp_json_encode( $styles );
	}

	/**
	 * Set setting data.
	 *
	 * @param string $key Key to set.
	 * @param mixed  $value Value to set.
	 * @return void
	 */
	public static function set( $key, $value ) {
		$value      = self::sanitize( $key, $value );
		self::$data = magazine_blocks_array_set( self::$data, $key, $value );
	}

	/**
	 * Sanitize value.
	 *
	 * @param string $key Key to sanitize.
	 * @param mixed  $value Value to sanitize.
	 * @return mixed Sanitized value.
	 */
	private static function sanitize( $key, $value ) {
		$sanitize_callback = magazine_blocks_array_get(
			(array) apply_filters(
				'magazine_blocks_setting_sanitize_callbacks',
				self::$sanitize_callbacks
			),
			$key
		);
		if ( is_callable( $sanitize_callback ) || ( is_string( $sanitize_callback ) && function_exists( $sanitize_callback ) ) ) {
			return call_user_func_array( $sanitize_callback, array( $value ) );
		}
		return $value;
	}

	/**
	 * Save setting data after sanitization.
	 *
	 * @return void
	 */
	public static function save() {
		self::watch_responsive_breakpoints();
		update_option( '_magazine_blocks_settings', self::$data );
	}


	/**
	 * Watch responsive breakpoints.
	 *
	 * @return void
	 */
	private static function watch_responsive_breakpoints() {
		$new_breakpoints = magazine_blocks_array_get( self::$data, 'editor.responsive-breakpoints', array() );
		$old_breakpoints = wp_parse_args(
			magazine_blocks_array_get( get_option( '_magazine_blocks_settings', array() ), 'editor.responsive-breakpoints', array() ),
			magazine_blocks_array_get( self::$data, 'editor.responsive-breakpoints', array() )
		);

		ksort( $new_breakpoints );
		ksort( $old_breakpoints );

		if ( $new_breakpoints !== $old_breakpoints ) {
			do_action( 'magazine_blocks_responsive_breakpoints_changed', $new_breakpoints, $old_breakpoints );
		}
	}
}
