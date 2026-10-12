<?php

/**
 * Template Context Handler service.
 *
 * @since x.x.x
 * @package MagazineBlocks
 */

namespace MagazineBlocks\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Handles template-specific query modifications.
 *
 * @since x.x.x
 */
class TemplateContextHandler {

	/**
	 * Modify query args based on template context.
	 *
	 * @since x.x.x
	 *
	 * @param array $args Original query args.
	 * @param array $extra_args The extra arguments.
	 * @return array Modified query args.
	 */
	public function modify_query_args( $args, $extra_args = array() ) {
		if ( isset( $extra_args['exclude_template'] ) && $extra_args['exclude_template'] ) {
			return $args;
		}

		$type = get_query_var( 'mzb_template_type' );

		if ( ! in_array( $type, array( 'archive', 'search', 'single', 'front' ), true ) ) {
			return $args;
		}

		// Remove specific parameters.
		$params_to_remove = isset( $extra_args['params_to_remove'] ) ? $extra_args['params_to_remove'] : array(
			'cat',
			'tag_id',
			'orderby',
			'order',
			'author',
			'category__not_in',
			'ignore_sticky_posts',
			'paged',
			'offset',
		);

		foreach ( $params_to_remove as $param ) {
			unset( $args[ $param ] );
		}

		// Set paged parameter.
		$args['paged'] = get_query_var( 'paged' );

		// Handle specific template types.
		switch ( $type ) {
			case 'archive':
				if ( is_archive() ) {
					if ( is_category() ) {
						$args['category_name'] = get_query_var( 'category_name' );
					} elseif ( is_tag() ) {
						$args['tag'] = get_query_var( 'tag' );
					} elseif ( is_author() ) {
						if ( isset( $args['author'] ) && 'all' === $args['author'] ) {
							$args['author'] = '';
						} else {
							$args['author'] = get_query_var( 'author' );
						}
					}
				}
				break;

			case 'search':
				$args['s'] = get_search_query();
				break;
		}

		return $args;
	}

	/**
	 * Check if we're in a special template context.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	public function is_special_context() {
		$type = get_query_var( 'mzb_template_type' );
		return in_array( $type, array( 'archive', 'search', 'single', 'front' ), true );
	}
}
