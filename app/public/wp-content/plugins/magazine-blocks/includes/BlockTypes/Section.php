<?php
/**
 * Section block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\HasRender;

defined( 'ABSPATH' ) || exit;

/**
 * Section block.
 */
class Section extends Block {

	use HasRender;

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'section';
}
