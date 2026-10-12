<?php
/**
 * Singleton class trait.
 *
 * @package Magazine Blocks\Traits
 */

namespace MagazineBlocks\Traits;

/**
 * Singleton trait.
 */
trait Singleton {

	/**
	 * Holds single instance of the class.
	 *
	 * @var null|static
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return static
	 */
	final public static function init() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {}

	/**
	 * Disable unserializing of the class.
	 *
	 * @return void
	 */
	public function __wakeup() {}

	/**
	 * Disable cloning of the class.
	 *
	 * @return void
	 */
	public function __clone() {}
}
