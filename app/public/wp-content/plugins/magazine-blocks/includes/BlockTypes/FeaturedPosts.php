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
class FeaturedPosts extends Block {

	use PostRenderer;

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'featured-posts';
	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		if ( magazine_blocks_is_rest_request() ) {
			return $content;
		}

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
	 * @since x.x.x
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

		return array(
			// General attributes.
			'client_id'                          => $client_id,
			'class_name'                         => $class_name,
			'layout'                             => $layout,
			'advanced_style'                     => $advanced_style,
			'heading_layout'                     => $heading_layout,
			'heading_style'                      => $heading_style,
			'column'                             => magazine_blocks_array_get( $attributes, 'column', '' ),

			// Query parameters.
			'category'                           => magazine_blocks_array_get( $attributes, 'category', '' ),
			'tag'                                => magazine_blocks_array_get( $attributes, 'tag', '' ),
			'excluded_category'                  => magazine_blocks_array_get( $attributes, 'excludedCategory', '' ),
			'excluded_tag'                       => magazine_blocks_array_get( $attributes, 'excludedTag', '' ),
			'order_by'                           => magazine_blocks_array_get( $attributes, 'orderBy', '' ),
			'order_type'                         => magazine_blocks_array_get( $attributes, 'orderType', '' ),
			'author'                             => magazine_blocks_array_get( $attributes, 'authorName', '' ),
			'post_count'                         => magazine_blocks_array_get( $attributes, 'postCount', '' ),
			'post_type'                          => magazine_blocks_array_get( $attributes, 'postType', 'post' ),
			'offset'                             => magazine_blocks_array_get( $attributes, 'offset', 0 ),

			// Heading settings.
			'enable_heading'                     => magazine_blocks_array_get( $attributes, 'enableHeading', '' ),
			'label'                              => magazine_blocks_array_get( $attributes, 'label', 'Latest' ),

			// Post box settings.
			'post_box_style'                     => magazine_blocks_array_get( $attributes, 'postBoxStyle', 'boxed' ),

			// Post title settings.
			'post_title_markup'                  => magazine_blocks_sanitize_html_tag(
				magazine_blocks_array_get( $attributes, 'postTitleMarkup', 'h6' ),
				'h6'
			),

			// Featured image settings.
			'enable_featured_image'              => magazine_blocks_array_get( $attributes, 'enableFeaturedImage', '' ),
			'hover_animation'                    => magazine_blocks_array_get( $attributes, 'hoverAnimation', '' ),

			// Category and comment settings.
			'enable_category'                    => magazine_blocks_array_get( $attributes, 'enableCategory', '' ),
			'enable_comment'                     => magazine_blocks_array_get( $attributes, 'enableComment', '' ),
			'enable_highlighted_category'        => magazine_blocks_array_get( $attributes, 'enableHighlightedCategory', '' ),
			'enable_highlighted_comment'         => magazine_blocks_array_get( $attributes, 'enableHighlightedComment', '' ),
			'category_position'                  => magazine_blocks_array_get( $attributes, 'categoryPosition', '' ),
			'highlighted_category_position'      => magazine_blocks_array_get( $attributes, 'highlightedCategoryPosition', '' ),

			// Meta settings.
			'meta_position'                      => magazine_blocks_array_get( $attributes, 'metaPosition', '' ),
			'enable_author'                      => magazine_blocks_array_get( $attributes, 'enableAuthor', '' ),
			'enable_date'                        => magazine_blocks_array_get( $attributes, 'enableDate', '' ),
			'enable_readtime'                    => magazine_blocks_array_get( $attributes, 'enableReadTime', '' ),
			'enable_viewcount'                   => magazine_blocks_array_get( $attributes, 'enableViewCount', '' ),
			'enable_icon'                        => magazine_blocks_array_get( $attributes, 'enableIcon', '' ),
			'enable_meta_separator'              => magazine_blocks_array_get( $attributes, 'enableMetaSeparator', '' ),
			'meta_separator'                     => magazine_blocks_array_get( $attributes, 'separatorType', 'none' ),

			// Excerpt settings.
			'enable_excerpt'                     => magazine_blocks_array_get( $attributes, 'enableExcerpt', '' ),
			'excerpt_limit'                      => magazine_blocks_array_get( $attributes, 'excerptLimit', '' ),

			// Read more settings.
			'enable_readmore'                    => magazine_blocks_array_get( $attributes, 'enableReadMore', '' ),
			'read_more_text'                     => magazine_blocks_array_get( $attributes, 'readMoreText', '' ),
			'enable_read_more_icon'              => magazine_blocks_array_get( $attributes, 'enableReadMoreIcon', '' ),
			'read_more_icon'                     => magazine_blocks_array_get( $attributes, 'readMoreIcon', '' ),
			'enable_read_more_border'            => magazine_blocks_array_get( $attributes, 'enableReadMoreBorder', '' ),

			// Pagination settings.
			'enable_pagination'                  => magazine_blocks_array_get( $attributes, 'enablePagination', '' ),

			// View more settings.
			'enable_view_more'                   => magazine_blocks_array_get( $attributes, 'enableViewMore', '' ),
			'view_more_text'                     => magazine_blocks_array_get( $attributes, 'viewMoreText', '' ),
			'view_button_position'               => magazine_blocks_array_get( $attributes, 'viewButtonPosition', '' ),
			'enable_view_more_icon'              => magazine_blocks_array_get( $attributes, 'enableViewMoreIcon', '' ),
			'view_more_icon'                     => magazine_blocks_array_get( $attributes, 'viewMoreIcon', '' ),
			'view_more_url'                      => magazine_blocks_array_get( $attributes, 'viewMoreLink', array() ),
			'enable_view_more_border'            => magazine_blocks_array_get( $attributes, 'enableViewMoreBorder', '' ),

			// Layout specific settings.
			'layout4_top_row_count'              => magazine_blocks_array_get( $attributes, 'layout4TopRowCount', 2 ),
			'layout4_bottom_row_count'           => magazine_blocks_array_get( $attributes, 'layout4BottomRowCount', 3 ),
			'enable_highlighted_category_border' => magazine_blocks_array_get( $attributes, 'enableHighlightedCategoryBorder', '' ),
			'enable_category_border'             => magazine_blocks_array_get( $attributes, 'enableCategoryBorder', '' ),

			// Theme override settings.
			'enable_override_category_color'     => get_theme_mod( 'colormag_enable_override_category_color', false ),

			// Pagination.
			'paged'                              => isset( $_GET[ 'block_id_' . $client_id ] ) ? max( 1, intval( $_GET[ 'block_id_' . $client_id ] ) ) : 1, //phpcs:ignore.

			// Border settings.
			'post_card_border'                   => isset( magazine_blocks_array_get( $attributes, 'postCardBorder', '' )['border'] ) ?
				magazine_blocks_array_get( $attributes, 'postCardBorder', '' )['border'] : '',
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
		 * Filters the WP_Query args for the Featured Posts block.
		 *
		 * Allows overriding the post count above the editor's slider limit.
		 *
		 * @param array $args  WP_Query arguments.
		 * @param array $attrs Processed block attributes.
		 */
		$args = apply_filters( 'magazine_blocks_featured_posts_query_args', $args, $attrs );

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
		$html           = '';
		$heading_style  = $attributes['heading_style'];
		$advanced_style = $attributes['advanced_style'];

		$html = sprintf(
			'<div class="mzb-featured-posts mzb-featured-posts-%s %s%s">',
			esc_attr( $attributes['client_id'] ),
			esc_attr( $attributes['class_name'] ),
			$attributes['enable_view_more_border'] ? ' mzb-view-more-border' : ''
		);

		// Render heading section.
		$html .= $this->render_heading( $attributes, 'top' === $attributes['view_button_position'] ? 'top' : 'none' );

		// Render posts container.
		$html .= $this->render_posts_container( $query, $attributes );

		// Render bottom view more link.
		$html .= $this->render_view_more_link( $attributes, 'bottom' === $attributes['view_button_position'] ? 'bottom' : 'none' );

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
			'mzb-post-col--' . $attributes['column'],
			'mzb-' . $attributes['layout'],
			'mzb-' . $attributes['advanced_style'],
			'mzb-' . $attributes['post_box_style'],
			$attributes['post_card_border'] ? ' mzb-post-card-border' : '',
			$attributes['enable_read_more_border'] ? ' mzb-read-more-border' : '',
		);

		// Add layout-4 specific classes.
		if ( 'layout-4' === $attributes['layout'] ) {
			$classes[] = 'mzb-layout-4-style-1-top-row-' . $attributes['layout4_top_row_count'];
			$classes[] = 'mzb-layout-4-style-1-bottom-row-' . $attributes['layout4_bottom_row_count'];
		}

		// Add full width class for single post.
		if ( 1 === $attributes['post_count'] ) {
			$classes[] = 'mzb-post-col--full';
		}

		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', array_filter( $classes ) ) ) );

		// Render posts.
		$index = 1;
		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= $this->render_post( get_the_ID(), $attributes, $index );
			++$index;
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render individual post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @param int   $index Post index.
	 * @return string Post HTML.
	 */
	protected function render_post( $post_id, $attributes, $index ) {
		$is_highlighted = $this->is_highlighted_post( $index, $attributes );

		$html = sprintf(
			'<div class="mzb-post%s%s">',
			$is_highlighted ? ' mzb-first-post--highlight' : '',
			$is_highlighted && $attributes['enable_highlighted_category_border'] ? ' mzb-highlighted-post-category-border' :
			( $attributes['enable_category_border'] ? ' mzb-post-category-border' : '' )
		);

		// Render featured image if enabled.
		if ( $attributes['enable_featured_image'] ) {
			$html .= $this->render_featured_image_with_meta( $post_id, $attributes, $index, $is_highlighted );
		}

		$html .= '<div class="mzb-post-content' . ( $attributes['meta_position'] ? ' mzb-meta-position--' . esc_attr( $attributes['meta_position'] ) : '' ) . '">';

		// Render out-image meta.
		$html .= $this->render_out_image_meta( $post_id, $attributes, $index, $is_highlighted );

		// Render top meta.
		if ( 'top' === $attributes['meta_position'] ) {
			$html .= $this->render_post_meta( $post_id, $attributes );
		}

		// Render title.
		$html .= $this->render_post_title( $post_id, $attributes['post_title_markup'] );

		// Render bottom meta.
		if ( 'bottom' === $attributes['meta_position'] ) {
			$html .= $this->render_post_meta( $post_id, $attributes );
		}

		// Render excerpt and read more.
		if ( $this->should_render_excerpt( $index, $attributes ) ) {
			$html .= $this->render_excerpt_and_read_more( $post_id, $attributes );
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Check if post is highlighted based on layout and index.
	 *
	 * @param int   $index Post index.
	 * @param array $attributes Block attributes.
	 * @return bool Whether post is highlighted.
	 */
	protected function is_highlighted_post( $index, $attributes ) {
		if ( 'layout-4' === $attributes['layout'] ) {
			return false;
		}

		if ( 1 === $index ) {
			return true;
		}

		if ( 'layout-1' === $attributes['layout'] ) {
			$column = (int) $attributes['column'];
			return $index <= $column;
		}

		return false;
	}
	/**
	 * Render featured image with meta overlay.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @param int   $index Post index.
	 * @param bool  $is_highlighted Whether post is highlighted.
	 * @return string Featured image HTML.
	 */
	protected function render_featured_image_with_meta( $post_id, $attributes, $index, $is_highlighted ) {
		$src = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id ) : '';
		if ( ! $src ) {
			return '';
		}

		$classes = array( 'mzb-featured-image', $attributes['hover_animation'] );

		// Add category position classes.
		$position = $is_highlighted ? $attributes['highlighted_category_position'] : $attributes['category_position'];
		if ( 'in-image' === $position ) {
			$classes[] = 'mzb-category--inside-image';
		}

		$html = sprintf(
			'<div class="%s"><a href="%s" title="%s"><img src="%s" alt="%s"/></a>',
			esc_attr( implode( ' ', array_filter( $classes ) ) ),
			esc_url( get_the_permalink( $post_id ) ),
			esc_attr( get_the_title( $post_id ) ),
			esc_url( $src ),
			esc_attr( get_the_title( $post_id ) )
		);

		// Render in-image meta.
		if ( 'in-image' === $position ) {
			$html .= $this->render_in_image_meta( $post_id, $attributes, $is_highlighted );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render meta inside image.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @param bool  $is_highlighted Whether post is highlighted.
	 * @return string In-image meta HTML.
	 */
	protected function render_in_image_meta( $post_id, $attributes, $is_highlighted ) {
		$enable_category = $is_highlighted ? $attributes['enable_highlighted_category'] : $attributes['enable_category'];
		$enable_comment  = $is_highlighted ? $attributes['enable_highlighted_comment'] : $attributes['enable_comment'];

		if ( ! $enable_category && ! $enable_comment ) {
			return '';
		}

		$html = '<div class="mzb-post-meta">';

		if ( $enable_category ) {
			$html .= $this->render_categories( $post_id, $attributes['enable_override_category_color'] );
		}

		if ( $enable_comment ) {
			$html .= $this->render_comments( $post_id );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render meta outside image.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @param int   $index Post index.
	 * @param bool  $is_highlighted Whether post is highlighted.
	 * @return string Out-image meta HTML.
	 */
	protected function render_out_image_meta( $post_id, $attributes, $index, $is_highlighted ) {
		$position = $is_highlighted ? $attributes['highlighted_category_position'] : $attributes['category_position'];
		if ( 'out-image' !== $position ) {
			return '';
		}

		$enable_category = $is_highlighted ? $attributes['enable_highlighted_category'] : $attributes['enable_category'];
		$enable_comment  = $is_highlighted ? $attributes['enable_highlighted_comment'] : $attributes['enable_comment'];

		if ( ! $enable_category && ! $enable_comment ) {
			return '';
		}

		$html = '<div class="mzb-post-meta">';

		if ( $enable_category ) {
			$html .= $this->render_categories( $post_id, $attributes['enable_override_category_color'] );
		}

		if ( $enable_comment ) {
			$html .= $this->render_comments( $post_id );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render post meta information.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Post meta HTML.
	 */
	protected function render_post_meta( $post_id, $attributes ) {
		if ( ! $attributes['enable_author'] && ! $attributes['enable_date'] &&
			! $attributes['enable_readtime'] && ! $attributes['enable_viewcount'] ) {
			return '';
		}

		$classes = array( 'mzb-post-entry-meta' );
		if ( $attributes['enable_meta_separator'] && $attributes['meta_separator'] ) {
			$classes[] = 'mzb-meta-separator--' . $attributes['meta_separator'];
		}

		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', $classes ) ) );

		if ( $attributes['enable_author'] ) {
			$html .= $this->render_author( $post_id, $attributes['enable_icon'] );
		}

		if ( $attributes['enable_date'] ) {
			$html .= $this->render_date( $post_id, $attributes['enable_icon'] );
		}

		if ( $attributes['enable_readtime'] ) {
			$html .= $this->render_read_time( $post_id, $attributes['enable_icon'] );
		}

		if ( $attributes['enable_viewcount'] ) {
			$html .= $this->render_view_count( $post_id, $attributes['enable_icon'] );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Check if excerpt should be rendered for current post.
	 *
	 * @param int   $index Post index.
	 * @param array $attributes Block attributes.
	 * @return bool Whether to render excerpt.
	 */
	protected function should_render_excerpt( $index, $attributes ) {
		if ( ! $attributes['enable_excerpt'] && ! $attributes['enable_readmore'] ) {
			return false;
		}

		// Layout-specific excerpt rules.
		if ( 'layout-6' === $attributes['layout'] && $index > 1 ) {
			return false;
		}

		if ( 'layout-5' === $attributes['layout'] && $index > 1 ) {
			return false;
		}

		return true;
	}
}
