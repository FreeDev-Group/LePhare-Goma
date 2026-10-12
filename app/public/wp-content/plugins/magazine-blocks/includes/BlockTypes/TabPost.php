<?php

/**
 * Tab Post block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\PostRenderer;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Tab Post block class.
 */
class TabPost extends Block {

	use PostRenderer;

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'tab-post';
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

		// Get both queries.
		$latest_query  = $this->build_latest_query( $attrs );
		$popular_query = $this->build_popular_query( $attrs );

		return $this->render_block( $latest_query, $popular_query, $attrs );
	}

	/**
	 * Extract and process block attributes.
	 *
	 * @param array $attributes Raw block attributes.
	 * @return array Processed attributes.
	 */
	protected function extract_attributes( $attributes ) {
		$client_id = magazine_blocks_array_get( $attributes, 'clientId', '' );

		return array(
			// General attributes.
			'client_id'                      => $client_id,
			'class_name'                     => magazine_blocks_array_get( $attributes, 'className', '' ),
			'css_id'                         => magazine_blocks_array_get( $attributes, 'cssID', '' ),
			'post_count'                     => magazine_blocks_array_get( $attributes, 'postCount', '4' ),
			'post_title_markup'              => magazine_blocks_array_get( $attributes, 'postTitleMarkup', 'h6' ),
			'layout'                         => magazine_blocks_array_get( $attributes, 'layout', 'default' ),

			// Query parameters.
			'category'                       => magazine_blocks_array_get( $attributes, 'category', '' ),
			'tag'                            => magazine_blocks_array_get( $attributes, 'tag', '' ),
			'excluded_category'              => magazine_blocks_array_get( $attributes, 'excludedCategory', '' ),
			'excluded_tag'                   => magazine_blocks_array_get( $attributes, 'excludedTag', '' ),
			'order_by'                       => magazine_blocks_array_get( $attributes, 'orderBy', '' ),
			'order_type'                     => magazine_blocks_array_get( $attributes, 'orderType', '' ),
			'author'                         => magazine_blocks_array_get( $attributes, 'authorName', '' ),
			'offset'                         => magazine_blocks_array_get( $attributes, 'offset', 0 ),

			// Category settings.
			'enable_category'                => magazine_blocks_array_get( $attributes, 'enableCategory', '' ),

			// Meta settings.
			'meta_position'                  => magazine_blocks_array_get( $attributes, 'metaPosition', '' ),
			'enable_author'                  => magazine_blocks_array_get( $attributes, 'enableAuthor', '' ),
			'enable_date'                    => magazine_blocks_array_get( $attributes, 'enableDate', '' ),
			'meta_separator'                 => magazine_blocks_array_get( $attributes, 'separatorType', 'none' ),
			'enable_icon'                    => magazine_blocks_array_get( $attributes, 'enableIcon', '' ),
			'enable_excerpt'                 => magazine_blocks_array_get( $attributes, 'enableExcerpt', true ),
			'excerpt_limit'                  => magazine_blocks_array_get( $attributes, 'excerptLimit', 20 ),
			'enable_readmore'                => magazine_blocks_array_get( $attributes, 'enableReadMore', false ),
			'read_more_text'                 => magazine_blocks_array_get( $attributes, 'readMoreText', '' ),

			// Theme override.
			'enable_override_category_color' => get_theme_mod( 'colormag_enable_override_category_color', false ),
		);
	}

	/**
	 * Build latest posts query.
	 *
	 * @param array $attrs Processed attributes.
	 * @return WP_Query
	 */
	protected function build_latest_query( $attrs ) {
		$args = array(
			'posts_per_page'      => $attrs['post_count'],
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

		return $this->query_builder->build_query( $args );
	}

	/**
	 * Build popular posts query.
	 *
	 * @param array $attrs Processed attributes.
	 * @return WP_Query
	 */
	protected function build_popular_query( $attrs ) {
		$args = array(
			'posts_per_page' => $attrs['post_count'],
			'orderby'        => 'comment_count',
			'post_status'    => 'publish',
		);

		return $this->query_builder->build_query( $args );
	}

	/**
	 * Render the main block HTML structure.
	 *
	 * @param WP_Query $latest_query Latest posts query.
	 * @param WP_Query $popular_query Popular posts query.
	 * @param array    $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	protected function render_block( $latest_query, $popular_query, $attributes ) {
		$html = sprintf(
			'<div id="%s" class="mzb-tab-post mzb-tab-post-%s %s" data-active-tab="latest">',
			esc_attr( $attributes['css_id'] ),
			esc_attr( $attributes['client_id'] ),
			esc_attr( $attributes['class_name'] )
		);

		// Render tab controls.
		$html .= $this->render_tab_controls( $attributes['layout'] );

		// Render latest posts.
		$html .= $this->render_posts_container( $latest_query, $attributes, 'latest' );

		// Render popular posts.
		$html .= $this->render_posts_container( $popular_query, $attributes, 'popular' );

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render tab controls.
	 *
	 * @param string $layout Tab header preset layout.
	 * @return string Tab controls HTML.
	 */
	protected function render_tab_controls( $layout = 'default' ) {
		$classes = 'style1' === $layout ? 'mzb-tab-controls mzb-tab-layout-style1' : 'mzb-tab-controls';

		return '
			<div class="' . esc_attr( $classes ) . '">
				<div data-tab="latest" class="mzb-tab-title active">' . esc_html__( 'Latest', 'magazine-blocks' ) . '</div>
				<div data-tab="popular" class="mzb-tab-title">' . esc_html__( 'Popular', 'magazine-blocks' ) . '</div>
			</div>
		';
	}

	/**
	 * Render posts container.
	 *
	 * @param WP_Query $query WP_Query object.
	 * @param array    $attributes Block attributes.
	 * @param string   $tab_type Tab type (latest or popular).
	 * @return string Posts container HTML.
	 */
	protected function render_posts_container( $query, $attributes, $tab_type ) {
		$html = sprintf( '<div class="mzb-posts" data-posts="%s">', esc_attr( $tab_type ) );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$html .= $this->render_post( get_the_ID(), $attributes, $tab_type );
			}
			wp_reset_postdata();
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render individual post.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $attributes Block attributes.
	 * @param string $tab_type Tab type (latest or popular).
	 * @return string Post HTML.
	 */
	protected function render_post( $post_id, $attributes, $tab_type ) {
		// Determine position class.
		$src            = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id ) : '';
		$position_class = $src ? '' : 'no-thumbnail';

		$html = sprintf( '<div class="mzb-post %s">', esc_attr( $position_class ) );

		// Render featured image.
		if ( $src ) {
			$html .= $this->render_featured_image( $post_id );
		}

		// Render content.
		$html .= '<div class="mzb-post-content">';

		// Render category meta.
		if ( $attributes['enable_category'] ) {
			$html .= '<div class="mzb-post-meta">';
			$html .= $this->render_categories( $post_id, $attributes['enable_override_category_color'] );
			$html .= '</div>';
		}

		// Render top meta.
		if ( 'top' === $attributes['meta_position'] ) {
			$html .= $this->render_post_meta_info( $post_id, $attributes );
		}

		// Render title.
		$html .= $this->render_post_title( $post_id, $attributes['post_title_markup'] );

		// Render excerpt.
		$html .= $this->render_excerpt_and_read_more( $post_id, $attributes );

		// Render bottom meta.
		if ( 'bottom' === $attributes['meta_position'] ) {
			$html .= $this->render_post_meta_info( $post_id, $attributes );
		}

		$html .= '</div></div>';

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
		if ( ! $attributes['enable_author'] && ! $attributes['enable_date'] ) {
			return '';
		}

		$classes = array( 'mzb-post-entry-meta' );
		if ( $attributes['meta_separator'] && 'none' !== $attributes['meta_separator'] ) {
			$classes[] = 'mzb-meta-separator--' . $attributes['meta_separator'];
		}

		$html = sprintf( '<div class="%s">', esc_attr( implode( ' ', $classes ) ) );

		if ( $attributes['enable_author'] ) {
			$html .= $this->render_author( $post_id, $attributes['enable_icon'] );
		}

		if ( $attributes['enable_date'] ) {
			$html .= $this->render_date( $post_id, $attributes['enable_icon'] );
		}

		$html .= '</div>';

		return $html;
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
