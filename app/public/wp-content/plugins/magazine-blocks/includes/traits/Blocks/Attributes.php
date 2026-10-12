<?php
namespace MagazineBlocks\Traits\Blocks;

/**
 * Attributes class trait.
 *
 * @package Magazine Blocks\Traits
 */
trait Attributes {

	/**
	 * Block attributes (set during render).
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Get all attributes.
	 *
	 * @return array
	 */
	public function get_attributes() {
		return $this->attributes ?? array();
	}

	/**
	 * Get a single attribute safely.
	 *
	 * @param string  $attribute_key Key to fetch.
	 * @param mixed   $default_value Default if not found.
	 * @param boolean $sanitize      Whether to sanitize for CSS use.
	 *
	 * @return mixed
	 */
	public function get_attribute( $attribute_key, $default_value = null, $sanitize = false ) {
		$attributes = $this->get_attributes();
		$attribute  = magazine_blocks_array_get( $attributes, $attribute_key, $default_value );

		return $sanitize ? preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $attribute ) : $attribute;
	}
}
