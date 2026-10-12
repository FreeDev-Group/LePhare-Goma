<?php
namespace MagazineBlocks\Traits\Blocks;

/**
 * HasAttributes class trait.
 *
 * @package Magazine Blocks\Traits
 */
trait HasAttributes {
	/**
	 * Attributes.
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Get attribute.
	 *
	 * @param string  $attribute_key The attribute key.
	 * @param mixed   $default_value The default value.
	 * @param boolean $sanitize The do sanitize or not.
	 * @return mixed
	 */
	public function get_attribute( $attribute_key, $default_value = null, $sanitize = false ) {
		$attribute = magazine_blocks_array_get( $this->attributes, $attribute_key, $default_value );
		return $sanitize ? preg_replace( '/[^a-zA-Z0-9_-]/', '', $attribute ) : $attribute;
	}
}
