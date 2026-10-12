<?php
namespace MagazineBlocks\Traits\Blocks;

/**
 * Render class trait.
 *
 * @package Magazine Blocks\Traits
 */
trait HasRender {

	/**
	 * Block attributes (set during render).
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Block content.
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * Block instance.
	 *
	 * @var \WP_Block
	 */
	protected $block;

	/**
	 * Default render callback.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block object.
	 * @return string
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		$this->attributes = $attributes;
		$this->block      = $block;
		$this->content    = $content;

		$content = apply_filters(
			"magazine_blocks_{$this->block_name}_content",
			$this->build_html( $this->content ),
			$this
		);

		return $content;
	}

	/**
	 * Default build_html. Child classes may override.
	 *
	 * @param string $content The content.
	 * @return string
	 */
	protected function build_html( $content ) {
		return $content;
	}
}
