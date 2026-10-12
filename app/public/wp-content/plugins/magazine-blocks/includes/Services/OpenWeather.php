<?php

namespace MagazineBlocks\Services;

use MagazineBlocks\Traits\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * OpenWeather Service.
 */
class OpenWeather {

	use Singleton;

	/**
	 * Weather data.
	 *
	 * @var mixed
	 */
	private $weather_data = null;

	/**
	 * Api key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Lattitude.
	 *
	 * @var string|integer
	 */
	private $lat;

	/**
	 * Longitude.
	 *
	 * @var string|integer
	 */
	private $lon;

	/**
	 * Postal.
	 *
	 * @var mixed
	 */
	private $postal;
	/**
	 * Unit.
	 *
	 * @var string
	 */
	private $unit;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->api_key = magazine_blocks_get_setting( 'integrations.dateWeatherApiKey' );
		$this->postal  = magazine_blocks_get_setting( 'integrations.dateWeatherZipCode' );
		$this->lat     = magazine_blocks_get_setting( 'integrations.dateWeatherLatitude' );
		$this->lon     = magazine_blocks_get_setting( 'integrations.dateWeatherLongitude' );
		$this->unit    = magazine_blocks_get_setting( 'integrations.dateWeatherUnit', 'imperial' );
	}

	/**
	 * Check api is connected.
	 *
	 * @return boolean
	 */
	public function is_api_connected() {
		if ( empty( $this->api_key ) ) {
			return false;
		}

		$url = 'https://api.openweathermap.org/data/2.5/weather?q=London&APPID=' . $this->api_key;

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		return ( isset( $data->cod ) && 200 === $data->cod );
	}

	/**
	 * Get weather data from API
	 *
	 * @return array|bool Weather data or false on failure
	 */
	private function get_weather_data() {
		// Return cached data if already fetched
		if ( null !== $this->weather_data ) {
			return $this->weather_data;
		}

		if ( empty( $this->api_key ) ) {
			return false;
		}

		// Get coordinates from postal code if needed
		if ( ( empty( $this->lat ) || empty( $this->lon ) ) && ! empty( $this->postal ) ) {
			$geo_url      = 'https://api.openweathermap.org/geo/1.0/zip?zip=' . urlencode( $this->postal ) . '&APPID=' . $this->api;
			$geo_response = wp_remote_get( $geo_url );

			if ( ! is_wp_error( $geo_response ) && 200 === wp_remote_retrieve_response_code( $geo_response ) ) {
				$geo_body = wp_remote_retrieve_body( $geo_response );
				$geo_data = json_decode( $geo_body, true );

				if ( isset( $geo_data['lat'] ) && isset( $geo_data['lon'] ) ) {
					$this->lat = $geo_data['lat'];
					$this->lon = $geo_data['lon'];
				}
			}
		}

		if ( empty( $this->lat ) || empty( $this->lon ) ) {
			return false;
		}

		// Get weather data
		$weather_url      = 'https://api.openweathermap.org/data/2.5/weather?lat=' . $this->lat . '&lon=' . $this->lon . '&units=' . $this->unit . '&APPID=' . $this->api_key;
		$weather_response = wp_remote_get( $weather_url );

		if ( is_wp_error( $weather_response ) || 200 !== wp_remote_retrieve_response_code( $weather_response ) ) {

			// Log detailed error.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				if ( is_wp_error( $weather_response ) ) {
					error_log( 'Weather API Request Failed: ' . $weather_response->get_error_message() );
				} else {
					error_log( 'Weather API returned non-200 response. URL: ' . $weather_url . ' | Response Code: ' . wp_remote_retrieve_response_code( $weather_response ) );
				}
			}

			return false;
		}

		$weather_body       = wp_remote_retrieve_body( $weather_response );
		$this->weather_data = json_decode( $weather_body, true );

		return $this->weather_data;
	}

	/**
	 * Show temparature.
	 *
	 */
	public function show_temp() {
		$transient = get_transient( 'magazine_blocks_temp' );

		if ( false !== $transient ) {
			return $transient;
		}

		$weather_data = $this->get_weather_data();

		if ( ! $weather_data || ! isset( $weather_data['main']['temp'] ) ) {
			return '';
		}

		$temp = round( $weather_data['main']['temp'] );
		set_transient( 'magazine_blocks_temp', $temp, HOUR_IN_SECONDS );

		return $temp;
	}

	/**
	 * Show weather.
	 *
	 */
	public function show_weather() {
		$transient = get_transient( 'magazine_blocks_weather' );

		if ( false !== $transient ) {
			return $transient;
		}

		$weather_data = $this->get_weather_data();

		if ( ! $weather_data || ! isset( $weather_data['weather'][0]['main'] ) ) {
			return '';
		}

		$weather = $weather_data['weather'][0]['main'];
		set_transient( 'magazine_blocks_weather', $weather, HOUR_IN_SECONDS );

		return $weather;
	}

	/**
	 * Show location.
	 *
	 */
	public function show_location() {
		$transient = get_transient( 'magazine_blocks_location' );

		if ( false !== $transient ) {
			return $transient;
		}

		$weather_data = $this->get_weather_data();

		if ( ! $weather_data || ! isset( $weather_data['name'] ) ) {
			return '';
		}

		$location = $weather_data['name'];
		set_transient( 'magazine_blocks_location', $location, HOUR_IN_SECONDS );

		return $location;
	}

	/**
	 * Get comprehensive weather information.
	 *
	 * @return array Weather data array
	 */
	public function get_weather_info() {
		$transient = get_transient( 'magazine_blocks_weather_info' );

		if ( false !== $transient ) {
			return $transient;
		}

		$weather_data = $this->get_weather_data();

		if ( ! $weather_data ) {
			return array();
		}

		$info = array(
			'temp'     => isset( $weather_data['main']['temp'] ) ? round( $weather_data['main']['temp'] ) : '',
			'weather'  => isset( $weather_data['weather'][0]['main'] ) ? $weather_data['weather'][0]['main'] : '',
			'location' => isset( $weather_data['name'] ) ? $weather_data['name'] : '',
			'humidity' => isset( $weather_data['main']['humidity'] ) ? $weather_data['main']['humidity'] : '',
			'wind'     => isset( $weather_data['wind']['speed'] ) ? $weather_data['wind']['speed'] : '',
			'pressure' => isset( $weather_data['main']['pressure'] ) ? $weather_data['main']['pressure'] : '',
			'icon'     => isset( $weather_data['weather'][0]['icon'] ) ? $weather_data['weather'][0]['icon'] : '',
		);

		set_transient( 'magazine_blocks_weather_info', $info, HOUR_IN_SECONDS );

		return $info;
	}

	/**
	 * Clear weather cache
	 */
	public function clear_weather_cache() {
		delete_transient( 'magazine_blocks_temp' );
		delete_transient( 'magazine_blocks_weather' );
		delete_transient( 'magazine_blocks_location' );
		delete_transient( 'magazine_blocks_weather_info' );
		$this->weather_data = null;
	}
}
