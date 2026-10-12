<?php
/**
 * Archive.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;

defined( 'ABSPATH' ) || exit;

/**
 * Heading block.
 */
class Archive extends Block {

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'archive';
	/**
	 * Render callback.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block object.
	 *
	 * @return string
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		// Check if we have a queried object (archive page).
		$queried_object = get_queried_object();
		$client_id      = magazine_blocks_array_get( $attributes, 'clientId', '' );

		// Start output div.
		$html = '<div class="mzb-archive mzb-archive-' . esc_attr( $client_id ) . '">';

		// Generate appropriate heading based on archive type.
		if ( is_search() ) {
			// For search results.
			$search_query = get_search_query();
			$html        .= sprintf( '<h2>%s "%s"</h2>', esc_html__( 'Search Results for:', 'magazine-blocks' ), esc_html( $search_query ) );
		} elseif ( is_tax() || is_category() || is_tag() ) { // Check if we're on a taxonomy archive page.
			// Get the taxonomy name.
			$taxonomy = get_taxonomy( $queried_object->taxonomy );

			if ( $taxonomy ) {
				// Get the taxonomy label.
				$taxonomy_name = $taxonomy->labels->singular_name;
				// Get the term name.
				$term_name = $queried_object->name;

				$html .= sprintf( '<h2>%s</h2>', esc_html( $term_name ) );
			}
		} elseif ( is_author() ) {
			// For author archives.
			$html .= '<h2>' . esc_html( get_the_author() ) . '</h2>';
		} elseif ( is_date() ) {
			// For date archives.
			$html .= '<h2>' . esc_html( get_the_archive_title() ) . '</h2>';
		} else {
			// For other archive types.
			$html .= '<h2>' . esc_html( get_the_archive_title() ) . '</h2>';
		}

		// Close the div.
		$html .= '</div>';

		return $html;
	}
}
