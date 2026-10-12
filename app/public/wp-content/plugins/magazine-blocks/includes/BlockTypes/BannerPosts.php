<?php
/**
 * Banner Posts block.
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
class BannerPosts extends Block {

	use PostRenderer;

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'banner-posts';

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		// Extract attributes.
		$attrs = $this->extract_attributes( $attributes );

		// Build the query.
		$query = $this->build_query( $attrs );

		return $this->render_block( $query, $attrs );
	}

	/**
	 * Extracting the attributes.
	 *
	 * @param array $attributes The original attributes.
	 */
	protected function extract_attributes( $attributes ) {
		$client_id  = magazine_blocks_array_get( $attributes, 'clientId', '' );
		$class_name = magazine_blocks_array_get( $attributes, 'className', '' );

		$layout         = magazine_blocks_array_get( $attributes, 'layout', 'layout-1' );
		$heading_layout = magazine_blocks_array_get( $attributes, 'headingLayout', 'heading-layout-1' );

		// Get the specific advanced styles based on layout and heading layout.
		$advanced_style = magazine_blocks_array_get( $attributes, magazine_get_style_key( $layout ), '' );
		$heading_style  = magazine_blocks_array_get( $attributes, magazine_get_heading_style_key( $heading_layout ), '' );

		return array(
			'client_id'                      => $client_id,
			'class_name'                     => $class_name,
			'layout'                         => $layout,
			'advanced_style'                 => $advanced_style,
			'heading_layout'                 => $heading_layout,
			'heading_style'                  => $heading_style,
			'container'                      => magazine_blocks_array_get( $attributes, 'container', '' ),
			'category_background'            => magazine_blocks_array_get( $attributes, 'categoryBackground', '' )['color'] ?? '',
			'enable_heading'                 => magazine_blocks_array_get( $attributes, 'enableHeading', false ),
			'label'                          => magazine_blocks_array_get( $attributes, 'label', 'Latest' ),
			'category'                       => magazine_blocks_array_get( $attributes, 'category', '' ),
			'tag'                            => magazine_blocks_array_get( $attributes, 'tag', '' ),
			'excluded_category'              => magazine_blocks_array_get( $attributes, 'excludedCategory', '' ),
			'excluded_tag'                   => magazine_blocks_array_get( $attributes, 'excludedTag', '' ),
			'order_by'                       => magazine_blocks_array_get( $attributes, 'orderBy', '' ),
			'order_type'                     => magazine_blocks_array_get( $attributes, 'orderType', '' ),
			'author'                         => magazine_blocks_array_get( $attributes, 'authorName', '' ),
			'post_type'                      => magazine_blocks_array_get( $attributes, 'postType', 'post' ),
			'post_title_markup'              => magazine_blocks_sanitize_html_tag(
				magazine_blocks_array_get( $attributes, 'postTitleMarkup', 'h6' ),
				'h6'
			),
			'enable_category'                => magazine_blocks_array_get( $attributes, 'enableCategory', true ),
			'enable_highlighted_category'    => magazine_blocks_array_get( $attributes, 'enableHighlightedCategory', true ),
			'meta_position'                  => magazine_blocks_array_get( $attributes, 'metaPosition', '' ),
			'enable_author'                  => magazine_blocks_array_get( $attributes, 'enableAuthor', '' ),
			'enable_date'                    => magazine_blocks_array_get( $attributes, 'enableDate', '' ),
			'enable_readtime'                => magazine_blocks_array_get( $attributes, 'enableReadTime', '' ),
			'enable_viewcount'               => magazine_blocks_array_get( $attributes, 'enableViewCount', '' ),
			'enable_icon'                    => magazine_blocks_array_get( $attributes, 'enableIcon', '' ),
			'meta_separator'                 => magazine_blocks_array_get( $attributes, 'separatorType', 'none' ),
			'enable_excerpt'                 => magazine_blocks_array_get( $attributes, 'enableExcerpt', '' ),
			'excerpt_limit'                  => magazine_blocks_array_get( $attributes, 'excerptLimit', '' ),
			'excerpt_position'               => magazine_blocks_array_get( $attributes, 'excerptPosition', '' ),
			'enable_readmore'                => magazine_blocks_array_get( $attributes, 'enableReadMore', '' ),
			'read_more_text'                 => magazine_blocks_array_get( $attributes, 'readMoreText', '' ),
			'enable_view_more'               => magazine_blocks_array_get( $attributes, 'enableViewMore', '' ),
			'view_more_text'                 => magazine_blocks_array_get( $attributes, 'viewMoreText', '' ),
			'enable_view_more_icon'          => magazine_blocks_array_get( $attributes, 'enableViewMoreIcon', '' ),
			'view_more_icon'                 => magazine_blocks_array_get( $attributes, 'viewMoreIcon', '' ),
			'view_more_url'                  => magazine_blocks_array_get( $attributes, 'viewMoreLink', array() ),
			'offset'                         => magazine_blocks_array_get( $attributes, 'offset', 0 ),
			'hover_animation'                => magazine_blocks_array_get( $attributes, 'hoverAnimation', '' ),
			'enable_override_category_color' => get_theme_mod( 'colormag_enable_override_category_color', false ),
			'post_box_wrapper_border'        => isset( magazine_blocks_array_get( $attributes, 'postBoxWrapperBorder', '' )['border'] ) ?
				magazine_blocks_array_get( $attributes, 'postBoxWrapperBorder', '' )['border'] : '',
			'layout_2_advanced_style'        => 'layout-2' === $layout ?
				magazine_blocks_array_get( $attributes, 'layout2AdvancedStyle', '' ) : '',
		);
	}

	/**
	 * Get posts per page based on layout.
	 *
	 * @param string $layout The layout slug.
	 * @return int Number of posts.
	 */
	protected function get_posts_per_page_for_layout( $layout ) {
		$layout_config = array(
			'layout-1' => 4,
			'layout-2' => 3,
			'layout-3' => 2,
			'layout-4' => 5,
			'layout-5' => 4,
		);

		return $layout_config[ $layout ] ?? 4;
	}

	/**
	 * Build the WP_Query based on attributes.
	 *
	 * @param array $attrs Processed attributes.
	 * @return WP_Query The built query.
	 */
	protected function build_query( $attrs ) {
		$attrs['post_per_page'] = $this->get_posts_per_page_for_layout( $attrs['layout'] );

		$args = array(
			'post_type'           => $attrs['post_type'],
			'posts_per_page'      => $this->get_posts_per_page_for_layout( $attrs['layout'] ),
			'post_status'         => 'publish',
			'cat'                 => $attrs['category'],
			'tag_id'              => $attrs['tag'],
			'orderby'             => $attrs['order_by'],
			'order'               => $attrs['order_type'],
			'author'              => $attrs['author'],
			'category__not_in'    => $attrs['excluded_category'],
			'tag__not_in'         => $attrs['excluded_tag'],
			'ignore_sticky_posts' => 1,
			'offset'              => $attrs['offset'],
		);

		if ( 'all' === $args['author'] ) {
			unset( $args['author'] );
		}

		/**
		 * Filters the WP_Query args for the Banner Posts block.
		 *
		 * Allows overriding the post count above the editor's slider limit.
		 *
		 * @param array $args  WP_Query arguments.
		 * @param array $attrs Processed block attributes.
		 */
		$args = apply_filters( 'magazine_blocks_banner_posts_query_args', $args, $attrs );

		return $this->query_builder->build_query( $args );
	}

	/**
	 * Render the block HTML.
	 *
	 * @param WP_Query $query The posts query.
	 * @param array    $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	protected function render_block( $query, $attributes ) {
		$html           = '';
		$heading_style  = $attributes['heading_style'];
		$advanced_style = $attributes['advanced_style'];

		if ( $query->have_posts() ) {
			// Start block.
			$html .= sprintf(
				'<div class="mzb-banner-posts mzb-banner-posts-%s %s%s mzb-%s">',
				esc_attr( $attributes['client_id'] ),
				esc_attr( $attributes['class_name'] ),
				( 'layout-5' === $attributes['layout'] && 'layout-5-style-2' === $advanced_style && 'stretched' === $attributes['container'] ) ? 'mzb-alignfull' : '',
				esc_attr( $heading_style )
			);

			// Render heading.
			$html .= $this->render_heading( $attributes );

			// Start posts container.
			$html .= sprintf(
				'<div class="mzb-posts mzb-%s mzb-%s">',
				esc_attr( $attributes['layout'] ),
				esc_attr( $advanced_style )
			);

			// Render posts based on layout.
			if ( 'layout-5-style-2' === $advanced_style ) {
				$html .= $this->render_layout5_style2( $query, $attributes );
			} else {
				$html .= $this->render_default_layout( $query, $attributes );
			}

			// Close posts container.
			$html .= '</div>';

			// Close block container.
			$html .= '</div>';

			$query->reset_postdata();
		}

		return $html;
	}

	/**
	 * Render posts for layout-5-style-2.
	 *
	 * @param WP_Query $query The posts query.
	 * @param array    $attributes Block attributes.
	 * @return string Posts HTML.
	 */
	protected function render_layout5_style2( $query, $attributes ) {
		$html = '';
		$query->the_post();

		// Render the first highlighted post.
		$html .= $this->render_post( get_post(), $attributes, true );

		// Start bottom posts wrapper.
		$html .= '<div class="mzb-bottom-posts-outer-wrapper">';
		$html .= sprintf(
			'<div class="mzb-bottom-posts-wrapper%s">',
			$attributes['post_box_wrapper_border'] ? ' mzb-bottom-posts-wrapper-border' : ''
		);

		// Render remaining posts.
		$index = 2;
		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= $this->render_post( get_post(), $attributes, false, $index );
			++$index;
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Render posts for default layout.
	 *
	 * @param WP_Query $query The posts query.
	 * @param array    $attributes Block attributes.
	 * @return string Posts HTML.
	 */
	protected function render_default_layout( $query, $attributes ) {
		$html  = '';
		$index = 1;

		while ( $query->have_posts() ) {
			$query->the_post();
			$is_highlighted = ( 1 === $index && true === $attributes['enable_highlighted_category'] );
			$html          .= $this->render_post( get_post(), $attributes, $is_highlighted, $index );
			++$index;
		}

		return $html;
	}

	/**
	 * Render a single post.
	 *
	 * @param WP_Post $post The post object.
	 * @param array   $attributes Block attributes.
	 * @param bool    $is_highlighted Whether the post is highlighted.
	 * @param int     $index Post index.
	 * @return string Post HTML.
	 */
	protected function render_post( $post, $attributes, $is_highlighted = false, $index = 1 ) {
		setup_postdata( $post );

		$post_id = $post->ID;
		$html    = '';

		// Get featured image.
		$image = $this->render_featured_image( $post_id, $attributes['hover_animation'] );

		// Get post title.
		$tag   = tag_escape( $attributes['post_title_markup'] );
		$title = sprintf(
			'<%s class="mzb-post-title"><a href="%s">%s</a></%s>',
			$tag,
			esc_url( get_the_permalink( $post_id ) ),
			esc_html( get_the_title( $post_id ) ? get_the_title( $post_id ) : __( '(Untitled)', 'magazine-blocks' ) ),
			$tag
		);

		// Get categories if enabled.
		$category = '';
		if ( ( $is_highlighted && $attributes['enable_highlighted_category'] ) ||
			( ! $is_highlighted && $attributes['enable_category'] )
		) {
			$category = $this->render_categories( $post_id, true );
		}

		// Get author if enabled.
		$author = $attributes['enable_author'] ? $this->render_author( $post_id, $attributes['enable_icon'] ) : '';

		// Get date if enabled.
		$date = $attributes['enable_date'] ? $this->render_date( $post_id, $attributes['enable_icon'] ) : '';

		// Get read time if enabled.
		$read_time = $attributes['enable_readtime'] ? $this->render_read_time( $post_id, $attributes['enable_icon'] ) : '';

		// Get view count if enabled.
		$view_count = $attributes['enable_viewcount'] ? $this->render_view_count( $post_id, $attributes['enable_icon'] ) : '';

		// Get excerpt and read more if enabled.
		$excerpt_content = '';
		if ( $attributes['enable_excerpt'] || $attributes['enable_readmore'] ) {
			$excerpt_content = $this->render_excerpt_and_read_more( $post_id, $attributes );
		}

		// Start post HTML.
		$html .= sprintf(
			'<div class="mzb-post%s">',
			$is_highlighted ? ' mzb-first-post--highlight' : ''
		);

		// Add featured image.
		$html .= $image;

		// Start post content.
		$html .= '<div class="mzb-post-content">';

		// Add categories if available.
		if ( $category ) {
			$html .= '<div class="mzb-post-meta">' . $category . '</div>';
		}

		// Add meta at top if configured.
		if ( 'top' === $attributes['meta_position'] ) {
			$html .= $this->render_post_meta(
				$author,
				$date,
				$read_time,
				$view_count,
				$attributes['meta_separator'],
				$attributes['layout'],
				$attributes['layout_2_advanced_style'],
				$index
			);
		}

		// Add post title.
		$html .= $title;

		// Add meta at bottom if configured.
		if ( 'bottom' === $attributes['meta_position'] ) {
			// Add excerpt above meta if configured.
			if ( ( $attributes['enable_excerpt'] || $attributes['enable_readmore'] ) &&
				'above-meta' === $attributes['excerpt_position']
			) {
				$html .= $excerpt_content;
			}

			$html .= $this->render_post_meta(
				$author,
				$date,
				$read_time,
				$view_count,
				$attributes['meta_separator'],
				$attributes['layout'],
				$attributes['layout_2_advanced_style'],
				$index
			);

			// Add excerpt below meta if configured.
			if ( ( $attributes['enable_excerpt'] || $attributes['enable_readmore'] ) &&
				'below-meta' === $attributes['excerpt_position']
			) {
				$html .= $excerpt_content;
			}
		}

		// Add excerpt if not handled by meta position.
		if ( ( $attributes['enable_excerpt'] || $attributes['enable_readmore'] ) &&
			'bottom' !== $attributes['meta_position']
		) {
			$html .= $excerpt_content;
		}

		// Close post content and post.
		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Render post meta.
	 *
	 * @param string $author Author HTML.
	 * @param string $date Date HTML.
	 * @param string $read_time Read time HTML.
	 * @param string $view_count View count HTML.
	 * @param string $meta_separator Separator type.
	 * @param string $layout Layout type.
	 * @param string $layout_style Layout style.
	 * @param int    $index Post index.
	 * @return string Post meta HTML.
	 */
	protected function render_post_meta(
		$author,
		$date,
		$read_time,
		$view_count,
		$meta_separator,
		$layout,
		$layout_style,
		$index
	) {
		// Skip for certain layout conditions.
		if ( 'layout-2' === $layout && 'layout-2-style-3' === $layout_style && $index > 1 ) {
			return '';
		}

		$html  = '<div class="mzb-post-entry-meta mzb-meta-separator--' . esc_attr( $meta_separator ) . '">';
		$html .= $author;
		$html .= $date;
		$html .= $read_time;
		$html .= $view_count;
		$html .= '</div>';

		return $html;
	}
}
