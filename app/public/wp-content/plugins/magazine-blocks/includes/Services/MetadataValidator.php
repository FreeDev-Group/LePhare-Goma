<?php
/**
 * MetadataValidator service.
 *
 * @since x.x.x
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Service class for validating block metadata files.
 *
 * @since x.x.x
 * @package Magazine Blocks
 */
class MetadataValidator {

	/**
	 * Validate block metadata file existence.
	 *
	 * @param string $metadata Full file path to the block.json metadata file.
	 *
	 * @return bool True if the metadata file exists and is accessible, false otherwise.
	 * @since x.x.x
	 */
	public function validate( $metadata ) {
		// Avoid stale stat cache causing false negatives for a file that does exist.
		clearstatcache( true, $metadata );
		return file_exists( $metadata );
	}
}
