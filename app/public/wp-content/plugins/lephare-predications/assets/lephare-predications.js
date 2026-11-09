/**
 * LePhare – Prédications & Ressources (page publique).
 *
 * 1. Filtres : choisir une valeur dans une liste déroulante envoie le formulaire.
 *    Sans JavaScript, le bouton « Filtrer » fait la même chose (paramètres dans l'URL).
 * 2. Apparition douce des cartes au défilement (classe « lpp-visible »).
 *    Sans JavaScript, ou si l'utilisateur a demandé moins de mouvements, tout reste visible.
 */
( function () {
	'use strict';

	function filtres() {
		// Adresse propre : les champs vides ne sont pas envoyés (?lph_orateur=… au lieu de ?lph_q=&lph_orateur=…&lph_serie=).
		Array.prototype.forEach.call( document.querySelectorAll( 'form.lpp-filtres' ), function ( form ) {
			form.addEventListener( 'submit', function () {
				Array.prototype.forEach.call( form.querySelectorAll( 'input[name], select[name]' ), function ( champ ) {
					if ( '' === champ.value ) {
						champ.disabled = true;
					}
				} );
				// Si la page est rechargée depuis le cache (bouton Retour), les champs redeviennent utilisables.
				window.addEventListener( 'pageshow', function () {
					Array.prototype.forEach.call( form.querySelectorAll( '[disabled]' ), function ( champ ) {
						champ.disabled = false;
					} );
				}, { once: true } );
			} );
		} );

		var listes = document.querySelectorAll( '.lpp-filtres select[data-lpp-auto]' );
		Array.prototype.forEach.call( listes, function ( liste ) {
			liste.addEventListener( 'change', function () {
				if ( liste.form ) {
					if ( typeof liste.form.requestSubmit === 'function' ) {
						liste.form.requestSubmit();
					} else {
						liste.form.submit();
					}
				}
			} );
		} );
	}

	function apparition() {
		var reduit = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		if ( reduit || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}
		var cibles = document.querySelectorAll( '[data-lpp-reveal]' );
		if ( ! cibles.length ) {
			return;
		}
		document.documentElement.classList.add( 'lpp-anim' );

		var observateur = new IntersectionObserver(
			function ( entrees ) {
				entrees.forEach( function ( entree ) {
					if ( entree.isIntersecting ) {
						entree.target.classList.add( 'lpp-visible' );
						observateur.unobserve( entree.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -40px 0px', threshold: 0.05 }
		);

		Array.prototype.forEach.call( cibles, function ( el ) {
			el.classList.add( 'lpp-reveal' );
			observateur.observe( el );
			// Une carte qui reçoit le focus clavier s'affiche immédiatement.
			el.addEventListener( 'focusin', function () {
				el.classList.add( 'lpp-visible' );
			} );
		} );

		window.addEventListener( 'beforeprint', function () {
			Array.prototype.forEach.call( cibles, function ( el ) {
				el.classList.add( 'lpp-visible' );
			} );
		} );
	}

	// Après un clic sur un onglet, une série ou une page : la liste arrive en haut de l'écran avec un peu d'air
	// (le thème place l'ancre tout contre le bord).
	function recaler() {
		if ( '#lph-predications' !== window.location.hash ) {
			return;
		}
		var cible = document.getElementById( 'lph-predications' );
		if ( ! cible ) {
			return;
		}
		window.setTimeout( function () {
			var haut = cible.getBoundingClientRect().top + window.pageYOffset - 32;
			window.scrollTo( { top: Math.max( 0, haut ), behavior: 'auto' } );
		}, 120 );
	}

	function demarrer() {
		filtres();
		apparition();
		if ( 'complete' === document.readyState ) {
			recaler();
		} else {
			window.addEventListener( 'load', recaler, { once: true } );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', demarrer );
	} else {
		demarrer();
	}
}() );
