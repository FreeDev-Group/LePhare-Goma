<?php
/**
 * Paragraph block.
 *
 * @package MagazineBlocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\HasRender;

defined( 'ABSPATH' ) || exit;

/**
 * Paragraph block.
 */
class Paragraph extends Block {

	use HasRender;

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'paragraph';
}
