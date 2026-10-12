<?php

/**
 * RenderableInterface block.
 *
 * @since x.x.x
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for blocks that can render HTML output.
 *
 * @since x.x.x
 * @package Magazine Blocks
 */
interface RenderableInterface {

	/**
	 * Render the block's HTML output.
	 *
	 * @param array       $attributes Block attributes.
	 * @param string      $content    Inner block content for blocks.
	 * @param object|null $block      The block instance.
	 *
	 * @return string The rendered HTML markup for frontend display.
	 * @since x.x.x
	 */
	public function render( $attributes = array(), $content = '', $block = null );
}
