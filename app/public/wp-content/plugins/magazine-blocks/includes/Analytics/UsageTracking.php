<?php
/**
 * ThemeGrill SDK integration for opt-in anonymous usage tracking.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\Analytics;

defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Setting;
use MagazineBlocks\Traits\Singleton;

/**
 * Registers Magazine Blocks with the shared themegrill/themegrill-sdk Logger module
 * and supplies its data payload.
 *
 * Filter/option names here are verified against the SDK's own source
 * (github.com/themegrill/themegrill-sdk, v1.0.4: Product.php, Modules/Logger.php),
 * not assumed: `Product::get_slug()` (the plugin folder name, hyphenated) is used only
 * by Logger's own module-load gate; everywhere else (the opt-in option, the cron hook,
 * the data filter, the license filter) uses `Product::get_key()`, the same string
 * lowercased with hyphens turned to underscores. For this plugin that's
 * `magazine-blocks` (slug) vs `magazine_blocks` (key) respectively.
 *
 * @since 1.8.6
 */
class UsageTracking {

	use Singleton;

	/**
	 * WP option the SDK's Logger module reads/writes as this product's opt-in flag.
	 * Deliberately the single source of truth for consent — not mirrored into the
	 * plugin's own `_magazine_blocks_settings` blob, since the SDK's built-in consent
	 * notice writes here directly and a second copy would risk drifting out of sync.
	 *
	 * @since 1.8.6
	 */
	const OPT_IN_OPTION = 'magazine_blocks_logger_flag';

	/**
	 * Registers SDK integration hooks.
	 *
	 * The `themegrill_sdk_products` filter is added here rather than on any later
	 * hook because the SDK reads it from its own `init`-hooked bootstrap, so
	 * registration has to be in place before `init` fires. The opt-in default is
	 * force-set on every bootstrap, not just on activation, since an existing install
	 * that simply updates to this version never re-fires `register_activation_hook`.
	 *
	 * @since 1.8.6
	 */
	protected function __construct() {
		add_filter( 'themegrill_sdk_products', array( $this, 'register_product' ) );
		add_filter( 'themeGrill_sdk_registered_notifications', array( $this, 'bridge_notification_registration' ) );
		add_filter( 'magazine_blocks_logger_data', array( $this, 'build_payload' ) );
		add_filter( 'magazine_blocks_get_rest_api_controllers', array( $this, 'register_rest_controller' ) );

		register_activation_hook( MAGAZINE_BLOCKS_PLUGIN_FILE, array( __CLASS__, 'force_opt_out_default' ) );
		self::force_opt_out_default();
	}

	/**
	 * Registers this plugin as a ThemeGrill SDK product.
	 *
	 * @since 1.8.6
	 *
	 * @param string[] $products Already-registered product basefiles.
	 * @return string[]
	 */
	public function register_product( $products ) {
		$products[] = MAGAZINE_BLOCKS_PLUGIN_FILE;
		return $products;
	}

	/**
	 * Bridges a casing bug present in themegrill/themegrill-sdk v1.0.4: the Logger
	 * module registers its consent notice on the filter
	 * `themegrill_sdk_registered_notifications` (lowercase "grill"), but the
	 * Notification module that actually renders notices reads from
	 * `themeGrill_sdk_registered_notifications` (capital "G"). Without this bridge the
	 * SDK's own opt-in notice silently never displays.
	 *
	 * @since 1.8.6
	 *
	 * @param array $notifications Notifications already registered under the
	 *                             mis-cased hook.
	 * @return array
	 */
	public function bridge_notification_registration( $notifications ) {
		return apply_filters( 'themegrill_sdk_registered_notifications', $notifications );
	}

	/**
	 * Adds the tracking opt-in REST controller via the plugin's existing controller
	 * filter, so `RestApi.php` itself doesn't need editing.
	 *
	 * @since 1.8.6
	 *
	 * @param string[] $controllers Registered REST controller class names.
	 * @return string[]
	 */
	public function register_rest_controller( $controllers ) {
		$controllers[] = 'MagazineBlocks\RestApi\Controllers\TrackingController';
		return $controllers;
	}

	/**
	 * Forces the opt-in flag to `no` on activation.
	 *
	 * `Logger::is_logger_active()` defaults this flag to `yes` if any other
	 * SDK-registered product on the same site requires a license — a cross-product
	 * default this free plugin can't rely on to stay opted out. `add_option()` is a
	 * no-op if the option already holds a value, so this can never clobber a
	 * site owner's existing choice.
	 *
	 * @since 1.8.6
	 *
	 * @return void
	 */
	public static function force_opt_out_default() {
		add_option( self::OPT_IN_OPTION, 'no' );
	}

	/**
	 * Whether the site owner has opted into anonymous usage tracking.
	 *
	 * @since 1.8.6
	 *
	 * @return bool
	 */
	public static function is_opted_in() {
		return 'yes' === get_option( self::OPT_IN_OPTION, 'no' );
	}

	/**
	 * Supplies the payload the SDK's Logger module sends to
	 * `api.themegrill.com/tracking/log` via the `magazine_blocks_logger_data` filter.
	 *
	 * @since 1.8.6
	 *
	 * @param array $data Existing payload data (empty on first call).
	 * @return array
	 */
	public function build_payload( $data ) {
		if ( ! self::is_opted_in() ) {
			return array();
		}

		global $wpdb;

		$data['php_version']       = PHP_VERSION;
		$data['mysql_version']     = $wpdb->db_version();
		$data['server_software']   = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		$data['is_premium']        = defined( 'MAGAZINE_BLOCKS_PRO_VERSION' );
		$data['blocks']            = $this->get_block_usage_for_payload();
		$data['modified_settings'] = $this->get_modified_setting_keys();

		return $data;
	}

	/**
	 * Returns the block-usage aggregate keyed by bare block slug (e.g. `heading`, not
	 * `magazine-blocks/heading`). The internal aggregate keeps the full namespaced
	 * name (safer against collisions), but the already-live `logs_magazine-blocks`
	 * Mongo collection was verified to expect bare slugs under `data.blocks`, so the
	 * prefix is stripped here, at the payload boundary, to match that established
	 * schema rather than changing it.
	 *
	 * @since 1.8.6
	 *
	 * @return array<string,int>
	 */
	private function get_block_usage_for_payload() {
		$blocks = array();

		foreach ( BlockUsageTracker::get_aggregate() as $block_name => $count ) {
			$slug            = 0 === strpos( $block_name, 'magazine-blocks/' ) ? substr( $block_name, strlen( 'magazine-blocks/' ) ) : $block_name;
			$blocks[ $slug ] = $count;
		}

		return $blocks;
	}

	/**
	 * Returns the dot-notated keys of settings that differ from their defaults.
	 * Reports which settings were touched, never their values, so this stays
	 * non-sensitive telemetry.
	 *
	 * @since 1.8.6
	 *
	 * @return string[]
	 */
	private function get_modified_setting_keys() {
		$current  = magazine_blocks_array_dot( Setting::all() );
		$defaults = magazine_blocks_array_dot( Setting::get_defaults() );
		$changed  = array();

		foreach ( $current as $key => $value ) {
			if ( ! array_key_exists( $key, $defaults ) || $defaults[ $key ] !== $value ) {
				$changed[] = $key;
			}
		}

		return $changed;
	}
}
