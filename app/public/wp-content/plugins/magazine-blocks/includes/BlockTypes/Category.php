<?php

/**
 * Category.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;

defined( 'ABSPATH' ) || exit;

/**
 * Category block.
 */
class Category extends Block {

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'category';

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {

		$client_id = magazine_blocks_array_get( $attributes, 'clientId', '' );

		$current_post_id = get_the_ID();

		$selected_category = isset( $attributes['category'] ) ? (int) $attributes['category'] : 0;

		$assigned_categories = array();

		if ( $current_post_id ) {
			$assigned_categories = wp_get_post_categories( $current_post_id, array( 'fields' => 'all' ) );
		}

		if ( ! $selected_category && ! empty( $assigned_categories ) ) {
			$selected_category = $assigned_categories[0]->term_id;
		}

		$category_name = '';
		if ( $selected_category ) {
			$term = get_term( $selected_category, 'category' );
			if ( $term && ! is_wp_error( $term ) ) {
				$category_name = $term->name;
			}
		}

		ob_start();
		?>
		<div class="mzb-category mzb-category-<?php echo esc_attr( $client_id ); ?>">
			<div class="mzb-category-list">
			<?php if ( $category_name ) : ?>
				<span class="mzb-category-item">
					<?php
					/* translators: %s: Category name. */
					echo esc_html( sprintf( __( '%s', 'magazine-blocks' ), $category_name ) ); //phpcs:ignore.
					?>
				</span>
			<?php endif; ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}
}
