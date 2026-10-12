<?php
/**
 * Setting controller.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\RestApi\Controllers;

use MagazineBlocks\Setting;

defined( 'ABSPATH' ) || exit;

/**
 * Setting controller.
 */
class SettingsController extends \WP_REST_Controller {

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
	protected $rest_base = 'settings';

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
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema(),
				),
			)
		);
	}

	/**
	 * Create a single item.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		try {
			Setting::read();
			Setting::set_data( $request->get_params() );
			Setting::save();
			return rest_ensure_response( Setting::all() );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'rest_setting_create_error', $e->getMessage(), array( 'status' => 500 ) );
		}
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
	 * Check if a given request has access to update an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
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
		return new \WP_REST_Response( Setting::all(), 200 );
	}

	/**
	 * Get item schema
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'setting',
			'type'       => 'object',
			'properties' => array(
				'rated'            => array(
					'type' => 'boolean',
				),
				'blocks'           => array(
					'type'        => 'object',
					'description' => __( 'Blocks', 'magazine-blocks' ),
					'properties'  => array(
						'section'             => array(
							'description' => __( 'Section block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'heading'             => array(
							'description' => __( 'Heading block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'icon'                => array(
							'description' => __( 'Icon block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'image'               => array(
							'description' => __( 'Image block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'advertisement'       => array(
							'description' => __( 'Advertisement block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'banner-posts'        => array(
							'description' => __( 'Banner Posts block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'breadcrumbs'         => array(
							'description' => __( 'Breadcrumbs block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'category'            => array(
							'description' => __( 'Category block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'grid-module'         => array(
							'description' => __( 'Grid Module block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'post-image'          => array(
							'description' => __( 'Post Image block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'featured-posts'      => array(
							'description' => __( 'Featured Posts block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'featured-categories' => array(
							'description' => __( 'Split Feed block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'tab-post'            => array(
							'description' => __( 'Tab Post block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'paragraph'           => array(
							'description' => __( 'Paragraph block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'button'              => array(
							'description' => __( 'Button block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'post-title'          => array(
							'description' => __( 'Post Title block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'post-list'           => array(
							'description' => __( 'Post List block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'post-video'          => array(
							'description' => __( 'Post Video block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'category-list'       => array(
							'description' => __( 'Category List block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'news-ticker'         => array(
							'description' => __( 'News Ticker block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'date-weather'        => array(
							'description' => __( 'Date & Weather block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'social-icons'        => array(
							'description' => __( 'Social Icons block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
						'slider'              => array(
							'description' => __( 'Slider block', 'magazine-blocks' ),
							'type'        => 'boolean',
						),
					),
				),
				'editor'           => array(
					'type'        => 'object',
					'description' => __( 'Editor Options', 'magazine-blocks' ),
					'properties'  => array(
						'section-width'          => array(
							'type'        => 'integer',
							'description' => __( 'Default section max width', 'magazine-blocks' ),
						),
						'editor-blocks-spacing'  => array(
							'type'        => 'integer',
							'description' => __( 'Spacing between blocks in the block editor', 'magazine-blocks' ),
						),
						'design-library'         => array(
							'type'        => 'boolean',
							'description' => __( 'Collection of pre-made blocks', 'magazine-blocks' ),
						),
						'responsive-breakpoints' => array(
							'type'        => 'object',
							'description' => __( 'Responsive breakpoints', 'magazine-blocks' ),
							'properties'  => array(
								'tablet' => array(
									'type'        => 'integer',
									'description' => __( 'Tablet breakpoint', 'magazine-blocks' ),
								),
								'mobile' => array(
									'type'        => 'integer',
									'description' => __( 'Mobile breakpoint', 'magazine-blocks' ),
								),
							),
						),
						'copy-paste-styles'      => array(
							'type'        => 'boolean',
							'description' => __( 'Copy paste style for blocks', 'magazine-blocks' ),
						),
						'auto-collapse-panels'   => array(
							'type'        => 'boolean',
							'description' => __( 'Panels behavior similar to accordion. Open one at a time', 'magazine-blocks' ),
						),
					),
				),
				'performance'      => array(
					'type'        => 'object',
					'description' => __( 'Performance', 'magazine-blocks' ),
					'properties'  => array(
						'local-google-fonts'        => array(
							'type'        => 'boolean',
							'description' => __( 'Load google fonts locally', 'magazine-blocks' ),
						),
						'preload-local-fonts'       => array(
							'type'        => 'boolean',
							'description' => __( 'Preload local fonts', 'magazine-blocks' ),
						),
						'allow-only-selected-fonts' => array(
							'type'        => 'boolean',
							'description' => __( 'Allow only selected fonts', 'magazine-blocks' ),
						),

						'allowed-fonts'             => array(
							'type'        => 'array',
							'description' => __( 'Allowed fonts', 'magazine-blocks' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'           => array(
										'type' => 'string',
									),
									'category'     => array(
										'type' => 'string',
									),
									'defSubset'    => array(
										'type' => 'string',
									),
									'family'       => array(
										'type' => 'string',
									),
									'label'        => array(
										'type' => 'string',
									),
									'value'        => array(
										'type' => 'string',
									),
									'lastModified' => array(
										'type' => 'string',
									),
									'popularity'   => array(
										'type' => 'number',
									),
									'version'      => array(
										'type' => 'string',
									),
									'subsets'      => array(
										'type'  => 'array',
										'items' => array(
											'type' => 'string',
										),
									),
									'variants'     => array(
										'type'  => 'array',
										'items' => array(
											'type' => 'string',
										),
									),
								),
							),
						),
					),
				),
				'asset-generation' => array(
					'type'        => 'object',
					'description' => __( 'Asset generation', 'magazine-blocks' ),
					'properties'  => array(
						'external-file' => array(
							'type'        => 'boolean',
							'description' => __( 'File generation', 'magazine-blocks' ),
						),
					),
				),
				'version-control'  => array(
					'type'        => 'object',
					'description' => __( 'Version control', 'magazine-blocks' ),
					'properties'  => array(
						'beta-tester' => array(
							'type'        => 'boolean',
							'description' => __( 'Beta tester', 'magazine-blocks' ),
						),
					),
				),
				'integrations'     => array(
					'type'        => 'object',
					'description' => __( 'Third party integrations', 'magazine-blocks' ),
					'properties'  => array(
						'dateWeatherApiKey'    => array(
							'type'        => 'string',
							'description' => __( 'Newsticker api key', 'magazine-blocks' ),
						),
						'dateWeatherZipCode'   => array(
							'type'        => 'string',
							'description' => __( 'Newsticker api key', 'magazine-blocks' ),
						),
						'dateWeatherLatitude'  => array(
							'type'        => 'string',
							'description' => __( 'Latitude', 'magazine-blocks' ),
						),
						'dateWeatherLongitude' => array(
							'type'        => 'string',
							'description' => __( 'Longitude', 'magazine-blocks' ),
						),
						'dateWeatherUnit'      => array(
							'type'        => 'string',
							'description' => __( 'Unit', 'magazine-blocks' ),
						),
					),
				),
				'maintenance-mode' => array(
					'type'        => 'object',
					'description' => __( 'Maintenance mode', 'magazine-blocks' ),
					'properties'  => array(
						'maintenance-mode' => array(
							'type'        => 'boolean',
							'description' => __( 'Enable or disable maintenance mode', 'magazine-blocks' ),
						),
						'maintenance-page' => array(
							'oneOf' => array(
								array(
									'type'        => 'object',
									'description' => __( 'Maintenance mode page data.', 'magazine-blocks' ),
									'properties'  => array(
										'id'    => array(
											'type' => 'number',
										),
										'title' => array(
											'type' => 'string',
										),
									),
								),
								array(
									'type' => 'null',
								),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
