<?php
/**
 * Deactivation class.
 *
 * @package Magazine Blocks
 * @since 1.0.0
 */

namespace MagazineBlocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Traits\Singleton;

/**
 * Deactivation class.
 */
class Deactivation {

	use Singleton;

	/**
	 * Contructor.
	 */
	protected function __construct() {
		register_deactivation_hook( MAGAZINE_BLOCKS_PLUGIN_FILE, array( __CLASS__, 'on_deactivate' ) );
	}

	/**
	 * Callback for plugin deactivation hook.
	 *
	 * @since 1.0.0
	 */
	public static function on_deactivate() {}
}
