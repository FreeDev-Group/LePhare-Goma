<?php

/**
 * Slider block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\PostRenderer;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Slider block class.
 */
class Slider extends Block {


	use PostRenderer;

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'slider';
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
	 * @param array $attributes Raw block attributes.
	 * @return array Processed attributes.
	 */
	protected function extract_attributes( $attributes ) {
		$client_id      = magazine_blocks_array_get( $attributes, 'clientId', '' );
		$heading_layout = magazine_blocks_array_get( $attributes, 'headingLayout', '' );

		// Get the specific advanced styles based on layout and heading layout.
		$heading_style = magazine_blocks_array_get( $attributes, magazine_get_heading_style_key( $heading_layout ), '' );

		$slider_style    = magazine_blocks_array_get( $attributes, 'sliderStyle', 'style1' );
		$slides_per_view = ( 'style3' === $slider_style || 'style4' === $slider_style ) ?
			intval( magazine_blocks_array_get( $attributes, 'slidesPerView', '1' ) ) : 1;

		return array(
			// General attributes.
			'client_id'                      => $client_id,
			'class_name'                     => magazine_blocks_array_get( $attributes, 'className', '' ),
			'slider_style'                   => $slider_style,
			'slides_per_view'                => $slides_per_view,
			'hide_on_desktop'                => magazine_blocks_array_get( $attributes, 'hideOnDesktop', '' ),

			// Slider settings.
			'slider_args'                    => array(
				'speed'         => magazine_blocks_array_get( $attributes, 'sliderSpeed', '' ),
				'autoplay'      => magazine_blocks_array_get( $attributes, 'enableAutoPlay', 'true' ),
				'arrows'        => magazine_blocks_array_get( $attributes, 'enableArrow', '' ),
				'pagination'    => magazine_blocks_array_get( $attributes, 'enableDot', '' ),
				'pauseOnHover'  => magazine_blocks_array_get( $attributes, 'enablePauseOnHover', '' ),
				'slidesPerView' => $slides_per_view,
				'sliderStyle'   => $slider_style,
			),

			// Arrow settings.
			'enable_arrow'                   => magazine_blocks_array_get( $attributes, 'enableArrow', '' ),
			'arrow_position'                 => magazine_blocks_array_get( $attributes, 'arrowPosition', 'center' ),
			'arrow_horizontal_placement'     => magazine_blocks_array_get( $attributes, 'arrowHorizontalPlacement', 'inside' ),

			// Pagination settings.
			'enable_pagination'              => magazine_blocks_array_get( $attributes, 'enableDot', '' ),
			'dot_position'                   => magazine_blocks_array_get( $attributes, 'dotPosition', '' ),

			// Query parameters.
			'category'                       => magazine_blocks_array_get( $attributes, 'category', '' ),
			'tag'                            => magazine_blocks_array_get( $attributes, 'tag', '' ),
			'excluded_category'              => magazine_blocks_array_get( $attributes, 'excludedCategory', '' ),
			'excluded_tag'                   => magazine_blocks_array_get( $attributes, 'excludedTag', '' ),
			'order_by'                       => magazine_blocks_array_get( $attributes, 'orderBy', '' ),
			'order_type'                     => magazine_blocks_array_get( $attributes, 'orderType', '' ),
			'author'                         => magazine_blocks_array_get( $attributes, 'authorName', '' ),
			'post_count'                     => magazine_blocks_array_get( $attributes, 'postCount', 4 ),
			'post_type'                      => magazine_blocks_array_get( $attributes, 'postType', 'post' ),

			// Heading settings.
			'enable_heading'                 => magazine_blocks_array_get( $attributes, 'enableHeading', '' ),
			'heading_layout'                 => $heading_layout,
			'heading_style'                  => $heading_style,
			'label'                          => magazine_blocks_array_get( $attributes, 'label', 'Latest' ),

			// Category settings.
			'enable_category'                => magazine_blocks_array_get( $attributes, 'enableCategory', '' ),
			'category_position'              => magazine_blocks_array_get( $attributes, 'categoryPosition', '' ),
			'header_meta_position'           => magazine_blocks_array_get( $attributes, 'headerMetaPosition', '' ),

			// Meta settings.
			'meta_position'                  => magazine_blocks_array_get( $attributes, 'metaPosition', '' ),
			'enable_author'                  => magazine_blocks_array_get( $attributes, 'enableAuthor', '' ),
			'enable_date'                    => magazine_blocks_array_get( $attributes, 'enableDate', '' ),
			'enable_readtime'                => magazine_blocks_array_get( $attributes, 'enableReadTime', '' ),
			'enable_viewcount'               => magazine_blocks_array_get( $attributes, 'enableViewCount', '' ),
			'enable_icon'                    => magazine_blocks_array_get( $attributes, 'enableIcon', '' ),
			'enable_meta_separator'          => magazine_blocks_array_get( $attributes, 'enableMetaSeparator', '' ),
			'meta_separator_position'        => magazine_blocks_array_get( $attributes, 'metaSeparatorPosition', '' ),

			// Excerpt settings.
			'enable_excerpt'                 => magazine_blocks_array_get( $attributes, 'enableExcerpt', '' ),
			'excerpt_limit'                  => magazine_blocks_array_get( $attributes, 'excerptLimit', '' ),

			// Read more settings.
			'enable_readmore'                => magazine_blocks_array_get( $attributes, 'enableReadMore', '' ),
			'read_more_text'                 => magazine_blocks_array_get( $attributes, 'readMoreText', '' ),

			// Post box settings.
			'enable_post_box_border'         => magazine_blocks_array_get( $attributes, 'enablePostBoxBorder', 'true' ),

			// Post title settings.
			'post_title_markup'              => magazine_blocks_sanitize_html_tag(
				magazine_blocks_array_get( $attributes, 'postTitleMarkup', 'h3' ),
				'h3'
			),
			'enable_post_title_border'       => magazine_blocks_array_get( $attributes, 'enablePostTitleBorder', 'true' ),

			// View more settings.
			'enable_view_more'               => magazine_blocks_array_get( $attributes, 'enableViewMore', '' ),
			'view_more_text'                 => magazine_blocks_array_get( $attributes, 'viewMoreText', '' ),
			'view_button_position'           => magazine_blocks_array_get( $attributes, 'viewButtonPosition', '' ),
			'enable_view_more_icon'          => magazine_blocks_array_get( $attributes, 'enableViewMoreIcon', '' ),
			'view_more_icon'                 => magazine_blocks_array_get( $attributes, 'viewMoreIcon', '' ),
			'view_more_url'                  => magazine_blocks_array_get( $attributes, 'viewMoreLink', array() ),

			// Theme override.
			'enable_override_category_color' => get_theme_mod( 'colormag_enable_override_category_color', false ),
		);
	}

	/**
	 * Build WP_Query based on attributes.
	 *
	 * @param array $attrs Processed attributes.
	 * @return WP_Query
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
		);

		/**
		 * Filters the WP_Query args for the Slider block.
		 *
		 * Allows overriding the post count above the editor's slider limit.
		 *
		 * @param array $args  WP_Query arguments.
		 * @param array $attrs Processed block attributes.
		 */
		$args = apply_filters( 'magazine_blocks_slider_query_args', $args, $attrs );

		return $this->query_builder->build_query( $args );
	}

	/**
	 * Render the main block HTML structure.
	 *
	 * @param WP_Query $query WP_Query object.
	 * @param array    $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	protected function render_block( $query, $attributes ) {
		$classes = array(
			'mzb-slider',
			'mzb-slider-' . $attributes['client_id'],
			$attributes['class_name'],
			$attributes['hide_on_desktop'] ? 'magazine-blocks-hide-on-desktop' : '',
			'mzb-arrow-position-' . $attributes['arrow_position'],
			'mzb-arrow-horizontal-placement--' . $attributes['arrow_horizontal_placement'],
		);

		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', array_filter( $classes ) ) ) );

		// Render heading section.
		$html .= $this->render_heading_section( $attributes );

		// Render top arrows.
		if ( 'top' === $attributes['arrow_position'] && $attributes['enable_arrow'] ) {
			$html .= $this->render_arrows();
		}

		// Render slider.
		$html .= $this->render_slider( $query, $attributes );

		// Render bottom arrows.
		if ( 'bottom' === $attributes['arrow_position'] && $attributes['enable_arrow'] ) {
			$html .= $this->render_arrows();
		}

		// Render bottom view more link.
		if ( $attributes['enable_view_more'] && 'bottom' === $attributes['view_button_position'] ) {
			$html .= $this->render_view_more_link( $attributes, 'bottom' );
		}

		// Render outside arrows.
		if (
			'center' === $attributes['arrow_position'] && $attributes['enable_arrow'] &&
			( 'outside' === $attributes['arrow_horizontal_placement'] || 'in-between' === $attributes['arrow_horizontal_placement'] )
		) {
			$html .= $this->render_arrows();
		}

		// Render outside pagination.
		if ( $attributes['enable_pagination'] && 'outside-swiper' === $attributes['dot_position'] ) {
			$html .= '<div class="swiper-pagination swiper-pagination--outside-swiper"></div>';
		}

		$html .= '</div>';

		wp_reset_postdata();
		return $html;
	}

	/**
	 * Render heading section.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Heading HTML.
	 */
	protected function render_heading_section( $attributes ) {
		if ( ! $attributes['enable_heading'] && ! ( $attributes['enable_view_more'] && 'top' === $attributes['view_button_position'] ) ) {
			return '';
		}

		$html = sprintf(
			'<div class="mzb-post-heading mzb-%s mzb-%s">',
			esc_attr( $attributes['heading_layout'] ),
			esc_attr( $attributes['heading_style'] )
		);

		if ( $attributes['enable_heading'] ) {
			$html .= sprintf(
				'<h2 class="mzb-heading-text">%s</h2>',
				esc_html( $attributes['label'] )
			);
		}

		if ( $attributes['enable_view_more'] && 'top' === $attributes['view_button_position'] ) {
			$html .= $this->render_view_more_link( $attributes, 'top' );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render slider container.
	 *
	 * @param WP_Query $query WP_Query object.
	 * @param array    $attributes Block attributes.
	 * @return string Slider HTML.
	 */
	protected function render_slider( $query, $attributes ) {
		$html = sprintf(
			'<div class="swiper" data-swiper=%s>',
			htmlspecialchars_decode( wp_json_encode( $attributes['slider_args'] ) )
		);

		$html .= '<div class="swiper-wrapper">';

		// Render slides.
		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= $this->render_slide( get_the_ID(), $attributes );
		}

		$html .= '</div>';

		// Render inside pagination.
		if ( $attributes['enable_pagination'] && 'inside-swiper' === $attributes['dot_position'] ) {
			$html .= '<div class="swiper-pagination"></div>';
		}

		// Render inside arrows.
		if ( 'center' === $attributes['arrow_position'] && $attributes['enable_arrow'] && 'inside' === $attributes['arrow_horizontal_placement'] ) {
			$html .= $this->render_arrows();
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render individual slide.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Slide HTML.
	 */
	protected function render_slide( $post_id, $attributes ) {
		$classes = array(
			'swiper-slide',
			$attributes['slider_style'] ? $attributes['slider_style'] : 'style1',
			$attributes['enable_post_box_border'] ? ' mzb-post-box-border' : '',
		);

		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', array_filter( $classes ) ) ) );

		// Render featured image.
		$html .= $this->render_featured_image( $post_id, $attributes );

		// Add overlay for style2.
		if ( 'style2' === $attributes['slider_style'] ) {
			$html .= '<div class="mzb-overlay">';
		}

		// Render content.
		$html .= $this->render_slide_content( $post_id, $attributes );

		// Close overlay for style2.
		if ( 'style2' === $attributes['slider_style'] ) {
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render featured image.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Featured image HTML.
	 */
	protected function render_featured_image( $post_id, $attributes ) {
		$src = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id ) : '';
		if ( ! $src ) {
			return '';
		}

		$classes = array( 'mzb-featured-image' );
		if ( 'style3' === $attributes['slider_style'] && 'in-image' === $attributes['category_position'] ) {
			$classes[] = 'mzb-category--inside-image';
		}

		$html = sprintf(
			'<div class="%s"><a href="%s" alt="%s"><img src="%s" alt="%s"/></a>',
			esc_attr( implode( ' ', $classes ) ),
			esc_url( get_the_permalink( $post_id ) ),
			esc_attr( get_the_title( $post_id ) ),
			esc_url( $src ),
			esc_attr( get_the_title( $post_id ) )
		);

		// Render in-image category meta.
		if ( $attributes['enable_category'] && 'style3' === $attributes['slider_style'] && 'in-image' === $attributes['category_position'] ) {
			$html .= '<div class="mzb-post-meta">';
			$html .= $this->render_categories( $post_id, $attributes['enable_override_category_color'] );
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render slide content.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Content HTML.
	 */
	protected function render_slide_content( $post_id, $attributes ) {
		$html = '<div class="mzb-post-content">';

		// Render category meta.
		if ( $attributes['enable_category'] && ( 'style3' !== $attributes['slider_style'] || 'out-image' === $attributes['category_position'] ) ) {
			$html .= '<div class="mzb-post-meta">';
			$html .= $this->render_categories( $post_id, $attributes['enable_override_category_color'] );
			$html .= '</div>';
		}

		// Render top meta.
		if ( 'top' === $attributes['meta_position'] ) {
			$html .= $this->render_post_meta_info( $post_id, $attributes );
		}
		// Render title.
		$title_classes = array(
			'mzb-post-title',
			$attributes['enable_post_title_border'] ? ' mzb-post-title-border' : '',
		);
		$tag           = tag_escape( $attributes['post_title_markup'] );

		$html .= sprintf(
			'<%s class="%s"><a href="%s">%s</a></%s>',
			$tag,
			esc_attr( implode( ' ', array_filter( $title_classes ) ) ),
			esc_url( get_the_permalink( $post_id ) ),
			esc_html( get_the_title( $post_id ) ? get_the_title( $post_id ) : __( '(Untitled)', 'magazine-blocks' ) ),
			$tag
		);

		// Render bottom meta.
		if ( 'bottom' === $attributes['meta_position'] ) {
			$html .= $this->render_post_meta_info( $post_id, $attributes );
		}

		// Render excerpt and read more.
		if ( $attributes['enable_excerpt'] || $attributes['enable_readmore'] ) {
			$html .= $this->render_excerpt_and_read_more( $post_id, $attributes );
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
	protected function render_post_meta_info( $post_id, $attributes ) {
		if (
			! $attributes['enable_author'] && ! $attributes['enable_date'] &&
			! $attributes['enable_readtime'] && ! $attributes['enable_viewcount']
		) {
			return '';
		}

		$classes = array( 'mzb-post-entry-meta' );
		if ( $attributes['enable_meta_separator'] && $attributes['meta_separator'] ) {
			$classes[] = 'mzb-meta-separator--' . $attributes['meta_separator_position'];
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
	 * Render view more link.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $position Link position.
	 * @return string View more HTML.
	 */
	protected function render_view_more_link( $attributes, $position = '' ) {
		if ( empty( $attributes['enable_view_more'] ) ) {
			return '';
		}

		$href   = $attributes['view_more_url']['url'] ?? '';
		$target = ! empty( $attributes['view_more_url']['newTab'] ) ? ' target="_blank"' : '';
		$rel    = ! empty( $attributes['view_more_url']['noFollow'] ) ? ' rel="nofollow"' : '';
		$icon   = $attributes['enable_view_more_icon'] ? wp_kses( magazine_blocks_get_icon( $attributes['view_more_icon'], false ), magazine_blocks_get_allowed_svg_elements() ) : '';

		return sprintf(
			'<div class="mzb-view-more%s"><a href="%s"%s%s>
				<p>%s</p>%s
			</a></div>',
			$position ? ' mzb-view-more--' . esc_attr( $position ) : '',
			esc_url( $href ),
			$target,
			$rel,
			esc_html( $attributes['view_more_text'] ),
			$icon
		);
	}

	/**
	 * Render navigation arrows.
	 *
	 * @return string Arrows HTML.
	 */
	protected function render_arrows() {
		return '<div class="swiper-button-prev"></div><div class="swiper-button-next"></div>';
	}

	/**
	 * Render categories with optional color override.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $enable_override_color Whether to enable color override.
	 * @return string Categories HTML.
	 */
	protected function render_categories( $post_id, $enable_override_color = false ) {
		$categories = get_the_category_list( ' ', '', $post_id );
		if ( ! $categories ) {
			return '';
		}

		// Add category link classes for color override.
		if ( $enable_override_color ) {
			$categories = preg_replace_callback(
				'/<a(.+?)href="([^"]+)"(.+?)>([^<]+)<\/a>/',
				function ( $matches ) {
					$cat_id = get_cat_ID( $matches[4] );
					$color  = function_exists( 'colormag_category_color' ) ? colormag_category_color( $cat_id ) : '';
					return sprintf(
						'<a%shref="%s"%s style="%s">%s</a>',
						$matches[1],
						$matches[2],
						$matches[3],
						$color ? 'background-color:' . esc_attr( $color ) . ';' : '',
						$matches[4]
					);
				},
				$categories
			);
		}

		return '<span class="mzb-post-categories">' . $categories . '</span>';
	}
}
