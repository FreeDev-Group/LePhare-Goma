<?php
/**
 * Array helper functions.
 *
 * @package Magazine Blocks
 */

/**
 * Determine whether the given value is array accessible.
 *
 * @param mixed $value Value to check.
 * @return bool
 */
function magazine_blocks_array_accessible( $value ): bool {
	return is_array( $value ) || $value instanceof ArrayAccess;
}

/**
 * Add an element to an array using "dot" notation if it doesn't exist.
 *
 * @param array  $array Array to add.
 * @param string $key Key.
 * @param mixed  $value Value.
 * @return array
 */
function magazine_blocks_array_add( array $array, string $key, $value ): array {
	if ( is_null( magazine_blocks_array_get( $array, $key ) ) ) {
		magazine_blocks_array_set( $array, $key, $value );
	}
	return $array;
}

/**
 * Collapse an array of arrays into a single array.
 *
 * @param iterable $array Array or Object.
 * @return array
 */
function magazine_blocks_array_collapse( $array ) {
	$results = array();
	foreach ( $array as $values ) {
		if ( ! is_array( $values ) ) {
			continue;
		}
		$results[] = $values;
	}
	return array_merge( array(), ...$results );
}

/**
 * Cross join the given arrays, returning all possible permutations.
 *
 * @return array
 */
function magazine_blocks_array_cross_join(): array {
	$arrays  = func_get_args();
	$results = array( array() );
	foreach ( $arrays as $index => $array ) {
		$append = array();
		foreach ( $results as $result ) {
			foreach ( $array as $item ) {
				$result[ $index ] = $item;

				$append[] = $result;
			}
		}
		$results = $append;
	}
	return $results;
}

/**
 * Divide an array into two arrays. One with keys and the other with values.
 *
 * @param array $array Array.
 * @return array
 */
function magazine_blocks_array_divide( array $array ) {
	return array( array_keys( $array ), array_values( $array ) );
}

/**
 * Flatten a multi-dimensional associative array with dots.
 *
 * @param iterable $array Array or object.
 * @param string   $prepend Prepend key.
 * @return array
 */
function magazine_blocks_array_dot( $array, $prepend = '' ) {
	$results = array();
	foreach ( $array as $key => $value ) {
		if ( is_array( $value ) && ! empty( $value ) ) {
			$results = array_merge( $results, magazine_blocks_array_dot( $value, $prepend . $key . '.' ) );
		} else {
			$results[ $prepend . $key ] = $value;
		}
	}
	return $results;
}

/**
/**
 * Converts a dot notation array to nested array
 *
 * Takes an array in dot notation like 'user.name'
 * and converts it to nested array format.
 *
 * @param array $array_value The dot notation input array
 *
 * @return array The converted nested array
 */
function magazine_blocks_array_undot( $array_value ) {
	$results = array();
	foreach ( $array_value as $key => $value ) {
		magazine_blocks_array_set( $results, $key, $value );
	}
	return $results;
}

/**
 * Get all of the given array except for a specified array of keys.
 *
 * @param array        $array Array.
 * @param array|string $keys Keys.
 * @return array
 */
function magazine_blocks_array_except( array $array, $keys ): array {
	magazine_blocks_array_forget( $array, $keys );
	return $array;
}

/**
 * Determine if the given key exists in the provided array.
 *
 * @param ArrayAccess|array $array Array.
 * @param string|int        $key Key.
 * @return bool
 */
function magazine_blocks_array_exists( $array, $key ): bool {
	if ( $array instanceof ArrayAccess ) {
		return $array->offsetExists( $key );
	}
	return array_key_exists( $key, $array );
}

/**
 * Return the first element in an array passing a given truth test.
 *
 * @param iterable      $array Array.
 * @param callable|null $callback Callback.
 * @param mixed         $default Default.
 * @return mixed
 */
function magazine_blocks_array_first( $array, $callback = null, $default = null ) {
	if ( is_null( $callback ) ) {
		if ( empty( $array ) ) {
			return magazine_blocks_value( $default );
		}
		foreach ( $array as $item ) {
			return $item;
		}
	}
	foreach ( $array as $key => $value ) {
		if ( $callback( $value, $key ) ) {
			return $value;
		}
	}
	return magazine_blocks_value( $default );
}

/**
 * Return the last element in an array passing a given truth test.
 *
 * @param array         $array Array.
 * @param callable|null $callback Callable.
 * @param mixed         $default Default.
 * @return mixed
 */
function magazine_blocks_array_last( array $array, $callback = null, $default = null ) {
	if ( is_null( $callback ) ) {
		return empty( $array ) ? magazine_blocks_value( $default ) : end( $array );
	}
	return magazine_blocks_array_first( array_reverse( $array, true ), $callback, $default );
}

/**
 * Flatten a multi-dimensional array into a single level.
 *
 * @param iterable $array Array or Object.
 * @param int      $depth Depth.
 * @return array
 */
function magazine_blocks_array_flatten( $array, $depth = INF ): array {
	$result = array();
	foreach ( $array as $item ) {
		if ( ! is_array( $item ) ) {
			$result[] = $item;
		} else {
			$values = 1 === $depth
				? array_values( $item )
				: magazine_blocks_array_flatten( $item, $depth - 1 );
			foreach ( $values as $value ) {
				$result[] = $value;
			}
		}
	}
	return $result;
}

/**
 * Remove one or many array items from a given array using "dot" notation.
 *
 * @param array        $array Array.
 * @param array|string $keys Keys.
 * @return void
 */
function magazine_blocks_array_forget( array &$array, $keys ) {
	$original = &$array;
	$keys     = (array) $keys;
	if ( count( $keys ) === 0 ) {
		return;
	}
	foreach ( $keys as $key ) {
		if ( magazine_blocks_array_exists( $array, $key ) ) {
			unset( $array[ $key ] );
			continue;
		}
		$parts = explode( '.', $key );
		$array = &$original;
		$count = count( $parts );
		while ( $count > 1 ) {
			$part  = array_shift( $parts );
			$count = count( $parts );
			if ( isset( $array[ $part ] ) && is_array( $array[ $part ] ) ) {
				$array = &$array[ $part ];
			} else {
				continue 2;
			}
		}
		unset( $array[ array_shift( $parts ) ] );
	}
}

/**
 * Get an item from an array using "dot" notation.
 *
 * @param ArrayAccess|array $array Array.
 * @param string|int|null   $key Key.
 * @param mixed             $default Default.
 * @return mixed
 */
function magazine_blocks_array_get( $array, $key, $default = null ) {
	if ( ! magazine_blocks_array_accessible( $array ) ) {
		return magazine_blocks_value( $default );
	}
	if ( is_null( $key ) ) {
		return $array;
	}
	if ( magazine_blocks_array_exists( $array, $key ) ) {
		return $array[ $key ];
	}
	if ( strpos( $key, '.' ) === false ) {
		return $array[ $key ] ?? magazine_blocks_value( $default );
	}
	foreach ( explode( '.', $key ) as $segment ) {
		if ( magazine_blocks_array_accessible( $array ) && magazine_blocks_array_exists( $array, $segment ) ) {
			$array = $array[ $segment ];
		} else {
			return magazine_blocks_value( $default );
		}
	}
	return $array;
}

/**
 * Check if an item or items exist in an array using "dot" notation.
 *
 * @param ArrayAccess|array $array Array.
 * @param string|array      $keys Keys.
 * @return bool
 */
function magazine_blocks_array_has( $array, $keys ): bool {
	$keys = (array) $keys;
	if ( ! $array || array() === $keys ) {
		return false;
	}
	foreach ( $keys as $key ) {
		$sub_key_array = $array;
		if ( magazine_blocks_array_exists( $array, $key ) ) {
			continue;
		}
		foreach ( explode( '.', $key ) as $segment ) {
			if ( magazine_blocks_array_accessible( $sub_key_array ) && magazine_blocks_array_exists( $sub_key_array, $segment ) ) {
				$sub_key_array = $sub_key_array[ $segment ];
			} else {
				return false;
			}
		}
	}
	return true;
}

/**
 * Determine if any of the keys exist in an array using "dot" notation.
 *
 * @param ArrayAccess|array $array Array.
 * @param string|array      $keys Keys.
 * @return bool
 */
function magazine_blocks_array_has_any( $array, $keys ): bool {
	if ( is_null( $keys ) ) {
		return false;
	}
	$keys = (array) $keys;
	if ( ! $array ) {
		return false;
	}
	if ( array() === $keys ) {
		return false;
	}
	foreach ( $keys as $key ) {
		if ( magazine_blocks_array_has( $array, $key ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Determines if an array is associative.
 *
 * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
 *
 * @param array $array Array.
 * @return bool
 */
function magazine_blocks_array_is_assoc( array $array ): bool {
	$keys = array_keys( $array );
	return array_keys( $keys ) !== $keys;
}

/**
 * Get a subset of the items from the given array.
 *
 * @param array        $array Array.
 * @param array|string $keys Keys.
 *
 * @return array
 */
function magazine_blocks_array_only( $array, $keys ) {
	return array_intersect_key( $array, array_flip( (array) $keys ) );
}

/**
 * Push an item onto the beginning of an array.
 *
 * @param array $array Array.
 * @param mixed $value Value.
 * @param mixed $key Key.
 * @return array
 */
function magazine_blocks_array_prepend( $array, $value, $key = null ) {
	if ( func_num_args() === 2 ) {
		array_unshift( $array, $value );
	} else {
		$array = array( $key => $value ) + $array;
	}
	return $array;
}

/**
 * Get a value from the array, and remove it.
 *
 * @param array  $array Array.
 * @param string $key Key.
 * @param mixed  $default Default.
 * @return mixed
 */
function magazine_blocks_array_pull( array &$array, string $key, $default = null ) {
	$value = magazine_blocks_array_get( $array, $key, $default );
	magazine_blocks_array_forget( $array, $key );
	return $value;
}

/**
 * Convert the array into a query string.
 *
 * @param array $array Array.
 * @return string
 */
function magazine_blocks_array_query( array $array ): string {
	return http_build_query( $array, '', '&', PHP_QUERY_RFC3986 );
}

/**
 * Get one or a specified number of random values from an array.
 *
 * @param array      $array Array.
 * @param bool|false $preserve_keys Preserve keys.
 * @param int|null   $number Number.
 * @return mixed
 * @throws InvalidArgumentException Invalid argument exception.
 */
function magazine_blocks_array_random( $array, $preserve_keys, $number = null ) {
	$requested = is_null( $number ) ? 1 : $number;
	$count     = count( $array );

	if ( $requested > $count ) {
		throw new InvalidArgumentException(
			esc_html( "You requested {$requested} items, but there are only {$count} items available." )
		);
	}
	if ( is_null( $number ) ) {
		return $array[ array_rand( $array ) ];
	}
	if ( 0 === (int) $number ) {
		return array();
	}
	$keys    = array_rand( $array, $number );
	$results = array();
	if ( $preserve_keys ) {
		foreach ( (array) $keys as $key ) {
			$results[ $key ] = $array[ $key ];
		}
	} else {
		foreach ( (array) $keys as $key ) {
			$results[] = $array[ $key ];
		}
	}
	return $results;
}

/**
 * Set an array item to a given value using "dot" notation.
 *
 * If no key is given to the method, the entire array will be replaced.
 *
 * @param array       $array Array.
 * @param string|null $key Key.
 * @param mixed       $value Value.
 * @return array
 */
function magazine_blocks_array_set( &$array, $key, $value ): array {
	if ( is_null( $key ) ) {
		$array = $value;
		return $array;
	}
	$keys = explode( '.', $key );
	foreach ( $keys as $i => $key ) {
		if ( count( $keys ) === 1 ) {
			break;
		}
		unset( $keys[ $i ] );
		if ( ! isset( $array[ $key ] ) || ! is_array( $array[ $key ] ) ) {
			$array[ $key ] = array();
		}
		$array = &$array[ $key ];
	}
	$array[ array_shift( $keys ) ] = $value;
	return $array;
}

/**
 * Shuffle the given array and return the result.
 *
 * @param array    $array Array.
 * @param int|null $seed Seed.
 * @return array
 */
function magazine_blocks_array_shuffle( array $array, $seed = null ): array {
	if ( is_null( $seed ) ) {
		shuffle( $array );
	} else {
		wp_rand( $seed );
		shuffle( $array );
		wp_rand();
	}

	return $array;
}

/**
 * Recursively sort an array by keys and values.
 *
 * @param array $array Array.
 * @param int   $options Options.
 * @param bool  $descending Descending.
 *
 * @return array
 */
function magazine_blocks_array_sort_recursive( array $array, int $options = SORT_REGULAR, bool $descending = true ): array {
	foreach ( $array as &$value ) {
		if ( is_array( $value ) ) {
			$value = magazine_blocks_array_sort_recursive( $value, $options, $descending );
		}
	}
	if ( magazine_blocks_array_is_assoc( $array ) ) {
		$descending
			? krsort( $array, $options )
			: ksort( $array, $options );
	} else {
		$descending
			? rsort( $array, $options )
			: sort( $array, $options );
	}
	return $array;
}

/**
 * Conditionally compile classes from an array into a CSS class list.
 *
 * @param array $array Array.
 * @return string
 */
function magazine_blocks_array_to_css_classes( $array ) {
	$class_list = magazine_blocks_array_wrap( $array );
	$classes    = array();
	foreach ( $class_list as $class => $constraint ) {
		if ( is_numeric( $class ) ) {
			$classes[] = $constraint;
		} elseif ( $constraint ) {
			$classes[] = $class;
		}
	}
	return implode( ' ', $classes );
}

/**
 * Filter the array using the given callback.
 *
 * @param array    $array Array.
 * @param callable $callback Callable.
 * @return array
 */
function magazine_blocks_array_where( $array, $callback ): array {
	return array_filter( $array, $callback, ARRAY_FILTER_USE_BOTH );
}

/**
 * If the given value is not an array and not null, wrap it in one.
 *
 * @param mixed $value Array to wrap.
 * @return array
 */
function magazine_blocks_array_wrap( $value ): array {
	if ( is_null( $value ) ) {
		return array();
	}
	return is_array( $value ) ? $value : array( $value );
}

/**
 * Get the first element of an array. Useful for method chaining.
 *
 * @param array $array Array.
 * @return mixed
 */
function magazine_blocks_head( $array ) {
	return reset( $array );
}

/**
 * Get the last element from an array.
 *
 * @param array $array Array.
 * @return mixed
 */
function magazine_blocks_last( array $array ) {
	return end( $array );
}

/**
 * Convert Json into Array.
 *
 * @param string $json JSON.
 * @return array
 */
function magazine_blocks_to_array( $json ) {
	return (array) json_decode( $json, true );
}

/**
 * Return the default value of the given value.
 *
 * @param mixed $value Value.
 * @return mixed
 */
function magazine_blocks_value( $value ) {
	return $value instanceof Closure ? $value() : $value;
}

/**
 * Combine keys with same value.
 *
 * @param array  $array Array of data.
 * @param string $separator Separator.
 * @return array
 */
function magazine_blocks_array_combine_keys( array $array, string $separator = ',' ): array {
	$result = array();

	foreach ( $array as $key => $value ) {
		if ( ! is_array( $value ) ) {
			continue;
		}

		$found = false;

		foreach ( $result as $k => $v ) {
			if ( empty( array_diff_assoc( $v, $value ) ) && empty( array_diff_assoc( $value, $v ) ) ) {
				$result[ "$key$separator$k" ] = $value;
				unset( $result[ $k ] );
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			$result[ $key ] = $value;
		}
	}

	return $result;
}

/**
 * Parse args recursively.
 *
 * @param array $a Default args.
 * @param array $b Args.
 *
 * @return array
 */
function magazine_blocks_parse_args( &$a, $b ) {
	$a      = (array) $a;
	$b      = (array) $b;
	$result = $b;

	foreach ( $a as $k => &$v ) {
		if ( is_array( $v ) && isset( $result[ $k ] ) ) {
			$result[ $k ] = magazine_blocks_parse_args( $v, $result[ $k ] );
		} else {
			$result[ $k ] = $v;
		}
	}

	return $result;
}


/**
 * Convert array to html attribute string.
 *
 * @param mixed $array Array to convert.
 *
 * @return string
 */
function magazine_blocks_array_to_html_attributes( $array ) {
	$attributes = array();

	foreach ( $array as $key => $value ) {
		if ( is_null( $value ) ) {
			continue;
		}
		$key          = htmlentities( $key, ENT_QUOTES, 'UTF-8' );
		$value        = htmlentities( $value, ENT_QUOTES, 'UTF-8' );
		$attributes[] = "$key=\"$value\"";
	}

	return implode( ' ', $attributes );
}
