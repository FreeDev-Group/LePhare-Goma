/**
 * LePhare – Actualités (issue #07, v3.7). Aucune bibliothèque externe, aucun suivi.
 *
 * 1. Page d’un article : « Copier le lien » (message lu par les lecteurs d’écran), barre de progression, anneau « % lu », sommaire qui suit la lecture.
 * 2. Page Actualités : apparition douce des blocs au défilement et compteur d’articles (sauf « réduire les animations »).
 * 3. Retour sur la liste après un filtre ou une recherche : la liste arrive en haut de l’écran,
 *    sous la barre d'admin WordPress si elle est affichée.
 */
( function () {
	'use strict';

	/* Hauteur occupée en haut de l'écran par la barre d'admin WordPress (fixe), sinon 0. */
	function hautOccupe() {
		var admin = document.getElementById( 'wpadminbar' );
		if ( admin && 'fixed' === getComputedStyle( admin ).position ) {
			return Math.round( Math.max( 0, admin.getBoundingClientRect().bottom ) );
		}
		return 0;
	}

	function copier() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-lpa-copier]' ), function ( bouton ) {
			bouton.addEventListener( 'click', function () {
				var url = bouton.getAttribute( 'data-lpa-copier' );
				var statut = bouton.closest( '.lpa-partage' ).querySelector( '[data-lpa-statut]' );
				function annoncer( message ) {
					statut.textContent = '';
					window.setTimeout( function () {
						statut.textContent = message;
					}, 50 );
				}
				function secours() {
					var champ = document.createElement( 'textarea' );
					champ.value = url;
					champ.setAttribute( 'readonly', '' );
					champ.style.position = 'fixed';
					champ.style.opacity = '0';
					document.body.appendChild( champ );
					champ.select();
					var ok = false;
					try {
						ok = document.execCommand( 'copy' );
					} catch ( e ) {}
					document.body.removeChild( champ );
					bouton.focus();
					annoncer( ok ? 'Lien copié.' : 'Copie impossible : copiez l’adresse dans la barre du navigateur.' );
				}
				if ( navigator.clipboard && window.isSecureContext ) {
					navigator.clipboard.writeText( url ).then( function () {
						annoncer( 'Lien copié.' );
					}, secours );
				} else {
					secours();
				}
			} );
		} );
	}

	function recaler() {
		if ( '#lpa-journal' !== window.location.hash ) {
			return;
		}
		var cible = document.getElementById( 'lpa-journal' );
		if ( cible ) {
			window.setTimeout( function () {
				window.scrollTo( { top: Math.max( 0, cible.getBoundingClientRect().top + window.pageYOffset - hautOccupe() - 24 ), behavior: 'auto' } );
			}, 120 );
		}
	}

	/*
	 * Apparition douce des blocs de la page Actualités quand ils entrent dans l'écran.
	 * Rien n'est caché sans JavaScript ni si l'utilisateur demande moins de mouvement ;
	 * un bloc qui reçoit le focus clavier apparaît tout de suite.
	 */
	function apparition() {
		var racine = document.querySelector( '.lpa' );
		if ( ! racine || racine.classList.contains( 'lpa--calme' ) || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}
		if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			return;
		}
		var cibles = racine.querySelectorAll( '.lpa-rubrique-carte, .lpa-section-tete, .lpa-une__article, .lpa-liste__entete, .lpa-barre, .lpa-grille > li' );
		if ( ! cibles.length ) {
			return;
		}
		function montrer( el ) {
			if ( ! el.hasAttribute( 'data-lpa-apparition' ) || el.classList.contains( 'lpa-visible' ) ) {
				return;
			}
			el.classList.add( 'lpa-visible' );
			var delai = parseInt( el.style.getPropertyValue( '--lpa-delai' ), 10 ) || 0;
			// Fin de l'animation : on rend au bloc ses transitions habituelles (survol).
			window.setTimeout( function () {
				el.removeAttribute( 'data-lpa-apparition' );
				el.classList.remove( 'lpa-visible' );
				el.style.removeProperty( '--lpa-delai' );
			}, delai + 800 );
		}
		var obs = new IntersectionObserver( function ( entrees ) {
			entrees.forEach( function ( e ) {
				if ( e.isIntersecting ) {
					montrer( e.target );
					obs.unobserve( e.target );
				}
			} );
		}, { rootMargin: '0px 0px -6% 0px', threshold: 0.05 } );
		Array.prototype.forEach.call( cibles, function ( el ) {
			var rang = el.parentNode ? Array.prototype.indexOf.call( el.parentNode.children, el ) : 0;
			el.setAttribute( 'data-lpa-apparition', '' );
			el.style.setProperty( '--lpa-delai', ( Math.min( rang, 5 ) * 90 ) + 'ms' );
			obs.observe( el );
		} );
		racine.classList.add( 'lpa-anime' );
		racine.addEventListener( 'focusin', function ( ev ) {
			var bloc = ev.target.closest( '[data-lpa-apparition]' );
			if ( bloc ) {
				montrer( bloc );
			}
		} );
	}

	/* Compteur du bandeau : le nombre d'articles monte de 0 à sa vraie valeur (sauf « réduire les animations »). */
	function compteurs() {
		if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			return;
		}
		Array.prototype.forEach.call( document.querySelectorAll( '[data-lpa-compteur]' ), function ( el ) {
			var fin = parseInt( el.getAttribute( 'data-lpa-compteur' ), 10 ) || 0;
			if ( fin < 2 || el.closest( '.lpa--calme' ) ) {
				return;
			}
			var debut = null;
			var duree = 900;
			el.setAttribute( 'aria-hidden', 'true' );
			function pas( t ) {
				if ( null === debut ) {
					debut = t;
				}
				var p = Math.min( 1, ( t - debut ) / duree );
				el.textContent = String( Math.round( fin * ( 1 - Math.pow( 1 - p, 3 ) ) ) );
				if ( p < 1 ) {
					window.requestAnimationFrame( pas );
				} else {
					el.textContent = String( fin );
					el.removeAttribute( 'aria-hidden' );
				}
			}
			el.textContent = '0';
			window.requestAnimationFrame( pas );
		} );
	}

	/*
	 * Page d'un article : barre de progression fine, anneau « % lu » et sommaire qui suit la lecture.
	 * Ce sont des indications d'état (pas des animations) ; elles restent actives avec « réduire les animations ».
	 */
	function lecture() {
		var corps = document.querySelector( '.lpa-art-corps .lpa-lecture' );
		if ( ! corps ) {
			return;
		}
		var barre = document.querySelector( '.lpa-progression span' );
		var anneau = document.querySelector( '[data-lpa-anneau]' );
		var pourcent = document.querySelector( '[data-lpa-pourcent]' );
		var circonference = 2 * Math.PI * 22;
		var attente = false;
		function maj() {
			attente = false;
			var r = corps.getBoundingClientRect();
			var h = window.innerHeight;
			var parcours = r.height - h * 0.5;
			var fait = h * 0.5 - r.top;
			var p = parcours > 0 ? Math.max( 0, Math.min( 1, fait / parcours ) ) : 1;
			if ( barre ) {
				barre.style.transform = 'scaleX(' + p + ')';
			}
			if ( anneau ) {
				anneau.style.strokeDashoffset = String( circonference * ( 1 - p ) );
			}
			if ( pourcent ) {
				pourcent.textContent = String( Math.round( p * 100 ) );
			}
		}
		window.addEventListener( 'scroll', function () {
			if ( ! attente ) {
				attente = true;
				window.requestAnimationFrame( maj );
			}
		}, { passive: true } );
		window.addEventListener( 'resize', maj );
		maj();

		// Sommaire : la partie en cours de lecture est mise en évidence (aria-current).
		var liens = document.querySelectorAll( '.lpa-art-sommaire a[href^="#"]' );
		if ( ! liens.length || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}
		var parId = {};
		var titres = [];
		Array.prototype.forEach.call( liens, function ( a ) {
			var id = decodeURIComponent( a.getAttribute( 'href' ).slice( 1 ) );
			var t = document.getElementById( id );
			if ( t ) {
				parId[ id ] = a;
				titres.push( t );
			}
		} );
		function activer( id ) {
			Object.keys( parId ).forEach( function ( k ) {
				if ( k === id ) {
					parId[ k ].setAttribute( 'aria-current', 'true' );
				} else {
					parId[ k ].removeAttribute( 'aria-current' );
				}
			} );
		}
		var obs = new IntersectionObserver( function ( entrees ) {
			entrees.forEach( function ( e ) {
				if ( e.isIntersecting ) {
					activer( e.target.id );
				}
			} );
		}, { rootMargin: '0px 0px -70% 0px' } );
		titres.forEach( function ( t ) {
			obs.observe( t );
		} );
	}

	function demarrer() {
		copier();
		apparition();
		compteurs();
		lecture();
		if ( 'complete' === document.readyState ) {
			recaler();
		} else {
			window.addEventListener( 'load', recaler, { once: true } );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', demarrer );
	} else {
		demarrer();
	}
}() );
