<?php

/**
 * Post Image Block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;

defined( 'ABSPATH' ) || exit;

/**
 * Post Image block class.
 */
class PostImage extends Block {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'post-image';

	/**
	 * Render callback for the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block instance.
	 * @return string
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		$post_id  = get_the_ID();
		$image_id = get_post_thumbnail_id( $post_id );

		if ( ! $image_id ) {
			return '<p>' . esc_html__( 'No featured image set.', 'magazine-blocks' ) . '</p>';
		}

		// Extract attributes.
		$client_id    = isset( $attributes['clientId'] ) ? $attributes['clientId'] : '';
		$hide_desktop = ! empty( $attributes['hideOnDesktop'] );

		// Construct class list.
		$class_names = array(
			'mzb-post-image',
			"mzb-post-image-{$client_id}",
		);

		if ( $hide_desktop ) {
			$class_names[] = 'magazine-blocks-hide-on-desktop';
		}

		$classes = implode( ' ', array_map( 'sanitize_html_class', $class_names ) );

		// Get image HTML.
		$image_html = wp_get_attachment_image(
			$image_id,
			'large',
			false,
			array(
				'alt' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : esc_attr__( 'Featured image', 'magazine-blocks' ),
			)
		);

		return sprintf(
			'<div class="%s">%s</div>',
			esc_attr( $classes ),
			$image_html
		);
	}
}
