<?php
/**
 * Activation class.
 *
 * @package Magazine Blocks
 * @since 1.0.0
 */

namespace MagazineBlocks;

defined( 'ABSPATH' ) || exit;

/**
 * Activation class.
 */
class Activation {

	/**
	 * Init.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		register_activation_hook( MAGAZINE_BLOCKS_PLUGIN_FILE, array( __CLASS__, 'on_activate' ) );
	}

	/**
	 * Callback for plugin activation hook.
	 *
	 * @since 1.0.0
	 */
	public static function on_activate() {}
}
