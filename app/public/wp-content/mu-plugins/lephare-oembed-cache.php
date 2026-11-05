<?php
/**
 * Plugin Name: LePhare - Cache oEmbed
 * Description: Met en cache 24 h les appels wp_oembed_get() faits hors des articles (ex. Orderable qui interroge YouTube a chaque requete), pour accelerer le site.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'pre_oembed_result', function ( $result, $url, $args ) {
	if ( null !== $result ) {
		return $result;
	}
	$cached = get_transient( 'lephare_oembed_' . md5( $url . wp_json_encode( $args ) ) );
	return false === $cached ? null : ( '' === $cached ? false : $cached );
}, 99, 3 );

add_filter( 'oembed_result', function ( $html, $url, $args ) {
	// Un echec est aussi memorise (chaine vide) pour ne pas reessayer a chaque page hors ligne.
	set_transient( 'lephare_oembed_' . md5( $url . wp_json_encode( $args ) ), false === $html ? '' : $html, DAY_IN_SECONDS );
	return $html;
}, 99, 3 );
