<?php
namespace MagazineBlocks\Traits\Blocks;

/**
 * HasClassNames class trait.
 *
 * @package Magazine Blocks\Traits
 */
trait HasClassNames {
	/**
	 * Cn.
	 *
	 * @param [type] ...$args The args.
	 */
	public function cn( ...$args ) {
		$is_assoc = function ( $arr ) {
			return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
		};

		$to_string = function ( $val ) use ( &$to_string, $is_assoc ) {
			$str = '';
			if ( is_string( $val ) || is_numeric( $val ) ) {
				$str .= $val;
			} elseif ( is_array( $val ) && $is_assoc( $val ) ) {
				foreach ( $val as $key => $value ) {
					if ( $value ) {
						$str .= ( $str ? ' ' : '' ) . $key;
					}
				}
			} elseif ( is_array( $val ) ) {
				foreach ( $val as $item ) {
					$y = $to_string( $item );
					if ( $y ) {
						$str .= ( $str ? ' ' : '' ) . $y;
					}
				}
			}
			return $str;
		};

		$result = '';
		foreach ( $args as $arg ) {
			if ( $arg ) {
				$x = $to_string( $arg );
				if ( $x ) {
					$result .= ( $result ? ' ' : '' ) . $x;
				}
			}
		}

		return $result ? $result : null;
	}
}
