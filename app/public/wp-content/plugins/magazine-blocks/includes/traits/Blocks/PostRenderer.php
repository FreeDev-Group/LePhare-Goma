<?php

namespace MagazineBlocks\Traits\Blocks;

/**
 * Post Render class trait.
 *
 * @since x.x.x
 *
 * @package Magazine Blocks\Traits
 */
trait PostRenderer {


	/**
	 * Render post title.
	 *
	 * @param int   $post_id The post id.
	 * @param mixed $markup The markup.
	 */
	protected function render_post_title( $post_id, $markup = 'h6' ) {
		$markup = magazine_blocks_sanitize_html_tag( $markup, 'h3' );
		$title  = get_the_title( $post_id );

		if ( empty( $title ) ) {
			$title = __( '(Untitled)', 'magazine-blocks' );
		}

		return sprintf(
			'<%s class="mzb-post-title"><a href="%s">%s</a></%s>',
			$markup,
			esc_url( get_the_permalink( $post_id ) ),
			esc_html( $title ),
			$markup
		);
	}

	/**
	 * Render comments.
	 *
	 * @param int $post_id The post id.
	 */
	protected function render_comments( $post_id ) {
		$comment_count = get_comments_number( $post_id );
		$comment_link  = get_comments_link( $post_id );

		return sprintf(
			'<span class="comments-link">
                <svg class="mzb-icon mzb-icon--comment" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M12 4c-5.19 0-9 3.33-9 7 0 1.756.84 3.401 2.308 4.671l.412.358-.46 3.223 3.456-1.728.367.098c.913.245 1.893.378 2.917.378 5.19 0 9-3.33 9-7s-3.81-7-9-7zM1 11c0-5.167 5.145-9 11-9s11 3.833 11 9-5.145 9-11 9c-1.06 0-2.087-.122-3.06-.352l-6.2 3.1.849-5.94C1.999 15.266 1 13.246 1 11z"></path>
                </svg>
                <a href="%s">%s</a>
            </span>',
			esc_url( $comment_link ),
			$comment_count
		);
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

		// Only override with the theme's per-category color when it can actually supply one,
		// so the badge never ends up with a class but no matching color (inline style guarantees
		// it wins over the block's own default/custom color on both the editor and the frontend).
		if ( $enable_override_color && function_exists( 'colormag_category_color' ) ) {
			$categories = preg_replace_callback(
				'/<a(.+?)href="([^"]+)"(.+?)>([^<]+)<\/a>/',
				function ( $matches ) {
					$cat_id = get_cat_ID( $matches[4] );
					$color  = colormag_category_color( $cat_id );

					if ( ! $color ) {
						return $matches[0];
					}

					return sprintf(
						'<a%shref="%s"%s class="category-link category-link-%s" style="color: %s;">%s</a>',
						$matches[1],
						$matches[2],
						$matches[3],
						$cat_id,
						esc_attr( $color ),
						$matches[4]
					);
				},
				$categories
			);
		}

		return '<span class="mzb-post-categories">' . $categories . '</span>';
	}

	/**
	 * Render date.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $enable_icon Whether to show icon.
	 * @return string Date HTML.
	 */
	protected function render_date( $post_id, $enable_icon ) {
		$date = '';

		if ( $enable_icon ) {
			$date = '<svg class="mzb-icon mzb-icon--calender" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14">
				<path d="M1.892 12.929h10.214V5.5H1.892v7.429zm2.786-8.822v-2.09a.226.226 0 00-.066-.166.226.226 0 00-.166-.065H3.98a.226.226 0 00-.167.065.226.226 0 00-.065.167v2.09c0 .067.022.122.065.166.044.044.1.065.167.065h.465a.226.226 0 00.166-.065.226.226 0 00.066-.167zm5.571 0v-2.09a.226.226 0 00-.065-.166.226.226 0 00-.167-.065h-.464a.226.226 0 00-.167.065.226.226 0 00-.065.167v2.09c0 .067.021.122.065.166.043.044.099.065.167.065h.464a.226.226 0 00.167-.065.226.226 0 00.065-.167zm2.786-.464v9.286c0 .251-.092.469-.276.652a.892.892 0 01-.653.276H1.892a.892.892 0 01-.653-.275.892.892 0 01-.276-.653V3.643c0-.252.092-.47.276-.653a.892.892 0 01.653-.276h.929v-.696c0-.32.113-.593.34-.82.228-.227.501-.34.82-.34h.465c.319 0 .592.113.82.34.227.227.34.5.34.82v.696h2.786v-.696c0-.32.114-.593.34-.82.228-.227.501-.34.82-.34h.465c.32 0 .592.113.82.34.227.227.34.5.34.82v.696h.93c.25 0 .468.092.652.276a.892.892 0 01.276.653z" />
			</svg>';
		}

		$date .= sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_the_permalink( $post_id ) ),
			esc_html( get_the_date( '', $post_id ) )
		);

		return '<span class="mzb-post-date">' . $date . '</span>';
	}

	/**
	 * Render read time.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $enable_icon Whether to show icon.
	 * @return string Read time HTML.
	 */
	protected function render_read_time( $post_id, $enable_icon ) {
		$read_time = '';
		if ( $enable_icon ) {
			$read_time = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18ZM1 12C1 5.925 5.925 1 12 1s11 4.925 11 11-4.925 11-11 11S1 18.075 1 12Z" clip-rule="evenodd"/>
                <path fill-rule="evenodd" d="M12 5a1 1 0 0 1 1 1v5.382l3.447 1.724a1 1 0 1 1-.894 1.788l-4-2A1 1 0 0 1 11 12V6a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
            </svg>';
		}
		$read_time .= sprintf(
			'<span>%s min read</span>',
			magazine_calculate_read_time( $post_id )
		);

		return '<span class="mzb-post-read-time">' . $read_time . '</span>';
	}

	/**
	 * Render author.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $enable_icon Whether to show icon.
	 * @return string Author HTML.
	 */
	protected function render_author( $post_id, $enable_icon ) {
		$author_id   = (int) get_post_field( 'post_author', $post_id );
		$author_name = get_the_author_meta( 'display_name', $author_id );
		$author_url  = get_author_posts_url( $author_id );

		$author = '';

		if ( $enable_icon ) {
			$author .= sprintf(
				'<img class="post-author-image" src="%s" alt="%s"/>',
				esc_url( get_avatar_url( $author_id ) ),
				esc_attr( $author_name )
			);
		}

		$author .= sprintf(
			'<a href="%s">%s</a>',
			esc_url( $author_url ),
			esc_html( $author_name )
		);

		return '<span class="mzb-post-author">' . $author . '</span>';
	}

	/**
	 * Render excerpt and read more content.
	 *
	 * For a post without a manual excerpt, get_the_excerpt() trims post_content to whatever the active
	 * theme's `excerpt_length` filter returns (e.g. ColorMag hardcodes 20 words) before this method ever
	 * sees it, so the block's own excerpt_limit is silently capped by the theme. PHP_INT_MAX priority
	 * forces our override to run last regardless of what priority the theme registered at, and the
	 * filter is removed immediately after so it never leaks into posts or blocks rendered afterward.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $attributes Block attributes.
	 * @return string Excerpt and read more HTML.
	 */
	protected function render_excerpt_and_read_more( $post_id, $attributes ) {
		$html = '<div class="mzb-entry-content">';

		if ( $attributes['enable_excerpt'] ) {
			$excerpt_length_filter = function () use ( $attributes ) {
				return $attributes['excerpt_limit'];
			};

			add_filter( 'excerpt_length', $excerpt_length_filter, PHP_INT_MAX );
			// wp_trim_words() already runs wp_strip_all_tags() internally, so the excerpt is plain text by this point.
			$excerpt = wp_trim_words( get_the_excerpt( $post_id ), $attributes['excerpt_limit'] );
			remove_filter( 'excerpt_length', $excerpt_length_filter, PHP_INT_MAX );

			$html .= sprintf( '<div class="mzb-entry-summary"><p>%s</p></div>', esc_html( $excerpt ) );
		}

		if ( $attributes['enable_readmore'] ) {
			$icon = isset( $attributes['enable_read_more_icon'] ) ?
				wp_kses( magazine_blocks_get_icon( $attributes['read_more_icon'], false ), magazine_blocks_get_allowed_svg_elements() ) : '';

			$html .= sprintf(
				'<div class="mzb-read-more"><a href="%s">%s%s</a></div>',
				esc_url( get_the_permalink( $post_id ) ),
				esc_html( $attributes['read_more_text'] ),
				$icon
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render featured image.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $hover_animation Hover animation class.
	 * @return string Featured image HTML.
	 */
	protected function render_featured_image( $post_id, $hover_animation = '' ) {
		$src = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id ) : '';
		if ( ! $src ) {
			return '';
		}

		return sprintf(
			'<div class="mzb-featured-image %s"><a href="%s" alt="%s"/><img src="%s" alt="%s"/></a></div>',
			esc_attr( $hover_animation ),
			esc_url( get_the_permalink( $post_id ) ),
			esc_attr( get_the_title( $post_id ) ),
			esc_url( $src ),
			esc_attr( get_the_title( $post_id ) )
		);
	}

	/**
	 * Render the heading section.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $position The view more link position.
	 * @return string Heading section HTML.
	 */
	protected function render_heading( $attributes, $position = '' ) {
		$heading_style  = $attributes['heading_style'];
		$view_more_link = $this->render_view_more_link( $attributes, $position );

		$html = sprintf(
			'<div class="mzb-post-heading mzb-%s mzb-%s">',
			esc_attr( $attributes['heading_layout'] ),
			esc_attr( $heading_style )
		);

		if ( $attributes['enable_heading'] ) {
			$html .= sprintf(
				'<h2 class="mzb-heading-text">%s</h2>',
				esc_html( $attributes['label'] )
			);
		}

		$html .= $view_more_link;
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render view more link.
	 *
	 * @param mixed  $attributes The attributes.
	 * @param string $position The position.
	 */
	protected function render_view_more_link( $attributes, $position = '' ) {
		if ( empty( $attributes['enable_view_more'] ) || 'none' === $position ) {
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
			$position ? ' mzb-view-more--' . $position : '',
			esc_url( $href ),
			$target,
			$rel,
			esc_html( $attributes['view_more_text'] ),
			$icon
		);
	}

	/**
	 * Render view count.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $enable_icon Whether to show icon.
	 * @return string View count HTML.
	 */
	protected function render_view_count( $post_id, $enable_icon ) {
		$view       = get_post_meta( $post_id, '_mzb_post_view_count', true );
		$view_count = '';
		if ( $enable_icon ) {
			$view_count = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 17.9c-4.2 0-7.9-2.1-9.9-5.5-.2-.3-.2-.6 0-.9C4.1 8.2 7.8 6 12 6s7.9 2.1 9.9 5.5c.2.3.2.6 0 .9-2 3.4-5.7 5.5-9.9 5.5zM3.9 12c1.6 2.6 4.8 4.2 8.1 4.2s6.4-1.6 8.1-4.2c-1.6-2.6-4.7-4.2-8.1-4.2S5.6 9.4 3.9 12zm8.1 3.3c-1.8 0-3.3-1.5-3.3-3.3s1.5-3.3 3.3-3.3 3.3 1.5 3.3 3.3-1.5 3.3-3.3 3.3zm0-4.9c-.9 0-1.6.8-1.6 1.6 0 .9.8 1.6 1.6 1.6s1.6-.8 1.6-1.6c0-.9-.7-1.6-1.6-1.6z" />
            </svg>';
		}
		$view_count .= '<span>' . ( empty( $view ) ? 0 : $view ) . ' views</span>';

		return '<span class="mzb-post-view-count">' . $view_count . '</span>';
	}
}
