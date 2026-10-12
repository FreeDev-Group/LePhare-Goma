<?php
namespace MagazineBlocks;

defined( 'ABSPATH' ) || exit;

class Functions {

	// Pagination
	public function pagination( $pages = '', $paginationNav, $range = 1 ) {

		$html      = '';
		$showitems = 3;
		$paged     = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );
		$paged     = $paged ? $paged : 1;
		if ( $pages == '' ) {
			global $wp_query;
			$pages = $wp_query->max_num_pages;
			if ( ! $pages ) {
				$pages = 1;
			}
		}
		$data = ( $paged >= 3 ? array( ( $paged - 1 ), $paged, $paged + 1 ) : array( 1, 2, 3 ) );

		if ( 1 != $pages ) {
			$html        .= '<ul class="mzb-pagination">';
			$display_none = 'style="display:none"';
			if ( $pages > 4 ) {
				$html .= '<li class="mzb-prev-page-numbers" ' . ( $paged == 1 ? $display_none : '' ) . '><a href="' . get_pagenum_link( $paged - 1 ) . '">' . ultimate_post()->svg_icon( 'leftAngle2' ) . ' ' . ( $paginationNav == 'textArrow' ? __( 'Previous', 'ultimate-post' ) : '' ) . '</a></li>';
			}
			if ( $pages > 4 ) {
				$html .= '<li class="mzb-first-pages" ' . ( $paged < 2 ? $display_none : '' ) . ' data-current="1"><a href="' . get_pagenum_link( 1 ) . '">1</a></li>';
			}
			if ( $pages > 4 ) {
				$html .= '<li class="mzb-first-dot" ' . ( $paged < 2 ? $display_none : '' ) . '><a href="#">...</a></li>';
			}
			foreach ( $data as $i ) {
				if ( $pages >= $i ) {
					$html .= ( $paged == $i ) ? '<li class="mzb-center-item pagination-active" data-current="' . $i . '"><a href="' . get_pagenum_link( $i ) . '">' . $i . '</a></li>' : '<li class="mzb-center-item" data-current="' . $i . '"><a href="' . get_pagenum_link( $i ) . '">' . $i . '</a></li>';
				}
			}
			if ( $pages > 4 ) {
				$html .= '<li class="mzb-last-dot" ' . ( $pages <= $paged + 1 ? $display_none : '' ) . '><a href="#">...</a></li>';
			}
			if ( $pages > 4 ) {
				$html .= '<li class="mzb-last-pages" ' . ( $pages <= $paged + 1 ? $display_none : '' ) . ' data-current="' . $pages . '"><a href="' . get_pagenum_link( $pages ) . '">' . $pages . '</a></li>';
			}
			if ( $paged != $pages ) {
				$html .= '<li class="mzb-next-page-numbers"><a href="' . get_pagenum_link( $paged + 1 ) . '">' . ( $paginationNav == 'textArrow' ? __( 'Next', 'ultimate-post' ) : '' ) . ultimate_post()->svg_icon( 'rightAngle2' ) . '</a></li>';
			}
			$html .= '</ul>';
		}
		return $html;
	}
}
