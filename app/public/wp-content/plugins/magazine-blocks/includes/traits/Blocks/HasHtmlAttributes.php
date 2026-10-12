<?php
namespace MagazineBlocks\Traits\Blocks;

/**
 * HasHtmlAttributes class trait.
 *
 * @package Magazine Blocks\Traits
 */
trait HasHtmlAttributes {
	/**
	 * Get default HTML attributes.
	 *
	 * @return array
	 */
	protected function get_default_html_attrs() {
		return array(
			'id'    => $this->get_attribute( 'cssID', '', true ),
			'class' => $this->cn(
				"mzb-$this->block_name mzb-$this->block_name-{$this->get_attribute('clientId', '', true)}",
				$this->get_attribute( 'className', '' )
			),
		);
	}

	/**
	 * Get custom HTML attributes.
	 *
	 * @return array
	 */
	protected function get_html_attrs() {
		return array();
	}

	/**
	 * Build html attributes.
	 *
	 * @param boolean $echo_attrs
	 * @return string
	 */
	protected function build_html_attributes( $echo_attrs = false ) {
		$attrs = wp_parse_args( $this->get_html_attrs(), $this->get_default_html_attrs() );
		return magazine_blocks_build_html_attrs( $attrs, $echo_attrs );
	}

	/**
	 * Merge the block's cssID/className attributes into the outermost tag of
	 * already-rendered HTML. Used as a single, block-agnostic safety net for
	 * blocks that hand-build their own markup instead of using
	 * build_html_attributes(), so the Advanced panel's CSS ID/Class fields
	 * work everywhere without touching each block's render logic.
	 *
	 * No-ops (returns $html untouched) whenever cssID/className are unset, or
	 * whenever the outermost tag already carries that exact id/class (e.g.
	 * blocks that already merge them manually) - so it never duplicates or
	 * overrides anything a block already does correctly.
	 *
	 * @param string $html Already-rendered block HTML.
	 * @return string
	 */
	protected function merge_advanced_attributes( $html ) {
		if ( ! is_string( $html ) || '' === trim( $html ) ) {
			return $html;
		}

		$css_id     = $this->get_attribute( 'cssID', '', true );
		$class_name = trim( (string) $this->get_attribute( 'className', '' ) );

		if ( '' === $css_id && '' === $class_name ) {
			return $html;
		}

		$tag_pattern = '/^(\s*<[a-zA-Z][a-zA-Z0-9-]*)((?:\s+[a-zA-Z_:][-a-zA-Z0-9_:.]*(?:=(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+))?)*)(\s*\/?>)/';

		return preg_replace_callback(
			$tag_pattern,
			function ( $matches ) use ( $css_id, $class_name ) {
				list( , $tag_open, $attrs, $tag_close ) = $matches;

				if ( '' !== $class_name ) {
					$existing = array();
					if ( preg_match( '/\sclass\s*=\s*"([^"]*)"/', $attrs, $class_match ) ) {
						$existing = preg_split( '/\s+/', trim( $class_match[1] ), -1, PREG_SPLIT_NO_EMPTY );
					}

					$missing = array_diff( preg_split( '/\s+/', $class_name, -1, PREG_SPLIT_NO_EMPTY ), $existing );

					if ( $missing ) {
						$merged = esc_attr( trim( implode( ' ', array_merge( $existing, $missing ) ) ) );
						$attrs  = $existing
							? preg_replace( '/\sclass\s*=\s*"[^"]*"/', ' class="' . $merged . '"', $attrs, 1 )
							: $attrs . ' class="' . $merged . '"';
					}
				}

				if ( '' !== $css_id && ! preg_match( '/\sid\s*=\s*"[^"]*"/', $attrs ) ) {
					$attrs .= ' id="' . esc_attr( $css_id ) . '"';
				}

				return $tag_open . $attrs . $tag_close;
			},
			$html,
			1
		);
	}
}
