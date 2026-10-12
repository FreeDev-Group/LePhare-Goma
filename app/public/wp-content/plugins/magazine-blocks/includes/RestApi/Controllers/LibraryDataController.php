<?php
/**
 * Magazine Blocks Library Data Controller.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\RestApi\Controllers;

defined( 'ABSPATH' ) || exit;

/**
 * LibraryData controller.
 */
class LibraryDataController extends \WP_REST_Controller {

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
	protected $rest_base = 'library-data';

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
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You are not allowed to access this resource.', 'magazine-blocks' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Get library data.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$refresh = $request->get_param( 'refresh' ) ?? false;

		$refresh && delete_transient( '_magazine_blocks_library_data' );

		$data = get_transient( '_magazine_blocks_library_data' );

		if ( empty( $data ) ) {
			$response = wp_remote_get(
				'https://blocks.themegrilldemos.com/wp-json/blockart-library/v1/mzb/all',
				array(
					'timeout' => 120,
				)
			);

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return new \WP_Error(
					'mzb_library_data_fetch_failed',
					esc_html__( 'Unable to fetch the library data. Please try again later.', 'magazine-blocks' ),
					array( 'status' => 502 )
				);
			}

			$data = wp_remote_retrieve_body( $response );

			$decoded = json_decode( $data, true );

			if ( JSON_ERROR_NONE !== json_last_error() ) {
				return new \WP_Error(
					'mzb_library_data_invalid_response',
					esc_html__( 'The library data response was invalid. Please try again later.', 'magazine-blocks' ),
					array( 'status' => 502 )
				);
			}
			$data = $decoded;
			set_transient( '_magazine_blocks_library_data', $decoded, WEEK_IN_SECONDS );
		}
		$response = $this->prepare_items_for_response( $data, $request );
		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Prepare items for response.
	 *
	 * @param array            $data Raw library data keyed by post type slug.
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return array
	 */
	protected function prepare_items_for_response( $data, $request ) {
		$categorizer = function ( $data_to_categorize ) {
			$result = array();
			foreach ( $data_to_categorize as $item ) {
				if ( ! empty( $item['children'] ) ) {
					array_walk(
						$item['children'],
						function ( &$v ) use ( $item ) {
							$v['slug'] = $item['post_name'] . ':' . $v['post_name'];
						}
					);
				}
				$item['slug'] = $item['post_name'];

				// Items without a category would otherwise be dropped entirely, hiding them from every tab's total count.
				$categories = ! empty( $item['category'] )
					? $item['category']
					: array(
						array(
							'name' => esc_html__( 'Uncategorized', 'magazine-blocks' ),
							'slug' => 'uncategorized',
						),
					);

				foreach ( $categories as $cat ) {
					if ( isset( $result[ $cat['slug'] ] ) ) {
						++$result[ $cat['slug'] ]['count'];
						$result[ $cat['slug'] ]['items'][] = $item;
						continue;
					}
					$result[ $cat['slug'] ] = array(
						'name'  => $cat['name'],
						'slug'  => $cat['slug'],
						'count' => 1,
						'items' => array( $item ),
					);
				}
			}
			return $result;
		};

		return array(
			'categorized_sections'  => $categorizer( $data['mzb-sections'] ?? array() ),
			'categorized_templates' => $categorizer( $data['mzb-templates'] ?? array() ),
		);
	}
}
