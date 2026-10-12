<?php
/**
 * BlockRegistrar service.
 *
 * @since x.x.x
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Service class for registering blocks with metadata.
 *
 * @since x.x.x
 * @package Magazine Blocks
 */
class BlockRegistrar {

	/**
	 * Metadata validator service.
	 *
	 * @var MetadataValidator
	 * @since x.x.x
	 */
	private $validator;

	/**
	 * Constructor - Initialize the block registrar with validator service.
	 *
	 * @param MetadataValidator $validator The metadata validation service.
	 * @since x.x.x
	 */
	public function __construct( MetadataValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * Register a block.
	 *
	 * @param string        $block_name      The block name (e.g., 'my-block').
	 * @param callable|null $render_callback Optional. PHP callback function for
	 *                                      server-side rendering. If provided,
	 *                                      enables dynamic block rendering.
	 *
	 * @return bool True on successful registration, false on failure.
	 * @since x.x.x
	 */
	public function register( $block_name, $render_callback = null ) {
		$metadata = trailingslashit( $this->get_metadata_base_dir( $block_name ) ) . $block_name . '/block.json';

		if ( ! $this->validator->validate( $metadata ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html( sprintf( 'Invalid metadata for block %s', $block_name ) ),
				'1.0.0'
			);
			return false;
		}

		$args = array();

		if ( $render_callback ) {
			$args['render_callback'] = $render_callback;
		}

		return (bool) register_block_type_from_metadata( $metadata, $args );
	}

	/**
	 * Get the base directory path for block metadata files.
	 *
	 * @param string $block_name The block name.
	 *
	 * @return string The base directory path for block metadata files.
	 * @since x.x.x
	 */
	protected function get_metadata_base_dir( $block_name ) {
		return apply_filters( 'magazine_blocks_get_metadata_base_dir', wp_normalize_path( MAGAZINE_BLOCKS_PLUGIN_DIR . '/dist' ), $block_name );
	}
}
