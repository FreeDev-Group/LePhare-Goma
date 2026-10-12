<?php
/**
 * News Ticker block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\PostRenderer;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * NewsTicker block.
 */
class NewsTicker extends Block {

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'news-ticker';

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		$attrs = $this->extract_attributes( $attributes );
		$query = $this->build_query( $attrs );

		$html = '';
		if ( $query->have_posts() ) {
			$html .= '<div class="mzb-news-ticker mzb-news-ticker-' . esc_attr( $attrs['client_id'] ) . '"';
			$html .= ' data-ticker-effect="' . esc_attr( $attrs['ticker_effect'] ) . '"';
			$html .= ' data-ticker-direction="' . esc_attr( $attrs['ticker_direction'] ) . '"';
			$html .= ' data-delay-timer="' . esc_attr( $attrs['delay_timer'] ) . '"';
			$html .= ' data-auto-play="' . esc_attr( $attrs['auto_play'] ? 'true' : 'false' ) . '"';
			$html .= ' data-scroll-speed="' . esc_attr( $attrs['scroll_speed'] ) . '"';
			$html .= ' data-stop-on-hover="' . esc_attr( $attrs['stop_on_hover'] ? 'true' : 'false' ) . '"';
			$html .= ' data-initialized="false"';
			$html .= '>';

			if ( isset( $attrs['enable_icon'] ) && $attrs['enable_icon'] ) {
				$html .= '<span class="mzb-weather">' . wp_kses( $attrs['get_icon'], magazine_blocks_get_allowed_svg_elements() ) . '</span>';
			}

			if ( isset( $attrs['enable_label'] ) && $attrs['enable_label'] ) {
				$html .= '<span class="mzb-heading">' . esc_html( $attrs['label'] ) . '</span>';
			}

			$html .= '<div class="mzb-news-ticker-box' . ( $attrs['arrows'] ? ' mzb-news-ticker-arrows' : '' ) . '" data-total-posts="' . esc_attr( $query->post_count ) . '">';

			if ( $attrs['arrows'] ) {
				$html .= $this->render_nav_button( 'prev' );
			}

			$html .= '<ul class="mzb-news-ticker-list">';
			while ( $query->have_posts() ) {
				$query->the_post();
				$id    = get_post_thumbnail_id();
				$src   = wp_get_attachment_image_src( $id );
				$src   = has_post_thumbnail( get_the_ID() ) ? get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' ) : '';
				$image = $src ? '<div class="mzb-featured-image"><a href="' . esc_url( get_the_permalink() ) . '"alt="' . esc_attr( get_the_title() ) . '"/><img src="' . esc_url( $src ) . '" alt="' . esc_attr( get_the_title() ) . '"/> </a></div>' : '';
				// Limit title to 60 characters.
				$title_text      = get_the_title();
				$truncated_title = strlen( $title_text ) > 60 ? substr( $title_text, 0, 60 ) . '...' : $title_text;

				$title = '<li><a href="' . esc_url( get_the_permalink() ) . '">' . esc_html( $truncated_title ) . '</a></li>';
				$html .= $title;
			}
			$html .= '</ul>';

			if ( $attrs['arrows'] ) {
				$html .= $this->render_nav_button( 'next' );
			}

			$html .= '</div>'; // .mzb-news-ticker-box
			$html .= '</div>'; // .mzb-news-ticker
			wp_reset_postdata();
		}

		return $html;
	}

	/**
	 * Extract attributes from block settings.
	 *
	 * @param array $attributes Original block attributes.
	 * @return array Processed attributes.
	 */
	protected function extract_attributes( $attributes ) {
		$category = magazine_blocks_array_get( $attributes, 'category', '' );
		$icon     = magazine_blocks_array_get( $attributes, 'icon', '' );

		return array(
			'client_id'        => magazine_blocks_array_get( $attributes, 'clientId', '' ),
			'category'         => 'all' === $category ? null : (int) $category,
			'post_count'       => magazine_blocks_array_get( $attributes, 'postCount', 5 ),
			'enable_label'     => magazine_blocks_array_get( $attributes, 'enableLabel', true ),
			'label'            => magazine_blocks_array_get( $attributes, 'label', 'Latest' ),
			'arrows'           => magazine_blocks_array_get( $attributes, 'enableArrows', false ),
			'enable_icon'      => magazine_blocks_array_get( $attributes, 'enableIcon', true ),
			'get_icon'         => magazine_blocks_get_icon( $icon, false ),
			// Get ticker effect settings.
			'ticker_effect'    => magazine_blocks_array_get( $attributes, 'tickerEffect', 'fade' ),
			'ticker_direction' => magazine_blocks_array_get( $attributes, 'tickerDirection', 'ltr' ),
			'delay_timer'      => magazine_blocks_array_get( $attributes, 'delayTimer', 4000 ),
			'auto_play'        => magazine_blocks_array_get( $attributes, 'autoPlay', true ),
			'scroll_speed'     => magazine_blocks_array_get( $attributes, 'scrollSpeed', 2 ),
			'stop_on_hover'    => magazine_blocks_array_get( $attributes, 'stopOnHover', true ),
		);
	}

	/**
	 * Build WP_Query object.
	 *
	 * @param array $attrs Processed attributes.
	 * @return WP_Query
	 */
	protected function build_query( $attrs ) {
		$args = array(
			'posts_per_page' => $attrs['post_count'],
			'post_status'    => 'publish',
			'category__in'   => $attrs['category'],
		);

		/**
		 * Filters the WP_Query args for the News Ticker block.
		 *
		 * Allows overriding the post count above the editor's slider limit.
		 *
		 * @param array $args  WP_Query arguments.
		 * @param array $attrs Processed block attributes.
		 */
		$args = apply_filters( 'magazine_blocks_news_ticker_query_args', $args, $attrs );

		return $this->query_builder->build_query( $args, array( 'exclude_template' => true ) );
	}

	/**
	 * Render navigation button HTML.
	 *
	 * @param string $direction "prev" or "next".
	 * @return string HTML output.
	 */
	protected function render_nav_button( $direction ) {
		$icon_path = 'prev' === $direction
			? 'M16.3 2l-10 10.1 10 10 1.4-1.4-8.5-8.6 8.6-8.7L16.3 2z'
			: 'M6.6 3.4l8.7 8.6-8.7 8.7L8 22.1 18 12 8 2 6.6 3.4z';

		return sprintf(
			'<a href="#" class="mzb-news-ticker-nav-btn %1$s" data-action="%1$s" aria-label="%2$s">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="%3$s"/></svg>
			</a>',
			esc_attr( $direction ),
			ucfirst( $direction ),
			$icon_path
		);
	}
}
