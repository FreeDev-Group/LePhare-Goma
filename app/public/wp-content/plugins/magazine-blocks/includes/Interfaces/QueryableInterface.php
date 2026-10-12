<?php

/**
 * QueryableInterface block.
 *
 * @since x.x.x
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for blocks that can perform database queries.
 *
 * @since x.x.x
 * @package Magazine Blocks
 */
interface QueryableInterface {

	/**
	 * Get the WordPress query object.
	 *
	 * @return object The WordPress query object.
	 * @since x.x.x
	 */
	public function getQuery();

	/**
	 * Get the query arguments array.
	 *
	 * @return array Associative array of query arguments.
	 * @since x.x.x
	 */
	public function getQueryArgs();
}
