<?php
/**
 * Post Meta.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;

defined( 'ABSPATH' ) || exit;

/**
 * Heading block.
 */
class PostMeta extends Block {

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'post-meta';
	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		global $post;
		if ( ! in_array( get_post_type( $post ), array( 'post', 'page' ), true ) ) {
			return '';
		}

		$client_id           = magazine_blocks_array_get( $attributes, 'clientId', '' );
		$enable_author       = magazine_blocks_array_get( $attributes, 'enableAuthor', true );
		$enable_comment      = magazine_blocks_array_get( $attributes, 'enableComment', true );
		$enable_date         = magazine_blocks_array_get( $attributes, 'enableDate', true );
		$enable_author_icon  = magazine_blocks_array_get( $attributes, 'enableAuthorIcon', true );
		$enable_date_icon    = magazine_blocks_array_get( $attributes, 'enableDateIcon', true );
		$enable_comment_icon = magazine_blocks_array_get( $attributes, 'enableCommentIcon', true );
		$author              = get_the_author();
		$date                = get_the_date( 'F j, Y' );
		$comments            = get_comments_number();
		$separator_type      = isset( $attributes['separatorType'] ) ? $attributes['separatorType'] : 'dash';
		$separator_map       = array(
			'dash'  => '—',
			'dot'   => '·',
			'pipe'  => '|',
			'slash' => '/',
			'none'  => '',
		);
		$separator           = isset( $separator_map[ $separator_type ] ) ? $separator_map[ $separator_type ] : '—';

		$comments_text = sprintf( _n( '%s Comment', '%s Comments', $comments, 'magazine-blocks' ), number_format_i18n( $comments ) );

		$html  = '<div class="mzb-post-meta mzb-post-meta-' . esc_attr( $client_id ) . '">';
		$html .= '<div class="mzb-post-meta-content">';

		// Author.
		if ( $enable_author ) {
			$html .= '<span class="mzb-post-meta-author">';
			if ( $enable_author_icon ) {
				$author_id = get_the_author_meta( 'ID' );
				$avatar    = get_avatar( $author_id, 18 );
				if ( $avatar ) {
					$html .= $avatar;
				} else {
					$html .= '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm4-3a4 4 0 1 1-8 0 4 4 0 0 1 8 0z" /><path d="M14 14s-1-1.5-6-1.5S2 14 2 14V13a6 6 0 1 1 12 0v1z" /></svg>';
				}
			}
			$html .= '<span>' . esc_html( $author ) . '</span>';
			$html .= '</span>';
		}

		if ( $enable_date ) {
			if ( $separator ) {
				$html .= ' <span class="mzb-post-meta-separator">' . esc_html( $separator ) . '</span> ';
			}

			// Date.
			$html .= '<span class="mzb-post-meta-date">';
			if ( $enable_date_icon ) {
				$html .= '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 3v1h14V3a1 1 0 0 0-1-1h-1v.5a.5.5 0 0 1-1 0V2H4v.5a.5.5 0 0 1-1 0V2H2a1 1 0 0 0-1 1zm14 2H1v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5z" /></svg>';
			}
			$html .= '<span>' . esc_html( $date ) . '</span>';
			$html .= '</span>';
		}

		if ( $enable_comment ) {
			if ( $separator ) {
				$html .= ' <span class="mzb-post-meta-separator">' . esc_html( $separator ) . '</span> ';
			}

			// Comments.
			$html .= '<span class="mzb-post-meta-comment">';
			if ( $enable_comment_icon ) {
				$html .= '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3a5 5 0 0 0-4.546 2.914A.5.5 0 0 0 3.5 7h9a.5.5 0 0 0 .046-.086A5 5 0 0 0 8 3zm-7 5a7 7 0 1 1 14 0A7 7 0 0 1 1 8zm7 6a6.978 6.978 0 0 1-4.546-1.914A.5.5 0 0 1 3.5 13h9a.5.5 0 0 1 .046-.086A6.978 6.978 0 0 1 8 14z" /></svg>';
			}
			$html .= '<span>' . esc_html( $comments_text ) . '</span>';
			$html .= '</span>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
}
