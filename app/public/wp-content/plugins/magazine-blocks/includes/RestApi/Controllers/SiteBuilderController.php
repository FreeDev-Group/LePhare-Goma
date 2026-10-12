<?php

/**
 * Magazine Blocks Site Builder Controller.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\RestApi\Controllers;

defined( 'ABSPATH' ) || exit;
/**
 * SiteBuilder controller.
 */
class SiteBuilderController extends \WP_REST_Controller {

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
	protected $rest_base = 'site-builder';

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
					'args'                => array(
						'refresh' => array(
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
							'required'          => false,
						),
						'type'    => array(
							'default'  => 'all',
							'required' => false,
						),
					),
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
	 * Get all Site Builder templates.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {

		$refresh = (bool) $request->get_param( 'refresh' );

		$transient_key = 'magazine_blocks_builder';

		// Get cached templates if refresh is not requested
		if ( ! $refresh ) {
			$cached_templates = get_transient( $transient_key );
			if ( false !== $cached_templates ) {
				return $cached_templates;
			}
		}

		// If no cache or refresh requested, fetch from remote API
		$api_url =
			'https://blocks.themegrilldemos.com/wp-json/blockart-library/v1/mzb/builder';

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 60,
			)
		);

		// Check for errors
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new \WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					esc_html__( 'Error fetching templates. Response code: %d', 'magazine-blocks' ),
					$response_code
				),
				array( 'status' => $response_code )
			);
		}

		$body      = wp_remote_retrieve_body( $response );
		$templates = json_decode( $body, true );

		if ( null === $templates && JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_Error(
				'json_parse_error',
				esc_html__( 'Error parsing template data.', 'magazine-blocks' ),
				array( 'status' => 500 )
			);
		}

		// Cache the templates for future use (cache for 12 hours)
		set_transient( $transient_key, $templates, 12 * HOUR_IN_SECONDS );

		return rest_ensure_response( $templates );
	}
}
