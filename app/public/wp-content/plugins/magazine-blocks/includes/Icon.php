<?php
/**
 * Icon class.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks;

defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Traits\Singleton;
use JsonMachine\Items;

/**
 * Icon.
 */
class Icon {

	use Singleton;

	const FONT_AWESOME_ICONS_PATH    = MAGAZINE_BLOCKS_PLUGIN_DIR . '/assets/json/font-awesome-v6.json';
	const MAGAZINE_BLOCKS_ICONS_PATH = MAGAZINE_BLOCKS_PLUGIN_DIR . '/assets/json/magazine-blocks-v1.json';

	/**
	 * Get icon.
	 *
	 * @param string $name Icon name.
	 * @param array  $args Additional args for html attributes.
	 * @return false|mixed|null|void
	 */
	public function get( string $name, array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'size'        => '24',
				'aria-hidden' => 'true',
				'focusable'   => 'false',
			)
		);

		preg_match( '/^(.*?)(-regular|-solid|-brands)?$/', $name, $matches );

		if ( ! empty( $matches[2] ) ) {
			$icons_path = self::FONT_AWESOME_ICONS_PATH;
		} else {
			$icons_path = self::MAGAZINE_BLOCKS_ICONS_PATH;
		}

		try {
			$iterator = Items::fromFile( $icons_path, array( 'pointer' => "/$name/svg" ) );
			$icon     = '';
			foreach ( $iterator as $svg ) {
				$icon = $svg;
			}
		} catch ( \Exception $e ) {
			_doing_it_wrong( 'magazine_blocks_get_icon', esc_html( $e->getMessage() ), 'x.x.x' );
		}

		if ( ! $icon ) {
			return '';
		}

		$icon = str_replace( '<svg', '<svg ' . $this->build_attributes( $args ), $icon );
		$icon = str_replace( 'http://www.w3.org/2000/svg', 'https://www.w3.org/2000/svg', $icon );

		return apply_filters( 'magazine_blocks_icon', $icon, $name, $args );
	}

	/**
	 * Build SVG attributes.
	 *
	 * @param array $args Attributes to add to the SVG.
	 * @return string
	 */
	private function build_attributes( array $args ): string {
		$size  = magazine_blocks_array_pull( $args, 'size', '24' );
		$class = 'magazine_blocks-icon ' . magazine_blocks_array_pull( $args, 'class', '' );

		$args['width']  = $size;
		$args['height'] = $size;
		$args['class']  = trim( $class );

		return magazine_blocks_array_to_html_attributes( $args );
	}
}
