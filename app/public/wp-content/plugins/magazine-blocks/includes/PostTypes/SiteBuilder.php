<?php
/**
 * SiteBuilder post type.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\PostTypes;

/**
 * Registers the `mzb-builder-template` post type used by the Site Builder feature and keeps exactly one
 * published template active per site-wide slot (header, footer, front, single, archive, 404, search).
 */
class SiteBuilder {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'mzb-builder-template';

	/**
	 * Valid site-wide template slot types accepted by the `_mzb_template` meta key.
	 *
	 * @var string[]
	 */
	const VALID_TEMPLATE_TYPES = array( 'header', 'footer', 'front', 'single', 'archive', '404', 'search' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'added_post_meta', array( $this, 'meta_updated' ), 10, 4 );
		add_action( 'updated_post_meta', array( $this, 'meta_updated' ), 10, 4 );
	}

	/**
	 * Demote the previously active template for a slot when a published template's slot is saved.
	 *
	 * @param int      $post_id Post ID being saved.
	 * @param \WP_Post $post    Post object being saved.
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( $post->post_type !== $this->post_type || 'publish' !== $post->post_status ) {
			return;
		}

		$this->demote_other_templates( $post_id, get_post_meta( $post_id, '_mzb_template', true ) );
	}

	/**
	 * Demote the previously active template for a slot when the `_mzb_template` meta is saved.
	 *
	 * REST requests that create and publish a template in a single call save its post meta after
	 * `save_post` has already fired, so the slot type isn't available yet when save_post() runs. This
	 * hook catches that case by re-running the demotion once the meta itself is persisted.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return void
	 */
	public function meta_updated( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( '_mzb_template' !== $meta_key ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== $this->post_type || 'publish' !== $post->post_status ) {
			return;
		}

		$this->demote_other_templates( $post_id, $meta_value );
	}

	/**
	 * Draft every other published template occupying the given slot.
	 *
	 * @param int   $post_id Post ID to keep active.
	 * @param mixed $type    Template slot type.
	 * @return void
	 */
	protected function demote_other_templates( $post_id, $type ) {
		if ( empty( $type ) || ! in_array( $type, self::VALID_TEMPLATE_TYPES, true ) ) {
			return;
		}

		$query = new \WP_Query(
			array(
				'post_type'      => $this->post_type,
				'meta_query'     => array(
					array(
						'key'     => '_mzb_template',
						'value'   => $type,
						'compare' => '=',
					),
				),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post__not_in'   => array( $post_id ),
				'fields'         => 'ids',
			)
		);

		foreach ( $query->posts as $active_id ) {
			// Require edit rights on the template being demoted, not just on the newly published one.
			if ( ! current_user_can( 'edit_post', $active_id ) ) {
				continue;
			}
			wp_update_post(
				array(
					'ID'          => $active_id,
					'post_status' => 'draft',
				)
			);
		}
	}

	/**
	 * Sanitize a `_mzb_template` meta value to a known template slot slug.
	 *
	 * @param mixed $value Raw meta value.
	 * @return string
	 */
	public static function sanitize_template_type( $value ) {
		$value = sanitize_key( $value );
		return in_array( $value, self::VALID_TEMPLATE_TYPES, true ) ? $value : '';
	}

	/**
	 * Get post type.
	 *
	 * @return string
	 */
	protected function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Get post type args.
	 *
	 * Capabilities are restricted to Administrators because site-wide templates affect every visitor. Only the
	 * plural keys are overridden; overriding the singular edit_post/read_post/delete_post keys with an existing
	 * primitive would corrupt WordPress's global meta-cap map and break that primitive for every post type.
	 *
	 * @return array
	 */
	protected function get_post_type_args() {
		$labels = apply_filters(
			"magazine_blocks_{$this->post_type}_labels",
			array(
				'name'               => __( 'Template', 'magazine-blocks' ),
				'singular_name'      => __( 'Template', 'magazine-blocks' ),
				'add_new'            => __( 'Add new Template', 'magazine-blocks' ),
				'add_new_item'       => __( 'Add new Template', 'magazine-blocks' ),
				'edit_item'          => __( 'Edit Template', 'magazine-blocks' ),
				'new_item'           => __( 'New Template', 'magazine-blocks' ),
				'view_item'          => __( 'View Template', 'magazine-blocks' ),
				'search_items'       => __( 'Search Template', 'magazine-blocks' ),
				'not_found'          => __( 'No Template found', 'magazine-blocks' ),
				'not_found_in_trash' => __( 'No Template found in Trash', 'magazine-blocks' ),
				'parent_item_colon'  => '',
			)
		);
		return array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => false,
			'has_archive'        => false,
			'hierarchical'       => false,
			'map_meta_cap'       => true,
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'edit_private_posts'     => 'manage_options',
				'edit_published_posts'   => 'manage_options',
				'publish_posts'          => 'manage_options',
				'read_private_posts'     => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'delete_private_posts'   => 'manage_options',
				'delete_published_posts' => 'manage_options',
			),
			'supports'           => array(
				'title',
				'editor',
				'custom-fields',
				'comments',
				'trackbacks',
				'author',
				'page-attributes',
			),
			'show_in_rest'       => true,
			'rest_namespace'     => 'magazine-blocks/v1',
			'rest_base'          => 'builder-templates',
		);
	}

	/**
	 * Register post type.
	 *
	 * @return void
	 */
	public function register() {
		$args = apply_filters( "magazine_blocks_{$this->get_post_type()}_post_type_args", $this->get_post_type_args() );
		register_post_type( $this->get_post_type(), $args );

		register_meta(
			'post',
			'_mzb_template',
			array(
				'object_subtype'    => $this->get_post_type(),
				'single'            => true,
				'type'              => 'string',
				'auth_callback'     => function () {
					return current_user_can( 'manage_options' );
				},
				'sanitize_callback' => array( __CLASS__, 'sanitize_template_type' ),
				'show_in_rest'      => true,
			)
		);
	}
}
