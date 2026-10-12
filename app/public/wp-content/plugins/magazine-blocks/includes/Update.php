<?php
/**
 * Update class.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks;

defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Traits\Singleton;

/**
 * Update class.
 */
class Update {

	use Singleton;

	/**
	 * {@inheritDoc}
	 */
	protected function __construct() {
		add_action( 'magazine_blocks_version_update', array( $this, 'on_update' ), 10, 2 );
	}

	/**
	 * On update.
	 *
	 * @param string $new_version Current version.
	 * @param string $old_version Old version.
	 * d previous version.
	 *
	 * @return void
	 */
	public function on_update( string $new_version, string $old_version ) {
		if ( version_compare( $old_version, '1.3.3', '<' ) ) {
			$this->update_to_1_3_3();
		}
	}

	/**
	 * Update to version 2.0.0.
	 *
	 * @return void
	 */
	private function update_to_1_3_3() {
		global $wpdb;

		// Delete old meta keys.
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_magazine_blocks_css' OR meta_key = '_magazine_blocks_active'" );

		// Delete old options.
		delete_option( '_magazine_blocks_widget_css' );
	}
}
