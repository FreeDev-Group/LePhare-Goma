<?php
/**
 * Breadcrumbs block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\Attributes;
use MagazineBlocks\Traits\Blocks\HasClassNames;
use MagazineBlocks\Traits\Blocks\HasHtmlAttributes;
use MagazineBlocks\Traits\Blocks\HasRender;

/**
 * Breadcrumbs block.
 */
class Breadcrumbs extends Block {

	use Attributes;
	use HasRender;
	use HasClassNames;
	use HasHtmlAttributes;

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'breadcrumbs';

	/**
	 * Get html attrs.
	 *
	 * @return array
	 */
	protected function get_default_html_attrs() {
		return array(
			'id'               => $this->get_attribute( 'cssID' ),
			'class'            => $this->cn(
				"mzb-breadcrumbs mzb-breadcrumbs-{$this->get_attribute('clientId', '', true)}",
				$this->get_attribute( 'className', '' )
			),
			'data-breadcrumbs' => '_magazine_blocks_breadcrumbs_' . $this->get_attribute( 'clientId' ),
		);
	}

	/**
	 * Build html.
	 *
	 * @param [type] $content The content.
	 */
	public function build_html( $content ) {
		if ( magazine_blocks_is_rest_request() ) {
			return $content;
		}

		$data = array(
			'separator'    => $this->get_attribute( 'separator', '>' ),
			'homeLabel'    => $this->get_attribute( 'homeLabel', 'Home' ),
			'showHomeLink' => $this->get_attribute( 'showHomeLink', true ),

		);

		$breadcrumbs_html = $this->generate_dynamic_breadcrumbs( $data );

		ob_start();
		?>
		<div <?php $this->build_html_attributes( true ); ?>>
			<nav aria-label="Breadcrumbs" class="mzb-breadcrumb">
				<div style="display: flex; align-items: center; margin: 10px 0;">
				<?php echo wp_kses_post( $breadcrumbs_html ); ?>
				</div>
			</nav>
		</div>
		<?php

		return str_replace( '{{CONTENT}}', $content, ob_get_clean() );
	}

	/**
	 * Generate Dynamic Breadcrumbs.
	 *
	 * @param array $data Breadcrumb settings.
	 * @return string Generated breadcrumbs HTML.
	 */
	private function generate_dynamic_breadcrumbs( $data ) {
		$breadcrumbs = array();

		if ( $data['showHomeLink'] ) {
			$breadcrumbs[] = sprintf(
				'<a href="%s" class="mzb-breadcrumb-link">%s</a>',
				esc_url( home_url( '/' ) ),
				esc_html( $data['homeLabel'] )
			);
		}

		if ( is_singular( 'post' ) ) {
			$categories = get_the_category();
			if ( ! empty( $categories ) ) {
				$breadcrumbs[] = sprintf(
					'<a href="%s" class="mzb-breadcrumb-link">%s</a>',
					esc_url( get_category_link( $categories[0]->term_id ) ),
					esc_html( $categories[0]->name )
				);
			}
			$breadcrumbs[] = sprintf( '<a href="%s" class="mzb-breadcrumb-link">%s</a>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
		} elseif ( is_page() ) {
			$post      = get_post();
			$ancestors = get_post_ancestors( $post );
			$ancestors = array_reverse( $ancestors );

			foreach ( $ancestors as $ancestor ) {
				$breadcrumbs[] = sprintf(
					'<a href="%s" class="mzb-breadcrumb-link">%s</a>',
					esc_url( get_permalink( $ancestor ) ),
					esc_html( get_the_title( $ancestor ) )
				);
			}
			$breadcrumbs[] = sprintf( '<a href="%s" class="mzb-breadcrumb-link">%s</a>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			if ( function_exists( 'wc_get_page_id' ) ) {
				$shop_page_id = wc_get_page_id( 'shop' );
				if ( $shop_page_id ) {
					$breadcrumbs[] = sprintf(
						'<a href="%s" class="mzb-breadcrumb-link">%s</a>',
						esc_url( get_permalink( $shop_page_id ) ),
						esc_html( get_the_title( $shop_page_id ) )
					);
				}
			}
			$breadcrumbs[] = sprintf( '<a href="%s" class="mzb-breadcrumb-link">%s</a>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$breadcrumbs[] = sprintf( '<a href="%s" class="mzb-breadcrumb-link">%s</a>', esc_url( get_permalink() ), esc_html( single_cat_title( '', false ) ) );
		} elseif ( is_archive() ) {
			$breadcrumbs[] = sprintf( '<a href="%s" class="mzb-breadcrumb-link">%s</a>', esc_url( get_permalink() ), esc_html( get_the_archive_title() ) );
		} elseif ( is_search() ) {
			$breadcrumbs[] = sprintf( '<a href="%s" class="mzb-breadcrumb-link">%s</a>', esc_url( get_permalink() ), esc_html( get_search_query() ) );
		} elseif ( is_404() ) {
			$breadcrumbs[] = sprintf( '<a href="%s" class="mzb-breadcrumb-link">%s</a>', esc_url( get_permalink() ), esc_html( '404' ) );
		} elseif ( is_home() ) {
			$breadcrumbs[] = sprintf( '<a href="%s" class="mzb-breadcrumb-link">%s</a>', esc_url( get_permalink() ), esc_html( 'Home' ) );
		} elseif ( is_single() ) {
			$post_type = get_post_type();
			if ( $post_type ) {
				$post_type_obj = get_post_type_object( $post_type );
				if ( $post_type_obj ) {
					$breadcrumbs[] = sprintf(
						'<a href="%s" class="mzb-breadcrumb-link">%s</a>',
						esc_url( get_post_type_archive_link( $post_type ) ),
						esc_html( $post_type_obj->labels->singular_name )
					);
				}
			}
			$breadcrumbs[] = sprintf( '<a href="%s" class="mzb-breadcrumb-link">%s</a>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
		}

		$separator = sprintf(
			'<span class="mzb-breadcrumb-separator">%s</span>',
			esc_html( $data['separator'] )
		);

		return implode( $separator, $breadcrumbs );
	}
}
