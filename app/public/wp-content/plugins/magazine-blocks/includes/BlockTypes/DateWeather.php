<?php
/**
 * Date & Weather block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Services\OpenWeather;

defined( 'ABSPATH' ) || exit;

/**
 * Button block class.
 */
class DateWeather extends Block {

	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'date-weather';

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		$client_id = magazine_blocks_array_get( $attributes, 'clientId', '' );
		$icon      = magazine_blocks_array_get( $attributes, 'icon', '' );
		$get_icon  = magazine_blocks_get_icon( $icon, false );

		$open_weather = OpenWeather::init();

		// The Loop.
		$html = '';

		$html .= '<div class="mzb-date-weather mzb-date-weather-' . esc_attr( $client_id ) . '">';
		$html .= '<span class="mzb-weather-icon">' . wp_kses( $get_icon, magazine_blocks_get_allowed_svg_elements() ) . '</span>';

		// Temperature with default.
		$temp  = $open_weather->show_temp();
		$html .= '<span class="mzb-temperature">';
		$html .= ( empty( $temp ) ? '25' : $temp ) . '° ';
		$html .= '</span>';

		// Weather and location with defaults.
		$weather  = $open_weather->show_weather();
		$location = $open_weather->show_location();
		$html    .= '<div class="mzb-weather-date">';
		$html    .= ( empty( $weather ) ? 'Sunny' : $weather ) . ', ';
		$html    .= gmdate( 'F j, Y' );
		$html    .= ' in ' . ( empty( $location ) ? 'Kathmandu' : $location );
		$html    .= '</div>';
		$html    .= '</div>';

		return $html;
	}
}
