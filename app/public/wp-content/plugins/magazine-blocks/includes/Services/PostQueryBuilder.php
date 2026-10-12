<?php
/**
 * PostQueryBuilder service.
 *
 * @since x.x.x
 * @package Magazine Blocks
 */

namespace MagazineBlocks\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Fluent builder for WordPress post queries.
 *
 * @since x.x.x
 * @package Magazine Blocks
 */
class PostQueryBuilder {

	/**
	 * Query arguments array.
	 *
	 * @var array
	 */
	private $args = array();

	/**
	 * Extra arguments array.
	 *
	 * @var array
	 */
	private $extra_args = array();

	/**
	 * The handler.
	 *
	 * @var TemplateContextHandler
	 */
	protected $template_handler;

	/**
	 * Template context handler constructor.
	 *
	 * @param TemplateContextHandler $template_handler The handler.
	 */
	public function __construct( TemplateContextHandler $template_handler ) {
		$this->template_handler = $template_handler;
	}

	/**
	 * Set post type to query.
	 *
	 * @param string|array $type Post type name(s).
	 * @return self
	 */
	public function set_post_type( $type ) {
		if ( is_string( $type ) || is_array( $type ) ) {
			$this->args['post_type'] = $type;
		}
		return $this;
	}

	/**
	 * Set number of posts to retrieve.
	 *
	 * @param int $number Posts per page (-1 for all).
	 * @return self
	 */
	public function set_posts_per_page( $number ) {
		// Handle -1 for all posts, otherwise use absint.
		$this->args['posts_per_page'] = ( -1 === $number ) ? -1 : absint( $number );
		return $this;
	}

	/**
	 * Filter posts by category ID.
	 *
	 * @param int $id Category ID.
	 * @return self
	 */
	public function set_category( $id ) {
		$this->args['cat'] = absint( $id );
		return $this;
	}

	/**
	 * Filter posts by tag ID.
	 *
	 * @param int $id Tag ID.
	 * @return self
	 */
	public function set_tag( $id ) {
		$this->args['tag_id'] = absint( $id );
		return $this;
	}

	/**
	 * Set order by parameter.
	 *
	 * @param string $orderby Field to order by.
	 * @return self
	 */
	public function set_order_by( $orderby ) {
		if ( 'popular' === $orderby ) {
			return $this->set_order_by_popular();
		}

		$valid_orderby = array( 'date', 'title', 'rand', 'comment_count', 'modified' );
		if ( in_array( $orderby, $valid_orderby, true ) ) {
			$this->args['orderby'] = $orderby;
		}
		return $this;
	}

	/**
	 * Order posts by view count, treating posts with no recorded views as zero.
	 *
	 * @return self
	 */
	private function set_order_by_popular() {
		$this->args = array_merge( $this->args, magazine_blocks_get_popular_order_args() );
		return $this;
	}

	/**
	 * Set order parameter.
	 *
	 * @param string $order Order direction (ASC/DESC).
	 * @return self
	 */
	public function set_order( $order = 'DESC' ) {
		$this->args['order'] = 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';
		return $this;
	}

	/**
	 * Set author parameter.
	 *
	 * @param int|string $author Author ID or 'all' for no filtering.
	 * @return self
	 */
	public function set_author( $author ) {
		if ( $author && 'all' !== $author ) {
			$this->args['author'] = is_numeric( $author ) ? absint( $author ) : $author;
		}
		return $this;
	}

	/**
	 * Set pagination parameters.
	 *
	 * @param int $paged Current page number.
	 * @return self
	 */
	public function set_pagination( $paged = 1 ) {
		$this->args['paged'] = max( 1, absint( $paged ) );
		return $this;
	}

	/**
	 * Set post offset.
	 *
	 * @param int $offset Post offset.
	 * @return self
	 */
	public function set_offset( $offset ) {
		if ( $offset > 0 ) {
			$this->args['offset'] = absint( $offset );
		}
		return $this;
	}

	/**
	 * Exclude categories.
	 *
	 * @param int|array $categories Category ID(s) to exclude.
	 * @return self
	 */
	public function exclude_categories( $categories ) {
		$this->args['category__not_in'] = (array) $categories;
		return $this;
	}

	/**
	 * Exclude posts having the given tags.
	 *
	 * @param array $tags Tag IDs to exclude.
	 * @return self
	 */
	public function exclude_tags( $tags ) {
		$this->args['tag__not_in'] = (array) $tags;
		return $this;
	}

	/**
	 * Build query from attributes.
	 *
	 * @param array $attrs Block attributes.
	 * @param array $extra_args The extra args.
	 * @return \WP_Query
	 */
	public function build_query( $attrs, $extra_args = array() ) {
		$this->args = array();

		$this->extra_args = $extra_args;

		if ( ! empty( $attrs['post_type'] ) ) {
			$this->set_post_type( $attrs['post_type'] );
		}

		if ( isset( $attrs['posts_per_page'] ) ) {
			$this->set_posts_per_page( $attrs['posts_per_page'] );
		}

		if ( ! empty( $attrs['cat'] ) ) {
			$this->set_category( $attrs['cat'] );
		}

		if ( ! empty( $attrs['tag_id'] ) ) {
			$this->set_tag( $attrs['tag_id'] );
		}

		if ( ! empty( $attrs['order'] ) ) {
			$this->set_order( $attrs['order'] );
		}

		if ( ! empty( $attrs['orderby'] ) ) {
			$this->set_order_by( $attrs['orderby'] );
		}

		if ( ! empty( $attrs['paged'] ) ) {
			$this->set_pagination( $attrs['paged'] );
		}

		if ( ! empty( $attrs['offset'] ) ) {
			$this->set_offset( $attrs['offset'] );
		}

		if ( ! empty( $attrs['category__not_in'] ) ) {
			$this->exclude_categories( $attrs['category__not_in'] );
		}

		if ( ! empty( $attrs['tag__not_in'] ) ) {
			$this->exclude_tags( $attrs['tag__not_in'] );
		}

		if ( ! empty( $attrs['author'] ) && 'all' !== $attrs['author'] ) {
			$this->set_author( $attrs['author'] );
		}

		return $this->build();
	}

	/**
	 * Build WP_Query with defaults and template context handling.
	 *
	 * @return \WP_Query
	 */
	public function build() {
		$defaults = array(
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
		);

		// Apply template context modifications.
		$args = $this->template_handler->modify_query_args(
			wp_parse_args( $this->args, $defaults ),
			$this->extra_args
		);
		return new \WP_Query( $args );
	}
}
