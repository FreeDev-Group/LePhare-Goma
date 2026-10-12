<?php
/**
 * VersionControl controller.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\RestApi\Controllers;

defined( 'ABSPATH' ) || exit;

/**
 * VersionControl controller.
 */
class VersionControlController extends \WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string The namespace of this controller's route.
	 */
	protected $namespace = 'magazine-blocks/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @var string The base of this controller's route.
	 */
	protected $rest_base = 'version-control';

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return true|\WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You are not allowed to access this resource.', 'magazine-blocks' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Get items.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ): \WP_REST_Response {
		$versions = $this->get_versions();
		return new \WP_REST_Response( $versions, 200 );
	}

	/**
	 * Get versions.
	 *
	 * @return array
	 */
	protected function get_versions() {
		$versions = get_transient( '_magazine_blocks_versions[' . MAGAZINE_BLOCKS_VERSION . ']' );
		if ( ! empty( $versions ) ) {
			return $versions;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$plugins_api = plugins_api(
			'plugin_information',
			array(
				'slug' => 'magazine-blocks',
			)
		);

		if ( empty( $plugins_api->versions ) ) {
			return array();
		}

		uksort( $plugins_api->versions, 'version_compare' );
		$plugins_api->versions = array_reverse( $plugins_api->versions );
		$versions              = array();

		$index = 0;
		foreach ( $plugins_api->versions as $version => $download_link ) {
			if ( 10 <= $index ) {
				break;
			}
			if ( version_compare( $version, MAGAZINE_BLOCKS_VERSION, '>=' ) ) {
				continue;
			}
			++$index;
			$versions[] = array(
				'label' => strtolower( $version ),
				'value' => $download_link,
			);
		}
		set_transient( '_magazine_blocks_versions[' . MAGAZINE_BLOCKS_VERSION . ']', $versions, WEEK_IN_SECONDS );
		return $versions;
	}
}
