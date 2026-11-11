/**
 * LePhare – Liste des événements : animations d'apparition au défilement.
 *
 * - Ajoute la classe « lph-visible » aux éléments [data-lph-reveal] quand ils entrent à l'écran.
 * - Sans JavaScript, ou si l'utilisateur a demandé moins de mouvements, tout reste visible
 *   (les styles d'animation ne s'appliquent que sous html.lph-anim).
 */
( function () {
	'use strict';

	var reduit = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	if ( reduit || ! ( 'IntersectionObserver' in window ) ) {
		return;
	}

	var racine = document.documentElement;
	racine.classList.add( 'lph-anim' );

	function preparer() {
		// Éléments générés par le shortcode (les widgets Elementor utilisent leurs propres animations d'entrée).
		var cibles = document.querySelectorAll( '[data-lph-reveal]' );

		var observateur = new IntersectionObserver(
			function ( entrees ) {
				entrees.forEach( function ( entree ) {
					if ( entree.isIntersecting ) {
						entree.target.classList.add( 'lph-visible' );
						observateur.unobserve( entree.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -40px 0px', threshold: 0.05 }
		);

		Array.prototype.forEach.call( cibles, function ( el, i ) {
			el.classList.add( 'lph-reveal' );
			// Décalage en cascade pour les cartes d'une même liste.
			var rang = el.getAttribute( 'data-lph-rang' );
			if ( rang !== null ) {
				el.style.setProperty( '--lph-delai', ( Math.min( parseInt( rang, 10 ), 6 ) * 90 ) + 'ms' );
			}
			observateur.observe( el );
		} );

		// Impression : tout afficher.
		window.addEventListener( 'beforeprint', function () {
			Array.prototype.forEach.call( cibles, function ( el ) {
				el.classList.add( 'lph-visible' );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', preparer );
	} else {
		preparer();
	}
}() );
