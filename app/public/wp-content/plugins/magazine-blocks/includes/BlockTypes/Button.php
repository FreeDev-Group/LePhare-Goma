<?php
/**
 * Button block.
 *
 * @package BlockArt
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\HasRender;

defined( 'ABSPATH' ) || exit;

/**
 * Button block class.
 */
class Button extends Block {

	use HasRender;

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'button';
}
