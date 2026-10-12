<?php
/**
 * Tracking controller.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\RestApi\Controllers;

use MagazineBlocks\Analytics\UsageTracking;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for the anonymous usage-tracking opt-in toggle.
 *
 * Deliberately kept independent of `SettingsController`/`Setting::save()`: the SDK's
 * own consent notice writes directly to the same raw option this reads/writes, so
 * routing the toggle through the generic settings blob would risk an unrelated
 * settings save silently reverting a decision made via that notice, or vice versa.
 *
 * @since 1.8.6
 */
class TrackingController extends \WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'magazine-blocks/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'tracking';

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.8.6
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
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'enabled' => array(
							'required'          => true,
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to read/write the tracking flag.
	 *
	 * @since 1.8.6
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return true|\WP_Error
	 */
	public function permissions_check( $request ) {
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
	 * Returns the current opt-in state.
	 *
	 * @since 1.8.6
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function get_item( $request ) {
		return new \WP_REST_Response( array( 'enabled' => UsageTracking::is_opted_in() ), 200 );
	}

	/**
	 * Updates the opt-in state.
	 *
	 * @since 1.8.6
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function update_item( $request ) {
		update_option( UsageTracking::OPT_IN_OPTION, $request->get_param( 'enabled' ) ? 'yes' : 'no' );
		return new \WP_REST_Response( array( 'enabled' => UsageTracking::is_opted_in() ), 200 );
	}
}
