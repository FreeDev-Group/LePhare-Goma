<?php
/**
 * Category List block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;

defined( 'ABSPATH' ) || exit;

/**
 * Category List block class.
 */
class CategoryList extends Block {


	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'category-list';

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {

		$attrs      = $this->extract_attributes( $attributes );
		$categories = $this->get_categories( $attrs );

		return $this->render_block( $categories, $attrs );
	}

	/**
	 * Extract and process block attributes.
	 *
	 * @param array $attributes Raw block attributes.
	 * @return array Processed attributes.
	 */
	protected function extract_attributes( $attributes ) {
		$client_id      = magazine_blocks_array_get( $attributes, 'clientId', '' );
		$layout         = magazine_blocks_array_get( $attributes, 'layout', '' );
		$heading_layout = magazine_blocks_array_get( $attributes, 'headingLayout', '' );

		// Get the specific advanced styles based on layout and heading layout.
		$advanced_style = magazine_blocks_array_get( $attributes, magazine_get_style_key( $layout ), '' );
		$heading_style  = magazine_blocks_array_get( $attributes, magazine_get_heading_style_key( $heading_layout ), '' );

		// Icon settings.
		$icon_list   = magazine_blocks_array_get( $attributes, 'categoryArrow', array() );
		$icon        = isset( $icon_list['icon'] ) ? magazine_blocks_get_icon( $icon_list['icon'], false ) : '';
		$enable_icon = isset( $icon_list['enable'] ) ? $icon_list['enable'] : false;

		// List icon settings (layout-3-style-1 preset).
		$list_icon_list   = magazine_blocks_array_get( $attributes, 'listIcon', array() );
		$list_icon        = isset( $list_icon_list['icon'] ) ? magazine_blocks_get_icon( $list_icon_list['icon'], false ) : '';
		$list_icon_enable = isset( $list_icon_list['enable'] ) ? $list_icon_list['enable'] : false;

		return array(
			// General attributes.
			'client_id'             => $client_id,
			'layout'                => $layout,
			'advanced_style'        => $advanced_style,
			'post_box_style'        => magazine_blocks_array_get( $attributes, 'postBoxStyle', 'true' ),
			'category_count'        => magazine_blocks_array_get( $attributes, 'categoryCount', '4' ),

			// Icon settings.
			'icon'                  => $icon,
			'enable_icon'           => $enable_icon,
			'list_icon'             => $list_icon,
			'list_icon_enable'      => $list_icon_enable,

			// Heading settings.
			'enable_heading'        => magazine_blocks_array_get( $attributes, 'enableHeading', '' ),
			'heading_layout'        => $heading_layout,
			'heading_style'         => $heading_style,
			'label'                 => magazine_blocks_array_get( $attributes, 'label', 'Categories' ),

			// Category title settings.
			'category_title_markup' => magazine_blocks_sanitize_html_tag( magazine_blocks_array_get( $attributes, 'categoryTitleMarkup', 'h6' ), 'h6' ),
		);
	}

	/**
	 * Get categories based on attributes.
	 *
	 * @param array $attrs Processed attributes.
	 * @return array Categories.
	 */
	protected function get_categories( $attrs ) {
		return get_categories(
			array(
				'hide_empty'          => 1,
				'number'              => $attrs['category_count'],
				'ignore_sticky_posts' => 1,
			)
		);
	}

	/**
	 * Render the main block HTML structure.
	 *
	 * @param array $categories Categories array.
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	protected function render_block( $categories, $attributes ) {
		$html = sprintf(
			'<div class="mzb-category-list mzb-category-list-%s">',
			esc_attr( $attributes['client_id'] )
		);

		// Render heading.
		$html .= $this->render_heading( $attributes );

		// Render categories.
		$html .= $this->render_categories_container( $categories, $attributes );

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render heading section.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Heading HTML.
	 */
	protected function render_heading( $attributes ) {
		if ( ! $attributes['enable_heading'] ) {
			return '';
		}

		return sprintf(
			'<div class="mzb-post-heading mzb-%s mzb-%s"><h2 class="mzb-heading-text">%s</h2></div>',
			esc_attr( $attributes['heading_layout'] ),
			esc_attr( $attributes['heading_style'] ),
			esc_html( $attributes['label'] )
		);
	}

	/**
	 * Render categories container.
	 *
	 * @param array $categories Categories array.
	 * @param array $attributes Block attributes.
	 * @return string Categories HTML.
	 */
	protected function render_categories_container( $categories, $attributes ) {
		$classes = array(
			'mzb-posts',
			'mzb-' . $attributes['layout'],
			'mzb-' . $attributes['advanced_style'],
		);

		// Add separator class for layout-3.
		if ( 'layout-3' === $attributes['layout'] && $attributes['post_box_style'] ) {
			$classes[] = 'separator';
		}

		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', array_filter( $classes ) ) ) );

		foreach ( $categories as $category ) {
			$html .= $this->render_category( $category, $attributes );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render individual category.
	 *
	 * @param \WP_Term $category Category object.
	 * @param array    $attributes Block attributes.
	 * @return string Category HTML.
	 */
	protected function render_category( $category, $attributes ) {
		$cat_id         = $category->term_id;
		$advanced_style = $attributes['advanced_style'];

		$html = sprintf( '<div class="mzb-post mzb-%s">', esc_attr( $cat_id ) );

		// Handle different layout styles.
		if ( 'layout-1-style-2' === $advanced_style ) {
			$html .= $this->render_layout_1_style_2( $category, $cat_id, $attributes );
		} elseif ( 'layout-2-style-2' === $advanced_style ) {
			$html .= $this->render_layout_2_style_2( $category, $cat_id, $attributes );
		} elseif ( 'layout-2-style-3' === $advanced_style ) {
			$html .= $this->render_layout_2_style_3( $category, $cat_id, $attributes );
		} else {
			// Default layout (includes layout-1-style-3 functionality).
			$html .= $this->render_default_layout( $category, $cat_id, $attributes );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render layout 1 style 2.
	 *
	 * @param \WP_Term $category Category object.
	 * @param int      $cat_id Category ID.
	 * @param array    $attributes Block attributes.
	 * @return string Layout HTML.
	 */
	protected function render_layout_1_style_2( $category, $cat_id, $attributes ) {
		$src              = $this->get_category_image( $category->slug );
		$background_style = $src ? 'style="background-image: url(' . esc_url( $src ) . ');"' : '';

		$color_style = '';
		if ( function_exists( 'colormag_category_color' ) ) {
			$color = colormag_category_color( $cat_id );
			if ( $color ) {
				$color_style = 'style="background-color:' . esc_attr( $color ) . ';"';
			}
		}

		$tag = $attributes['category_title_markup'];

		return '
			<div class="mzb-title-wrapper" ' . $background_style . '>
				<div class="mzb-title" ' . $color_style . '>
					<' . $tag . ' class="mzb-post-categories">
						<a href="' . esc_url( get_category_link( $cat_id ) ) . '">' . esc_html( $category->name ) . '</a>
					</' . $tag . '>
					<div class="mzb-post-count-wrapper">
						<div class="mzb-post-count">
							<a href="' . esc_url( get_category_link( $cat_id ) ) . '">' . esc_html( $category->category_count ) . ' ' . esc_html( $this->get_post_count_label( $category->category_count ) ) . '</a>
						</div>
					</div>
				</div>
			</div>
		';
	}

	/**
	 * Render layout 2 style 2.
	 *
	 * @param \WP_Term $category Category object.
	 * @param int      $cat_id Category ID.
	 * @param array    $attributes Block attributes.
	 * @return string Layout HTML.
	 */
	protected function render_layout_2_style_2( $category, $cat_id, $attributes ) {
		$src              = $this->get_category_image( $category->slug );
		$background_style = $src ? 'style="background-image: url(' . esc_url( $src ) . ');"' : '';

		$color_style = '';
		if ( function_exists( 'colormag_category_color' ) ) {
			$color = colormag_category_color( $cat_id );
			if ( $color ) {
				$color_style = 'style="background-color:' . esc_attr( $color ) . ';"';
			}
		}

		$tag = $attributes['category_title_markup'];

		$html  = '<div class="mzb-title-wrapper" ' . $background_style . '>';
		$html .= '<div class="mzb-title">';
		$html .= '<' . $tag . ' class="mzb-post-categories">';
		$html .= '<a href="' . esc_url( get_category_link( $cat_id ) ) . '">' . esc_html( $category->name ) . '</a>';
		$html .= '</' . $tag . '>';
		$html .= '<div class="mzb-post-count-wrapper">';
		$html .= '<div class="mzb-post-count">';
		$html .= '<a href="' . esc_url( get_category_link( $cat_id ) ) . '">' . esc_html( $category->category_count ) . ' ' . esc_html( $this->get_post_count_label( $category->category_count ) ) . '</a>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="mzb-overlay"></div>';
		if ( $attributes['enable_icon'] ) {
			$html .= '<div class="mzb-list-icon">';
			$html .= '<a href="' . esc_url( get_category_link( $cat_id ) ) . '">' . wp_kses( $attributes['icon'], magazine_blocks_get_allowed_svg_elements() ) . '</a>';
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render layout 2 style 3.
	 *
	 * @param \WP_Term $category Category object.
	 * @param int      $cat_id Category ID.
	 * @param array    $attributes Block attributes.
	 * @return string Layout HTML.
	 */
	protected function render_layout_2_style_3( $category, $cat_id, $attributes ) {
		$src              = $this->get_category_image( $category->slug );
		$background_style = $src ? 'style="background-image: url(' . esc_url( $src ) . ');"' : '';

		$html  = '<div class="mzb-title-wrapper" ' . $background_style . '>';
		$html .= '<div class="mzb-overlay"></div>';
		$html .= '</div>';

		$tag = $attributes['category_title_markup'];

		$html .= '<div class="mzb-title-with-icon">';
		$html .= '<div class="mzb-title">';
		$html .= '<' . $tag . ' class="mzb-post-categories">';
		$html .= '<a href="' . esc_url( get_category_link( $cat_id ) ) . '">' . esc_html( $category->name ) . '</a>';
		$html .= '</' . $tag . '>';
		$html .= '<div class="mzb-post-count-wrapper">';
		$html .= '<div class="mzb-post-count">';
		$html .= '<a href="' . esc_url( get_category_link( $cat_id ) ) . '">' . esc_html( $category->category_count ) . ' ' . esc_html( $this->get_post_count_label( $category->category_count ) ) . '</a>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		if ( $attributes['enable_icon'] ) {
			$html .= '<div class="mzb-list-icon">';
			$html .= '<a href="' . esc_url( get_category_link( $cat_id ) ) . '">' . wp_kses( $attributes['icon'], magazine_blocks_get_allowed_svg_elements() ) . '</a>';
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render default layout (includes layout-1-style-3 functionality).
	 *
	 * @param \WP_Term $category Category object.
	 * @param int      $cat_id Category ID.
	 * @param array    $attributes Block attributes.
	 * @return string Layout HTML.
	 */
	protected function render_default_layout( $category, $cat_id, $attributes ) {
		$src              = $this->get_category_image( $category->slug );
		$background_style = $src ? 'style="background-image: url(' . esc_url( $src ) . ');"' : '';

		$color_style = '';
		if ( function_exists( 'colormag_category_color' ) ) {
			$color = colormag_category_color( $cat_id );
			if ( $color ) {
				$color_style = 'style="background-color:' . esc_attr( $color ) . ';"';
			}
		}

		$tag = $attributes['category_title_markup'];

		$html  = '<div class="mzb-title-wrapper" ' . $background_style . '>';
		$html .= '<' . $tag . ' class="mzb-post-categories">';
		if ( 'layout-3' === $attributes['layout'] && $attributes['list_icon_enable'] && $attributes['list_icon'] ) {
			$html .= '<span class="mzb-list-icon">' . wp_kses( $attributes['list_icon'], magazine_blocks_get_allowed_svg_elements() ) . '</span>';
		}
		$html .= '<a href="' . esc_url( get_category_link( $cat_id ) ) . '" ' . $color_style . '>' . esc_html( $category->name ) . '</a>';
		$html .= '</' . $tag . '>';
		$html .= '</div>';
		$html .= '<div class="mzb-post-count-wrapper">';
		$html .= '<div class="mzb-post-count">';
		$html .= '<a href="' . esc_url( get_category_link( $cat_id ) ) . '">' . esc_html( $category->category_count ) . '<span class="mzb-post-count-text"> ' . esc_html( $this->get_post_count_label( $category->category_count ) ) . '</span></a>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get the singular/plural post count label for a category.
	 *
	 * @param int $count Category post count.
	 * @return string Translated "Post"/"Posts" label.
	 */
	protected function get_post_count_label( $count ) {
		return _n( 'Post', 'Posts', $count, 'magazine-blocks' );
	}

	/**
	 * Get category image.
	 *
	 * @param string $cat_slug Category slug.
	 * @return string Image URL.
	 */
	protected function get_category_image( $cat_slug ) {
		$args = array(
			'category_name'  => $cat_slug,
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'desc',
		);

		$posts = get_posts( $args );

		if ( $posts ) {
			$post_id = $posts[0]->ID;
			if ( has_post_thumbnail( $post_id ) ) {
				return get_the_post_thumbnail_url( $post_id );
			}
		}

		return '';
	}
}
