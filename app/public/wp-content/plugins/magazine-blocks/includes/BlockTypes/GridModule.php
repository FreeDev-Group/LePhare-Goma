<?php

/**
 * Grid Module block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\PostRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * Grid Module block class.
 */
class GridModule extends Block {

	use PostRenderer;

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'grid-module';

	/**
	 * Render the Grid Module block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {

		$attrs = $this->extract_attributes( $attributes );

		$query = $this->build_query( $attrs );

		if ( $query->have_posts() ) {
			return $this->render_block( $query, $attrs );
		}

		return '';
	}

	/**
	 * Extract and process block attributes.
	 *
	 * @param array $attributes Raw block attributes.
	 * @return array Processed attributes.
	 */
	protected function extract_attributes( $attributes ) {
		$client_id      = magazine_blocks_array_get( $attributes, 'clientId', '' );
		$class_name     = magazine_blocks_array_get( $attributes, 'className', '' );
		$layout         = magazine_blocks_array_get( $attributes, 'layout', '' );
		$heading_layout = magazine_blocks_array_get( $attributes, 'headingLayout', '' );

		// Get the specific advanced styles based on layout and heading layout.
		$advanced_style = magazine_blocks_array_get( $attributes, magazine_get_style_key( $layout ), '' );
		$heading_style  = magazine_blocks_array_get( $attributes, magazine_get_heading_style_key( $heading_layout ), '' );

		$enable_number_list = magazine_blocks_array_get( $attributes, 'enableNumberList', false );
		$number_style       = '';
		if ( true === $enable_number_list ) {
			$number_style = 'mzb-number-list';
		}

		$number_layout       = magazine_blocks_array_get( $attributes, 'numberLayout', 'default' );
		$number_layout_style = '';
		if ( 'default' === $number_layout || 'circle' === $number_layout || 'square' === $number_layout || 'zero' === $number_layout || 'dot' === $number_layout ) {
			$number_layout_style = $number_layout;
		}

		$number_position       = magazine_blocks_array_get( $attributes, 'numberPosition', 'with-image' );
		$number_position_style = '';
		if ( 'with-image' === $number_position || 'with-text' === $number_position ) {
			$number_position_style = $number_position;
		}

		return array(
			// General attributes.
			'client_id'                      => $client_id,
			'class_name'                     => $class_name,
			'layout'                         => $layout,
			'advanced_style'                 => $advanced_style,
			'heading_layout'                 => $heading_layout,
			'heading_style'                  => $heading_style,
			'column'                         => magazine_blocks_array_get( $attributes, 'column', '4' ),
			'size'                           => magazine_blocks_array_get( $attributes, 'size', '' ),
			'hide_on_desktop'                => magazine_blocks_array_get( $attributes, 'hideOnDesktop', '' ),

			// Query parameters.
			'category'                       => magazine_blocks_array_get( $attributes, 'category', '' ),
			'tag'                            => magazine_blocks_array_get( $attributes, 'tag', '' ),
			'excluded_category'              => magazine_blocks_array_get( $attributes, 'excludedCategory', '' ),
			'excluded_tag'                   => magazine_blocks_array_get( $attributes, 'excludedTag', '' ),
			'order_by'                       => magazine_blocks_array_get( $attributes, 'orderBy', '' ),
			'order_type'                     => magazine_blocks_array_get( $attributes, 'orderType', '' ),
			'author'                         => magazine_blocks_array_get( $attributes, 'authorName', '' ),
			'post_count'                     => magazine_blocks_array_get( $attributes, 'postCount', '' ),
			'post_type'                      => magazine_blocks_array_get( $attributes, 'postType', 'post' ),
			'offset'                         => magazine_blocks_array_get( $attributes, 'offset', 0 ),
			'paged'                          => isset( $_GET[ 'block_id_' . $client_id ] ) ? max( 1, intval( $_GET[ 'block_id_' . $client_id ] ) ) : 1, //phpcs:ignore.

			// Header Meta.
			'enable_category'                => magazine_blocks_array_get( $attributes, 'enableCategory', '' ),
			'enable_comment'                 => magazine_blocks_array_get( $attributes, 'enableComment', '' ),
			'category_position'              => magazine_blocks_array_get( $attributes, 'categoryPosition', '' ),
			'hover_animation'                => magazine_blocks_array_get( $attributes, 'hoverAnimation', '' ),

			// Heading.
			'enable_heading'                 => magazine_blocks_array_get( $attributes, 'enableHeading', '' ),
			'label'                          => magazine_blocks_array_get( $attributes, 'label', 'Explore More' ),

			// Post Box.
			'enable_post_box_border'         => magazine_blocks_array_get( $attributes, 'enablePostBoxBorder', 'true' ),

			// Post Title.
			'enable_post_title'              => magazine_blocks_array_get( $attributes, 'enablePostTitle', 'true' ),
			'enable_post_title_border'       => magazine_blocks_array_get( $attributes, 'enablePostTitleBorder', 'true' ),
			'tag_name'                       => magazine_blocks_array_get( $attributes, 'tagName', 'h6' ),

			// Meta.
			'meta_position'                  => magazine_blocks_array_get( $attributes, 'metaPosition', '' ),
			'enable_author'                  => magazine_blocks_array_get( $attributes, 'enableAuthor', '' ),
			'enable_date'                    => magazine_blocks_array_get( $attributes, 'enableDate', '' ),
			'enable_readtime'                => magazine_blocks_array_get( $attributes, 'enableReadTime', '' ),
			'enable_viewcount'               => magazine_blocks_array_get( $attributes, 'enableViewCount', '' ),
			'enable_icon'                    => magazine_blocks_array_get( $attributes, 'enableIcon', '' ),
			'meta_separator'                 => magazine_blocks_array_get( $attributes, 'separatorType', 'none' ),

			// Excerpt.
			'enable_excerpt'                 => magazine_blocks_array_get( $attributes, 'enableExcerpt', '' ),
			'excerpt_limit'                  => magazine_blocks_array_get( $attributes, 'excerptLimit', '' ),

			// ReadMore.
			'enable_readmore'                => magazine_blocks_array_get( $attributes, 'enableReadMore', '' ),
			'read_more_text'                 => magazine_blocks_array_get( $attributes, 'readMoreText', '' ),

			// View All.
			'enable_view_more'               => magazine_blocks_array_get( $attributes, 'enableViewMore', '' ),
			'view_more_text'                 => magazine_blocks_array_get( $attributes, 'viewMoreText', '' ),
			'view_button_position'           => magazine_blocks_array_get( $attributes, 'viewButtonPosition', '' ),
			'enable_view_more_icon'          => magazine_blocks_array_get( $attributes, 'enableViewMoreIcon', '' ),
			'view_more_icon'                 => magazine_blocks_array_get( $attributes, 'viewMoreIcon', '' ),
			'view_more_url'                  => magazine_blocks_array_get( $attributes, 'viewMoreLink', array() ),

			// Pagination.
			'enable_pagination'              => magazine_blocks_array_get( $attributes, 'enablePagination', '' ),

			// Number list.
			'enable_number_list'             => $enable_number_list,
			'number_style'                   => $number_style,
			'number_layout'                  => $number_layout,
			'number_layout_style'            => $number_layout_style,
			'number_position'                => $number_position,
			'number_position_style'          => $number_position_style,

			// Theme override.
			'enable_override_category_color' => get_theme_mod( 'colormag_enable_override_category_color', false ),
		);
	}

	/**
	 * Build WP_Query based on attributes.
	 *
	 * @param array $attrs Processed attributes.
	 */
	protected function build_query( $attrs ) {
		$args = array(
			'post_type'           => $attrs['post_type'],
			'posts_per_page'      => $attrs['post_count'],
			'post_status'         => 'publish',
			'cat'                 => $attrs['category'],
			'tag_id'              => $attrs['tag'],
			'orderby'             => $attrs['order_by'],
			'order'               => $attrs['order_type'],
			'author'              => 'all' === $attrs['author'] ? '' : $attrs['author'],
			'category__not_in'    => $attrs['excluded_category'],
			'tag__not_in'         => $attrs['excluded_tag'],
			'ignore_sticky_posts' => 1,
			'paged'               => $attrs['paged'],
			'offset'              => $attrs['offset'],
		);

		/**
		 * Filters the WP_Query args for the Grid Module block.
		 *
		 * Allows overriding the post count above the editor's slider limit.
		 *
		 * @param array $args  WP_Query arguments.
		 * @param array $attrs Processed block attributes.
		 */
		$args = apply_filters( 'magazine_blocks_grid_module_query_args', $args, $attrs );

		return $this->query_builder->build_query( $args );
	}

	/**
	 * Render the main block HTML structure.
	 *
	 * @param \WP_Query $query WP_Query object.
	 * @param array     $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	protected function render_block( $query, $attributes ) {
		$html    = '';
		$classes = array(
			'mzb-grid-module',
			'mzb-grid-module-' . $attributes['client_id'],
			$attributes['size'] ? 'is-' . $attributes['size'] : '',
			$attributes['class_name'],
			$attributes['hide_on_desktop'] ? 'magazine-blocks-hide-on-desktop' : '',
			'mzb-' . $attributes['layout'],
			'mzb-' . $attributes['advanced_style'],
		);
		// Render start.
		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', array_filter( $classes ) ) ) );
		// Render heading section.
		$show_top_view_more = $attributes['enable_view_more'] && 'top' === $attributes['view_button_position'];
		if ( $attributes['enable_heading'] || $show_top_view_more ) {
			$html .= $this->render_heading( $attributes, $show_top_view_more ? 'top' : 'none' );
		}

		// Render posts container.
		$html .= $this->render_posts_container( $query, $attributes );

		// Render bottom view more link.
		if ( $attributes['enable_view_more'] && 'bottom' === $attributes['view_button_position'] ) {
			$html .= $this->render_view_more_link( $attributes, 'bottom' );
		}

		// Render pagination.
		if ( $attributes['enable_pagination'] ) {
			$html .= mzb_numbered_pagination( $query->max_num_pages, $attributes['paged'], $attributes['client_id'] );
		}

		$html .= '</div>';

		wp_reset_postdata();
		return $html;
	}

	/**
	 * Render posts container.
	 *
	 * @param \WP_Query $query WP_Query object.
	 * @param array     $attributes Block attributes.
	 * @return string Posts container HTML.
	 */
	protected function render_posts_container( $query, $attributes ) {
		$classes = array(
			'mzb-posts',
			'mzb-' . $attributes['layout'],
			'mzb-' . $attributes['advanced_style'],
			'mzb-post-col--' . $attributes['column'],
			$attributes['number_style'],
			'mzb-number-list__' . $attributes['number_layout_style'],
			'mzb-number-list__' . $attributes['number_position_style'],
		);

		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', array_filter( $classes ) ) ) );

		// Render posts.
		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= $this->render_post( get_the_ID(), $attributes );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render individual post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Post HTML.
	 */
	protected function render_post( $post_id, $attributes ) {
		$html = sprintf(
			'<div class="mzb-post%s">',
			$attributes['enable_post_box_border'] ? ' mzb-post-box-border' : ''
		);

		// Render featured image.
		$html .= $this->render_featured_image_with_meta( $post_id, $attributes );

		// Render post content based on meta position.
		if ( 'top' === $attributes['meta_position'] ) {
			$html .= $this->render_post_content_top_meta( $post_id, $attributes );
		} else {
			$html .= $this->render_post_content_bottom_meta( $post_id, $attributes );
		}

		// Render excerpt and read more.
		if ( $attributes['enable_excerpt'] || $attributes['enable_readmore'] ) {
			$html .= $this->render_excerpt_and_read_more( $post_id, $attributes );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render featured image with meta overlay.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Featured image HTML.
	 */
	protected function render_featured_image_with_meta( $post_id, $attributes ) {
		$src = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id ) : '';
		if ( ! $src ) {
			return '';
		}

		$classes = array( 'mzb-featured-image', $attributes['hover_animation'] );

		// Add category position classes for layout-2.
		if ( 'layout-2' === $attributes['layout'] && 'in-image' === $attributes['category_position'] ) {
			$classes[] = 'mzb-category--inside-image';
		}

		$html = sprintf(
			'<div class="%s"><a href="%s" title="%s"><img src="%s" alt="%s"/><div class="mzb-overlay"></div></a>',
			esc_attr( implode( ' ', $classes ) ),
			esc_url( get_the_permalink( $post_id ) ),
			esc_attr( get_the_title( $post_id ) ),
			esc_url( $src ),
			esc_attr( get_the_title( $post_id ) )
		);

		// Render in-image meta for layout-2.
		if ( 'layout-2' === $attributes['layout'] && 'in-image' === $attributes['category_position'] ) {
			$html .= $this->render_in_image_meta( $post_id, $attributes );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render meta inside image.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string In-image meta HTML.
	 */
	protected function render_in_image_meta( $post_id, $attributes ) {
		if ( ! $attributes['enable_category'] && ! $attributes['enable_comment'] ) {
			return '';
		}

		$html = '<div class="mzb-post-meta">';

		if ( $attributes['enable_category'] ) {
			$html .= $this->render_categories( $post_id );
		}

		if ( $attributes['enable_comment'] ) {
			$html .= $this->render_comments( $post_id );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render post content with meta at top.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Post content HTML.
	 */
	protected function render_post_content_top_meta( $post_id, $attributes ) {
		$html = '<div class="mzb-post-content">';

		// Render out-image meta for not layout-2.
		if ( 'layout-2' !== $attributes['layout'] || 'out-image' === $attributes['category_position'] ) {
			$html .= $this->render_out_image_meta( $post_id, $attributes );
		}

		// Render meta information.
		if ( $this->has_meta_content( $attributes ) ) {
			$html .= $this->render_meta_section( $post_id, $attributes );
		}

		// Render title.
		if ( $attributes['enable_post_title'] ) {
			$html .= $this->render_post_title( $post_id, $attributes['tag_name'] );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render meta outside image.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Out-image meta HTML.
	 */
	protected function render_out_image_meta( $post_id, $attributes ) {
		if ( ! $attributes['enable_category'] && ! $attributes['enable_comment'] ) {
			return '';
		}

		$html = '<div class="mzb-post-meta">';

		if ( $attributes['enable_category'] ) {
			$html .= $this->render_categories( $post_id );
		}

		if ( $attributes['enable_comment'] ) {
			$html .= $this->render_comments( $post_id );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Check if there's any meta content to render.
	 *
	 * @param array $attributes Block attributes.
	 * @return bool Whether meta content exists.
	 */
	protected function has_meta_content( $attributes ) {
		return $attributes['enable_author'] || $attributes['enable_date'] ||
				$attributes['enable_readtime'] || $attributes['enable_viewcount'];
	}

	/**
	 * Render meta information section.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Meta section HTML.
	 */
	protected function render_meta_section( $post_id, $attributes ) {
		$meta_items = array();

		if ( $attributes['enable_author'] ) {
			$meta_items[] = $this->render_author( $post_id, $attributes['enable_icon'] );
		}

		if ( $attributes['enable_date'] ) {
			$meta_items[] = $this->render_date( $post_id, $attributes['enable_icon'] );
		}

		if ( $attributes['enable_readtime'] ) {
			$meta_items[] = $this->render_read_time( $post_id, $attributes['enable_icon'] );
		}

		if ( $attributes['enable_viewcount'] ) {
			$meta_items[] = $this->render_view_count( $post_id, $attributes['enable_icon'] );
		}

		if ( empty( $meta_items ) ) {
			return '';
		}

		return sprintf(
			'<div class="mzb-post-entry-meta mzb-meta-separator--%s">%s</div>',
			esc_attr( $attributes['meta_separator'] ),
			implode( '', $meta_items )
		);
	}

	/**
	 * Render post content with meta at bottom.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Post content HTML.
	 */
	protected function render_post_content_bottom_meta( $post_id, $attributes ) {
		$html = '<div class="mzb-post-content">';

		// Render out-image meta for layout-2.
		if ( 'layout-2' !== $attributes['layout'] || 'out-image' === $attributes['category_position'] ) {
			$html .= $this->render_out_image_meta( $post_id, $attributes );
		}

		// Render title.
		if ( $attributes['enable_post_title'] ) {
			$html .= $this->render_post_title( $post_id, $attributes['tag_name'] );
		}

		// Render meta information.
		if ( $this->has_meta_content( $attributes ) ) {
			$html .= $this->render_meta_section( $post_id, $attributes );
		}

		$html .= '</div>';

		return $html;
	}
}
