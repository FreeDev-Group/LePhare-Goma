<?php

/**
 * Post Title Block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;

defined( 'ABSPATH' ) || exit;

/**
 * Post Title block class.
 */
class PostTitle extends Block {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'post-title';

	/**
	 * Render callback for the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block instance.
	 * @return string
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return '';
		}

		$title = get_the_title( $post_id );

		$enable_excerpt = ! empty( $attributes['enableExcerpt'] );
		$excerpt        = $enable_excerpt ? get_the_excerpt( $post_id ) : '';

		// Extract attributes.
		$client_id    = $attributes['clientId'] ?? '';
		$hide_desktop = ! empty( $attributes['hideOnDesktop'] );
		$markup       = $attributes['markup'] ?? 'h2';

		// Construct class list.
		$class_names = array(
			'mzb-post-title',
			$client_id ? "mzb-post-title-{$client_id}" : '',
		);

		if ( $hide_desktop ) {
			$class_names[] = 'magazine-blocks-hide-on-desktop';
		}

		$class_names = array_filter( $class_names );
		$classes     = implode( ' ', array_map( 'sanitize_html_class', $class_names ) );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>">
			<<?php echo esc_html( $markup ); ?> class="mzb-post-title__title"><?php echo esc_html( $title ); ?></<?php echo esc_html( $markup ); ?>>
			<?php if ( $enable_excerpt && $excerpt ) : ?>
				<p class="mzb-post-title__excerpt"><?php echo esc_html( $excerpt ); ?></p>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}
}
