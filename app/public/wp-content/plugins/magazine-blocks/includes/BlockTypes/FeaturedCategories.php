<?php

/**
 * Featured Posts block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\PostRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * Button block class.
 */
class FeaturedCategories extends Block {


	use PostRenderer;

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'featured-categories';

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {

		$attrs = $this->extract_attributes( $attributes );

		$cat_1_args = $this->generate_cat_args( $attrs );

		$cat_2_args = $this->generate_cat_args( $attrs, 2 );

		$params_to_remove = array( 'cat', 'tag_id', 'orderby', 'order', 'category__not_in', 'ignore_sticky_posts', 'paged', 'offset' );

		$extras['params_to_remove'] = $params_to_remove;

		$query_1 = $this->build_query( $cat_1_args, $extras );
		$query_2 = $this->build_query( $cat_2_args, $extras );

		if ( $query_1->have_posts() || $query_2->have_posts() ) {
			return $this->render_block( $query_1, $query_2, $attrs );
		}
		return '';
	}

	/**
	 * Generate query arguments for a category.
	 *
	 * @param array  $attrs Block attributes.
	 * @param string $cat   Category suffix (empty for first category, '2' for second).
	 * @return array WP_Query arguments.
	 */
	protected function generate_cat_args( $attrs, $cat = '' ) {
		$suffix = '' !== $cat ? '_' . $cat : '';

		$args = array(
			'post_type'           => $attrs[ 'post_type' . $suffix ],
			'posts_per_page'      => $attrs['post_count'],
			'post_status'         => 'publish',
			'cat'                 => $attrs[ 'category' . $suffix ],
			'tag_id'              => $attrs[ 'tag' . $suffix ],
			'ignore_sticky_posts' => 1,
			'category__not_in'    => $attrs[ 'excluded_category' . $suffix ],
			'offset'              => $attrs['offset'],
		);

		/**
		 * Filters the WP_Query args for the Featured Categories block.
		 *
		 * Allows overriding the post count above the editor's slider limit.
		 *
		 * @param array  $args  WP_Query arguments.
		 * @param array  $attrs Processed block attributes.
		 * @param string $cat   Category suffix (empty for first category, '2' for second).
		 */
		return apply_filters( 'magazine_blocks_featured_categories_query_args', $args, $attrs, $cat );
	}

	/**
	 * Build the WP_Query based on attributes.
	 *
	 * @param array $attrs Processed attributes.
	 * @param array $extras Extra params.
	 * @return WP_Query The built query.
	 */
	protected function build_query( $attrs, $extras = array() ) {
		return $this->query_builder->build_query( $attrs, $extras );
	}

	/**
	 * Render the block HTML.
	 *
	 * @param WP_Query $query_1 The posts query 1.
	 * @param WP_Query $query_2 The posts query 2.
	 * @param array    $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	protected function render_block( $query_1, $query_2, $attributes ) {

		$html = '';

		$html = sprintf(
			'<div class="mzb-featured-categories mzb-featured-categories-%s">',
			esc_attr( $attributes['client_id'] )
		);

		$html .= '<div class="mzb-category-posts">';

		// First category.
		$html .= $this->render_category_posts( $query_1, $attributes, 1 );

		// Second category.
		$html .= $this->render_category_posts( $query_2, $attributes, 2 );

		$html .= '</div></div>';

		wp_reset_postdata();
		return $html;
	}

	/**
	 * Extracting the attributes.
	 *
	 * @param array $attributes The original attributes.
	 */
	protected function extract_attributes( $attributes ) {
		$client_id  = magazine_blocks_array_get( $attributes, 'clientId', '' );
		$class_name = magazine_blocks_array_get( $attributes, 'className', '' );

		$heading_layout = magazine_blocks_array_get( $attributes, 'headingLayout', 'heading-layout-1' );

		// Get the specific advanced styles based on layout and heading layout.
		$heading_style = magazine_blocks_array_get( $attributes, magazine_get_heading_style_key( $heading_layout ), '' );

		return array(
			'client_id'                      => $client_id,
			'heading_layout'                 => $heading_layout,
			'heading_style'                  => $heading_style,
			'category'                       => magazine_blocks_array_get( $attributes, 'category', '' ),
			'category_2'                     => magazine_blocks_array_get( $attributes, 'category2', '' ),
			'tag'                            => magazine_blocks_array_get( $attributes, 'tag', '' ),
			'tag_2'                          => magazine_blocks_array_get( $attributes, 'tag2', '' ),
			'label'                          => magazine_blocks_array_get( $attributes, 'label', 'Latest' ),
			'label_2'                        => magazine_blocks_array_get( $attributes, 'label2', 'Latest' ),
			'excluded_category'              => magazine_blocks_array_get( $attributes, 'excludedCategory', '' ),
			'excluded_category_2'            => magazine_blocks_array_get( $attributes, 'excludedCategory2', '' ),
			'post_type'                      => magazine_blocks_array_get( $attributes, 'postType', 'post' ),
			'post_type_2'                    => magazine_blocks_array_get( $attributes, 'postType2', 'post' ),
			'post_count'                     => magazine_blocks_array_get( $attributes, 'postCount', '' ),
			'post_title_markup'              => magazine_blocks_sanitize_html_tag(
				magazine_blocks_array_get( $attributes, 'postTitleMarkup', 'h6' ),
				'h6'
			),
			'post_box_style'                 => magazine_blocks_array_get( $attributes, 'postBoxStyle', 'boxed' ),
			'hover_animation'                => magazine_blocks_array_get( $attributes, 'hoverAnimation' ),
			'enable_category'                => magazine_blocks_array_get( $attributes, 'enableCategory', '' ),
			'enable_comment'                 => magazine_blocks_array_get( $attributes, 'enableComment', '' ),
			'meta_position'                  => magazine_blocks_array_get( $attributes, 'metaPosition', '' ),
			'enable_author'                  => magazine_blocks_array_get( $attributes, 'enableAuthor', '' ),
			'enable_date'                    => magazine_blocks_array_get( $attributes, 'enableDate', '' ),
			'meta_separator'                 => magazine_blocks_array_get( $attributes, 'separatorType', 'none' ),
			'enable_icon'                    => magazine_blocks_array_get( $attributes, 'enableIcon', '' ),
			'enable_heading'                 => magazine_blocks_array_get( $attributes, 'enableHeading', 'true' ),
			'enable_excerpt'                 => magazine_blocks_array_get( $attributes, 'enableExcerpt', '' ),
			'excerpt_limit'                  => magazine_blocks_array_get( $attributes, 'excerptLimit', '' ),
			'excerpt_position'               => magazine_blocks_array_get( $attributes, 'excerptPosition', '' ),
			'enable_readmore'                => magazine_blocks_array_get( $attributes, 'enableReadMore', '' ),
			'read_more_text'                 => magazine_blocks_array_get( $attributes, 'readMoreText', '' ),
			'enable_pagination'              => magazine_blocks_array_get( $attributes, 'enablePagination', '' ),
			'enable_view_more'               => magazine_blocks_array_get( $attributes, 'enableViewMore', '' ),
			'view_button_position'           => magazine_blocks_array_get( $attributes, 'viewButtonPosition', '' ),
			'view_more_text'                 => magazine_blocks_array_get( $attributes, 'viewMoreText', '' ),
			'enable_view_more_icon'          => magazine_blocks_array_get( $attributes, 'enableViewMoreIcon', '' ),
			'view_more_icon'                 => magazine_blocks_array_get( $attributes, 'viewMoreIcon', '' ),
			'view_more_url'                  => magazine_blocks_array_get( $attributes, 'viewMoreLink', array() ),
			'offset'                         => magazine_blocks_array_get( $attributes, 'offset', 0 ),
			'paged'                          => isset( $_GET[ 'block_id_' . $client_id ] ) ? max( 1, intval( $_GET[ 'block_id_' . $client_id ] ) ) : 1, //phpcs:ignore.
			'enable_override_category_color' => get_theme_mod( 'colormag_enable_override_category_color', false ),
		);
	}

	/**
	 * Render posts for a specific category.
	 *
	 * @param \WP_Query $query      WP_Query object for the category.
	 * @param array     $attributes Block attributes.
	 * @param int       $category_num Category number (1 or 2).
	 * @return string Rendered HTML for the category posts.
	 */
	protected function render_category_posts( $query, $attributes, $category_num ) {
		$category_key = 1 === $category_num ? 'category' : 'category_2';
		$label_key    = 1 === $category_num ? 'label' : 'label_2';

		$category_id   = $attributes[ $category_key ];
		$category_name = get_cat_name( $category_id ) ? get_cat_name( $category_id ) : $attributes[ $label_key ];

		$html = sprintf(
			'<div class="mzb-category-%d-posts mzb-%s">',
			esc_attr( $category_num ),
			esc_attr( $attributes['post_box_style'] )
		);

		// Heading.
		$html .= $this->render_category_heading( $category_name, $attributes );

		// Posts.
		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= $this->render_post( get_the_ID(), $attributes );
		}

		// Bottom view more.
		if ( $attributes['enable_view_more'] && 'bottom' === $attributes['view_button_position'] ) {
			$html .= $this->render_view_more_link( $attributes, 'bottom' );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render category heading section.
	 *
	 * @param string $category_name Name of the category.
	 * @param array  $attributes    Block attributes.
	 * @return string Rendered heading HTML.
	 */
	protected function render_category_heading( $category_name, $attributes ) {
		$html = sprintf(
			'<div class="mzb-post-heading mzb-%s mzb-%s">',
			esc_attr( $attributes['heading_layout'] ),
			esc_attr( $attributes['heading_style'] )
		);

		if ( $attributes['enable_heading'] ) {
			$html .= sprintf( '<h2 class="mzb-heading-text">%s</h2>', esc_html( $category_name ) );
		}

		if ( $attributes['enable_view_more'] && 'top' === $attributes['view_button_position'] ) {
			$html .= $this->render_view_more_link( $attributes, 'top' );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render individual post.
	 *
	 * @param int   $post_id    Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Rendered post HTML.
	 */
	protected function render_post( $post_id, $attributes ) {
		$html = '<div class="mzb-post">';

		// Featured image.
		$html .= $this->render_featured_image( $post_id, $attributes['hover_animation'] );

		$html .= '<div class="mzb-post-content">';

		// Category and comments meta.
		if ( $attributes['enable_category'] || $attributes['enable_comment'] ) {
			$html .= '<div class="mzb-post-meta">';
			$html .= $attributes['enable_category'] ? $this->render_categories( $post_id, $attributes['enable_override_category_color'] ) : '';
			$html .= $attributes['enable_comment'] ? $this->render_comments( $post_id ) : '';
			$html .= '</div>';
		}

		// Top meta.
		if ( ( $attributes['enable_author'] || $attributes['enable_date'] ) && 'top' === $attributes['meta_position'] ) {
			$html .= $this->render_post_meta( $post_id, $attributes );
		}

		// Title.
		$html .= $this->render_post_title( $post_id, $attributes['post_title_markup'] );

		// Bottom meta.
		if ( ( $attributes['enable_author'] || $attributes['enable_date'] ) && 'bottom' === $attributes['meta_position'] ) {
			$html .= $this->render_post_meta( $post_id, $attributes );
		}

		// Excerpt and read more.
		if ( $attributes['enable_excerpt'] || $attributes['enable_readmore'] ) {
			$html .= $this->render_excerpt_and_read_more( $post_id, $attributes );
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Render post meta information (author and date).
	 *
	 * @param int   $post_id    Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Rendered meta HTML.
	 */
	protected function render_post_meta( $post_id, $attributes ) {
		$html = sprintf(
			'<div class="mzb-post-entry-meta mzb-meta-separator--%s">',
			esc_attr( $attributes['meta_separator'] )
		);

		$html .= $attributes['enable_author'] ? $this->render_author( $post_id, $attributes['enable_icon'] ) : '';
		$html .= $attributes['enable_date'] ? $this->render_date( $post_id, $attributes['enable_icon'] ) : '';

		$html .= '</div>';

		return $html;
	}
}
