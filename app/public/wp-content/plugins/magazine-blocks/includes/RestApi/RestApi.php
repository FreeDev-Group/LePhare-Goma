<?php
/**
 * Magazine Blocks Rest API.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\RestApi;

defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Traits\Singleton;

/**
 * Magazine Blocks Rest API.
 */
class RestApi {

	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'on_rest_api_init' ) );
	}

	/**
	 * On rest api init.
	 *
	 * @return void
	 */
	public function on_rest_api_init() {
		$this->register_rest_routes();
		$this->register_rest_fields();
		$this->allow_random_orderby();
		$this->register_orderby_support();
	}

	/**
	 * Allow `orderby=rand` on the core posts collection endpoint.
	 *
	 * WordPress core restricts the REST posts collection `orderby` param to a fixed
	 * enum that excludes `rand`, even though `WP_Query` itself supports it. Blocks
	 * exposing a "Random" order option (Banner Posts, Latest Posts, etc.) query
	 * `wp/v2/{post_type}` via `getEntityRecords()` in the editor, so without this the
	 * request 400s and the block renders empty. See MZB-725.
	 *
	 * @return void
	 */
	private function allow_random_orderby() {
		foreach ( magazine_blocks_get_post_types() as $post_type ) {
			add_filter(
				"rest_{$post_type->name}_collection_params",
				function ( $params ) {
					if ( isset( $params['orderby']['enum'] ) && ! in_array( 'rand', $params['orderby']['enum'], true ) ) {
						$params['orderby']['enum'][] = 'rand';
					}

					return $params;
				}
			);
		}
	}

	/**
	 * Allow the editor's post-query preview to order by popularity/comment count.
	 *
	 * Core's REST post controller restricts `orderby` to a fixed enum, so
	 * these values are rejected with a 400 error unless explicitly added.
	 *
	 * @return void
	 */
	private function register_orderby_support() {
		foreach ( magazine_blocks_get_post_types() as $post_type ) {
			add_filter( "rest_{$post_type->name}_collection_params", array( $this, 'add_orderby_options' ) );
			add_filter( "rest_{$post_type->name}_query", array( $this, 'map_popular_orderby_to_query' ), 10, 2 );
		}
	}

	/**
	 * Add popularity-related values to the allowed `orderby` enum.
	 *
	 * @param array $params Collection query params.
	 * @return array
	 */
	public function add_orderby_options( $params ) {
		if ( isset( $params['orderby']['enum'] ) ) {
			$params['orderby']['enum'][] = 'popular';
			$params['orderby']['enum'][] = 'comment_count';
		}
		return $params;
	}

	/**
	 * Translate the `popular` orderby request into real WP_Query args.
	 *
	 * @param array            $args    WP_Query args.
	 * @param \WP_REST_Request $request REST request.
	 * @return array
	 */
	public function map_popular_orderby_to_query( $args, $request ) {
		if ( 'popular' === $request->get_param( 'orderby' ) ) {
			$args = array_merge( $args, magazine_blocks_get_popular_order_args() );
		}
		return $args;
	}

	/**
	 * Register rest routes.
	 *
	 * @return void
	 */
	private function register_rest_routes() {
		$controllers = $this->get_controllers();

		foreach ( $controllers as $controller ) {
			$controller = new $controller();
			$controller->register_routes();
		}
	}

	/**
	 * Register rest fields.
	 *
	 * Additional fields for post types.
	 *
	 * @return void
	 */
	public function register_rest_fields() {
		$post_types = magazine_blocks_get_post_types();
		$fields     = array(
			'magazineBlocksPostFeaturedMedia'  => array( $this, 'get_post_featured_media' ),
			'magazineBlocksPostAuthor'         => array( $this, 'get_post_author' ),
			'magazineBlocksPostCommentsNumber' => array( $this, 'get_post_comments_number' ),
			'magazineBlocksPostExcerpt'        => array( $this, 'get_post_excerpt' ),
			'magazineBlocksPostCategories'     => array( $this, 'get_post_categories' ),
			'magazineBlocksPostViewCount'      => array( $this, 'get_post_view_count' ),
			'magazineBlocksPostReadTime'       => array( $this, 'get_post_read_time' ),
		);

		foreach ( $post_types as $post_type ) {
			foreach ( $fields as $id => $callback ) {
				register_rest_field(
					$post_type->name,
					$id,
					array(
						'get_callback'    => $callback,
						'update_callback' => null,
						'schema'          => null,
					)
				);
			}
		}
	}

	/**
	 * Get post view count.
	 *
	 * @param array $post Post data.
	 *
	 * @return array
	 */
	public function get_post_view_count( $post ) {
		$count = get_post_meta( $post['id'], '_mzb_post_view_count', true );
		return ! $count ? 0 : absint( $count );
	}

	/**
	 * Get post read time.
	 *
	 * @param array $post Post data.
	 *
	 * @return array
	 */
	public function get_post_read_time( $post ) {
		$words_per_minute = 200;
		$content          = get_post_field( 'post_content', $post['id'] );
		$word_count       = str_word_count( wp_strip_all_tags( $content ) );
		$read_time        = ceil( $word_count / $words_per_minute );
		return $read_time;
	}

	/**
	 * Get post featured media.
	 *
	 * @param array $post Post data.
	 *
	 * @return array
	 */
	public function get_post_featured_media( array $post ): array {
		return array_reduce(
			get_intermediate_image_sizes(),
			function ( $acc, $curr ) use ( $post ) {
				if ( isset( $post['featured_media'] ) ) {
					$acc[ $curr ] = wp_get_attachment_image_src( $post['featured_media'], $curr )[0] ?? false;
				}

				return $acc;
			},
			array()
		);
	}

	/**
	 * Get post author.
	 *
	 * @param array $post Post data.
	 *
	 * @return array
	 */
	public function get_post_author( array $post ): array {
		$author_id = isset( $post['author'] ) ? $post['author'] : 0;

		return array(
			'name'   => get_the_author_meta( 'display_name', $author_id ),
			'avatar' => get_avatar_url( $author_id ),
		);
	}

	/**
	 * Get post comment.
	 *
	 * @param array $post Post data.
	 *
	 * @return false|int
	 */
	public function get_post_comments_number( array $post ) {
		if ( post_password_required( $post['id'] ) || ! comments_open( $post['id'] ) ) {
			return false;
		}
		return get_comments_number( $post['id'] );
	}

	/**
	 * Get post excerpt.
	 *
	 * @param array $post Post data.
	 *
	 * @return string
	 */
	public function get_post_excerpt( array $post ): string {
		return get_the_excerpt( $post['id'] );
	}

	/**
	 * Get categories.
	 *
	 * @param array $post Post data.
	 * @return array
	 */
	public function get_post_categories( array $post ): array {
		$categories = get_the_terms( $post['id'], 'category' );

		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			return array();
		}

		return array_reduce(
			$categories,
			function ( $acc, $curr ) {
				$acc[] = $curr->name;
				return $acc;
			},
			array()
		);
	}

	/**
	 * Get controllers.
	 *
	 * @return array
	 */
	public function get_controllers() {
		return apply_filters(
			'magazine_blocks_get_rest_api_controllers',
			array(
				'MagazineBlocks\RestApi\Controllers\LibraryDataController',
				'MagazineBlocks\RestApi\Controllers\ImageImportController',
				'MagazineBlocks\RestApi\Controllers\RegenCSSController',
				'MagazineBlocks\RestApi\Controllers\SettingsController',
				'MagazineBlocks\RestApi\Controllers\ChangelogController',
				'MagazineBlocks\RestApi\Controllers\VersionControlController',
				'MagazineBlocks\RestApi\Controllers\SiteBuilderController',
			)
		);
	}
}
