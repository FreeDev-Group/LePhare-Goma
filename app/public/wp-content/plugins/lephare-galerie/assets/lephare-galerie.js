/**
 * LePhare – Galerie (issue #08, v1.1). Aucune bibliothèque externe, aucun suivi.
 *
 * 1. Visionneuse plein écran (page d'un album et dernier album de la page Galerie) :
 *    précédent / suivant (boutons, flèches, balayage tactile), Échap, compteur « 3 / 12 », légende,
 *    bande de vignettes, diaporama (bouton ou barre d'espace, barre de progression), plein écran,
 *    fenêtre modale (aria-modal, reste de la page inerte, focus piégé, focus rendu à la fermeture).
 * 2. Cartes d'album : aperçu des photos au survol ou au focus clavier.
 * 3. Bandeau en fondu. 4. Menu déroulant des catégories (petit écran). 5. Arrivée directe sur la liste après un filtre.
 * Sans JavaScript, chaque vignette ouvre simplement l'image en grand.
 */
( function () {
	'use strict';

	var reduit = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* ---------------------------------------------------------------------
	 * 1. Visionneuse
	 * ------------------------------------------------------------------ */
	function visionneuse() {
		var vis = document.querySelector( '[data-lpg-visionneuse]' );
		if ( ! vis ) {
			return;
		}
		var donnees = vis.querySelector( 'script[data-lpg-items]' );
		var items = [];
		try {
			items = JSON.parse( donnees ? donnees.textContent : '[]' );
		} catch ( e ) {
			items = [];
		}
		var total = items.length;
		if ( ! total ) {
			return;
		}
		var calme = !! document.querySelector( '.lpg--calme' );

		// Placée à la fin de <body> pour pouvoir rendre tout le reste inerte.
		document.body.appendChild( vis );

		var img = vis.querySelector( '[data-lpg-image]' );
		var legende = vis.querySelector( '[data-lpg-legende]' );
		var compteur = vis.querySelector( '[data-lpg-compteur]' );
		var btnFermer = vis.querySelector( '[data-lpg-fermer]' );
		var btnPrec = vis.querySelector( '[data-lpg-prec]' );
		var btnSuiv = vis.querySelector( '[data-lpg-suiv]' );
		var btnLecture = vis.querySelector( '[data-lpg-lecture]' );
		var btnPlein = vis.querySelector( '[data-lpg-plein]' );
		var barre = vis.querySelector( '[data-lpg-progression]' );
		var bande = vis.querySelector( '[data-lpg-bande]' );
		var duree = Math.max( 2, parseInt( vis.getAttribute( 'data-lpg-duree' ), 10 ) || 5 ) * 1000;
		var txtLecture = vis.getAttribute( 'data-lpg-txt-lecture' ) || '';
		var txtPause = vis.getAttribute( 'data-lpg-txt-pause' ) || '';
		var txtPhoto = vis.getAttribute( 'data-lpg-txt-photo' ) || 'Photo %1$d';
		var courant = 0;
		var origine = null;
		var inertes = [];
		var minuteur = null;
		var lecture = false;
		var departX = null;
		var departY = null;
		var vignettes = [];

		if ( total < 2 ) {
			[ btnPrec, btnSuiv, btnLecture ].forEach( function ( b ) {
				if ( b ) {
					b.hidden = true;
				}
			} );
		}
		if ( btnPlein && document.fullscreenEnabled && vis.requestFullscreen ) {
			btnPlein.hidden = false;
		}

		// Bande de vignettes : un bouton par photo.
		if ( bande ) {
			items.forEach( function ( it, i ) {
				var b = document.createElement( 'button' );
				b.type = 'button';
				b.className = 'lpg-vis__vignette';
				b.setAttribute( 'aria-label', txtPhoto.replace( '%1$d', String( i + 1 ) ) + ( it.alt ? ' : ' + it.alt : '' ) );
				var im = document.createElement( 'img' );
				im.src = it.vignette || it.src;
				im.alt = '';
				im.loading = 'lazy';
				im.decoding = 'async';
				b.appendChild( im );
				b.addEventListener( 'click', function () {
					afficher( i );
					relancer();
				} );
				bande.appendChild( b );
				vignettes.push( b );
			} );
		}

		function precharger( i ) {
			var it = items[ ( i + total ) % total ];
			var p = new Image();
			p.sizes = '100vw';
			if ( it.srcset ) {
				p.srcset = it.srcset;
			}
			p.src = it.src;
		}

		function afficher( i ) {
			courant = ( i + total ) % total;
			var it = items[ courant ];
			if ( ! reduit && ! calme ) {
				img.classList.add( 'lpg-charge' );
			}
			img.onload = function () {
				img.classList.remove( 'lpg-charge' );
			};
			img.sizes = '100vw';
			img.srcset = it.srcset || '';
			img.src = it.src;
			img.alt = it.alt || '';
			legende.textContent = it.legende || '';
			compteur.textContent = ( courant + 1 ) + ' / ' + total;
			vignettes.forEach( function ( v, k ) {
				if ( k === courant ) {
					v.setAttribute( 'aria-current', 'true' );
					v.scrollIntoView( { block: 'nearest', inline: 'center', behavior: reduit ? 'auto' : 'smooth' } );
				} else {
					v.removeAttribute( 'aria-current' );
				}
			} );
			if ( total > 1 ) {
				precharger( courant + 1 );
				precharger( courant - 1 );
			}
		}

		/* Diaporama : une photo toutes les « duree » secondes ; la barre montre le temps restant. */
		function relancer() {
			window.clearTimeout( minuteur );
			if ( ! lecture ) {
				return;
			}
			if ( barre ) {
				barre.style.transition = 'none';
				barre.style.transform = 'scaleX(0)';
				void barre.offsetWidth; // Recalcul : la barre repart de zéro.
				barre.style.transition = 'transform ' + duree + 'ms linear';
				barre.style.transform = 'scaleX(1)';
			}
			minuteur = window.setTimeout( function () {
				afficher( courant + 1 );
				relancer();
			}, duree );
		}

		function jouer( oui ) {
			lecture = oui && total > 1;
			vis.classList.toggle( 'lpg-vis--lecture', lecture );
			if ( btnLecture ) {
				btnLecture.setAttribute( 'aria-pressed', lecture ? 'true' : 'false' );
				btnLecture.setAttribute( 'aria-label', lecture ? txtPause : txtLecture );
			}
			if ( ! lecture ) {
				window.clearTimeout( minuteur );
				if ( barre ) {
					barre.style.transition = 'none';
					barre.style.transform = 'scaleX(0)';
				}
			}
			relancer();
		}

		function focusables() {
			return Array.prototype.filter.call( vis.querySelectorAll( 'button' ), function ( b ) {
				return ! b.hidden;
			} );
		}

		function ouvrir( i, depuis, enLecture ) {
			origine = depuis || document.activeElement;
			afficher( i );
			vis.hidden = false;
			document.documentElement.classList.add( 'lpg-bloque' );
			inertes = Array.prototype.filter.call( document.body.children, function ( el ) {
				return el !== vis && ! el.hasAttribute( 'inert' ) && 'SCRIPT' !== el.tagName;
			} );
			inertes.forEach( function ( el ) {
				el.setAttribute( 'inert', '' );
			} );
			( enLecture && btnLecture && ! btnLecture.hidden ? btnLecture : btnFermer ).focus();
			document.addEventListener( 'keydown', clavier, true );
			jouer( !! enLecture );
		}

		function fermer() {
			jouer( false );
			if ( document.fullscreenElement ) {
				document.exitFullscreen().catch( function () {} );
			}
			vis.hidden = true;
			document.documentElement.classList.remove( 'lpg-bloque' );
			inertes.forEach( function ( el ) {
				el.removeAttribute( 'inert' );
			} );
			inertes = [];
			document.removeEventListener( 'keydown', clavier, true );
			img.removeAttribute( 'src' );
			img.removeAttribute( 'srcset' );
			// Retour du focus : la vignette de la photo affichée en dernier si elle existe, sinon l'élément d'origine.
			var vignette = document.querySelector( '[data-lpg-mur] [data-lpg-index="' + courant + '"]' );
			var retour = vignette || origine;
			if ( retour && retour.focus ) {
				retour.focus();
			}
		}

		function clavier( e ) {
			var surBouton = document.activeElement && 'BUTTON' === document.activeElement.tagName;
			if ( 'Escape' === e.key ) {
				e.preventDefault();
				fermer();
			} else if ( 'ArrowRight' === e.key && total > 1 ) {
				e.preventDefault();
				afficher( courant + 1 );
				relancer();
			} else if ( 'ArrowLeft' === e.key && total > 1 ) {
				e.preventDefault();
				afficher( courant - 1 );
				relancer();
			} else if ( ' ' === e.key && ! surBouton && total > 1 ) {
				e.preventDefault();
				jouer( ! lecture );
			} else if ( 'Tab' === e.key ) {
				// Focus piégé : on tourne entre les boutons de la visionneuse.
				var f = focusables();
				var pos = f.indexOf( document.activeElement );
				e.preventDefault();
				var suivant = e.shiftKey ? ( pos <= 0 ? f.length - 1 : pos - 1 ) : ( pos === f.length - 1 ? 0 : pos + 1 );
				f[ suivant ].focus();
			}
		}

		// Ouvrir : vignettes de l'album, mosaïque du dernier album, boutons « Lancer le diaporama ».
		Array.prototype.forEach.call( document.querySelectorAll( '[data-lpg-mur] a[data-lpg-index], .lpg-une a[data-lpg-index]' ), function ( l ) {
			l.addEventListener( 'click', function ( e ) {
				if ( e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || 0 !== e.button ) {
					return;
				}
				e.preventDefault();
				ouvrir( parseInt( l.getAttribute( 'data-lpg-index' ), 10 ) || 0, l, false );
			} );
		} );
		Array.prototype.forEach.call( document.querySelectorAll( '[data-lpg-diaporama]' ), function ( b ) {
			b.addEventListener( 'click', function () {
				ouvrir( 0, b, true );
			} );
		} );

		btnFermer.addEventListener( 'click', fermer );
		btnPrec.addEventListener( 'click', function () {
			afficher( courant - 1 );
			relancer();
		} );
		btnSuiv.addEventListener( 'click', function () {
			afficher( courant + 1 );
			relancer();
		} );
		if ( btnLecture ) {
			btnLecture.addEventListener( 'click', function () {
				jouer( ! lecture );
			} );
		}
		if ( btnPlein ) {
			btnPlein.addEventListener( 'click', function () {
				if ( document.fullscreenElement ) {
					document.exitFullscreen().catch( function () {} );
				} else {
					vis.requestFullscreen().catch( function () {} );
				}
			} );
		}

		// Clic sur le fond sombre (hors image et boutons) : fermeture.
		vis.addEventListener( 'click', function ( e ) {
			if ( e.target === vis || e.target.classList.contains( 'lpg-vis__scene' ) ) {
				fermer();
			}
		} );

		// Balayage horizontal sur écran tactile.
		vis.addEventListener( 'touchstart', function ( e ) {
			if ( 1 === e.touches.length ) {
				departX = e.touches[ 0 ].clientX;
				departY = e.touches[ 0 ].clientY;
			}
		}, { passive: true } );
		vis.addEventListener( 'touchend', function ( e ) {
			if ( null === departX || total < 2 ) {
				return;
			}
			var dx = e.changedTouches[ 0 ].clientX - departX;
			var dy = e.changedTouches[ 0 ].clientY - departY;
			departX = null;
			if ( Math.abs( dx ) > 50 && Math.abs( dx ) > Math.abs( dy ) ) {
				afficher( courant + ( dx < 0 ? 1 : -1 ) );
				relancer();
			}
		}, { passive: true } );
	}

	/* ---------------------------------------------------------------------
	 * 2. Cartes d'album : aperçu des photos au survol ou au focus clavier
	 * ------------------------------------------------------------------ */
	function apercus() {
		if ( reduit || document.querySelector( '.lpg--calme' ) ) {
			return;
		}
		Array.prototype.forEach.call( document.querySelectorAll( '[data-lpg-apercu]' ), function ( carte ) {
			var couches = carte.querySelectorAll( '.lpg-carte__apercu' );
			var points = carte.querySelectorAll( '.lpg-carte__points i' );
			var n = couches.length;
			var pas = 0;
			var minuteur = null;
			function montrer( k ) {
				pas = k;
				Array.prototype.forEach.call( couches, function ( c, j ) {
					c.classList.toggle( 'lpg-visible', j === k - 1 );
				} );
				Array.prototype.forEach.call( points, function ( p, j ) {
					p.classList.toggle( 'lpg-actif', j === k );
				} );
			}
			function demarrer() {
				if ( minuteur ) {
					return;
				}
				montrer( 1 );
				minuteur = window.setInterval( function () {
					montrer( ( pas + 1 ) % ( n + 1 ) );
				}, 1100 );
			}
			function arreter() {
				window.clearInterval( minuteur );
				minuteur = null;
				montrer( 0 );
			}
			montrer( 0 );
			carte.addEventListener( 'mouseenter', demarrer );
			carte.addEventListener( 'mouseleave', arreter );
			carte.addEventListener( 'focusin', demarrer );
			carte.addEventListener( 'focusout', arreter );
		} );
	}

	/* ---------------------------------------------------------------------
	 * 3. Bandeau : les couvertures des derniers albums se succèdent en fondu (toutes les 6 secondes)
	 * ------------------------------------------------------------------ */
	function bandeau() {
		var zone = document.querySelector( '.lpg-hero__photo--diapo' );
		if ( ! zone || reduit || document.querySelector( '.lpg--calme' ) ) {
			return;
		}
		var images = zone.querySelectorAll( 'img' );
		var k = 0;
		zone.classList.add( 'lpg-js' );
		images[ 0 ].classList.add( 'lpg-visible' );
		window.setInterval( function () {
			if ( document.hidden ) {
				return;
			}
			images[ k ].classList.remove( 'lpg-visible' );
			k = ( k + 1 ) % images.length;
			images[ k ].classList.add( 'lpg-visible' );
		}, 6000 );
	}

	/* ---------------------------------------------------------------------
	 * 4. Onglets des catégories regroupés dans un menu déroulant (petit écran, réglable)
	 *    Bouton aria-expanded, Échap ou clic ailleurs ferme, flèches ↑ ↓ / Début / Fin dans la liste,
	 *    focus rendu au bouton. Sans script : onglets qui défilent horizontalement.
	 * ------------------------------------------------------------------ */
	function filtres() {
		var nav = document.querySelector( '[data-lpg-filtres]' );
		if ( ! nav || nav.getAttribute( 'data-lpg-pret' ) || ! window.matchMedia ) {
			return;
		}
		nav.setAttribute( 'data-lpg-pret', '1' );
		var bouton = nav.querySelector( '.lpg-filtres__bouton' );
		var liste = nav.querySelector( '.lpg-filtres__liste' );
		var requetes = { tablette: '(max-width: 1023.98px)', mobile: '(max-width: 767.98px)', toujours: 'all', jamais: 'not all' };
		var mq = window.matchMedia( requetes[ nav.getAttribute( 'data-lpg-filtres' ) ] || requetes.tablette );
		var liens = function () {
			return Array.prototype.slice.call( liste.querySelectorAll( 'a' ) );
		};

		function ouvrir( oui, focusSur ) {
			nav.classList.toggle( 'lpg-filtres--ouvert', oui );
			bouton.setAttribute( 'aria-expanded', oui ? 'true' : 'false' );
			if ( oui && focusSur ) {
				var l = liens();
				var actif = liste.querySelector( '[aria-current]' );
				( 'dernier' === focusSur ? l[ l.length - 1 ] : ( 'premier' === focusSur ? l[ 0 ] : actif || l[ 0 ] ) ).focus();
			}
		}

		function appliquer() {
			var menu = mq.matches;
			nav.classList.add( 'lpg-filtres--js' );
			nav.classList.toggle( 'lpg-filtres--menu', menu );
			bouton.hidden = ! menu;
			if ( ! menu ) {
				ouvrir( false );
			}
		}

		bouton.addEventListener( 'click', function () {
			ouvrir( 'true' !== bouton.getAttribute( 'aria-expanded' ), false );
		} );
		bouton.addEventListener( 'keydown', function ( e ) {
			if ( 'ArrowDown' === e.key || 'ArrowUp' === e.key ) {
				e.preventDefault();
				ouvrir( true, 'ArrowUp' === e.key ? 'dernier' : 'actif' );
			}
		} );
		nav.addEventListener( 'keydown', function ( e ) {
			if ( ! nav.classList.contains( 'lpg-filtres--ouvert' ) ) {
				return;
			}
			if ( 'Escape' === e.key ) {
				e.preventDefault();
				ouvrir( false );
				bouton.focus();
				return;
			}
			var l = liens();
			var i = l.indexOf( document.activeElement );
			if ( i < 0 || e.target === bouton ) {
				return;
			}
			var cible = { ArrowDown: l[ ( i + 1 ) % l.length ], ArrowUp: l[ ( i - 1 + l.length ) % l.length ], Home: l[ 0 ], End: l[ l.length - 1 ] }[ e.key ];
			if ( cible ) {
				e.preventDefault();
				cible.focus();
			}
		} );
		// Clic ou focus en dehors du menu : il se referme.
		document.addEventListener( 'click', function ( e ) {
			if ( ! nav.contains( e.target ) ) {
				ouvrir( false );
			}
		} );
		nav.addEventListener( 'focusout', function ( e ) {
			if ( e.relatedTarget && ! nav.contains( e.relatedTarget ) ) {
				ouvrir( false );
			}
		} );
		if ( mq.addEventListener ) {
			mq.addEventListener( 'change', appliquer );
		} else if ( mq.addListener ) {
			mq.addListener( appliquer );
		}
		appliquer();

		// Barre d'onglets qui défile : l'onglet actif reste visible.
		var actif = liste.querySelector( '[aria-current]' );
		if ( actif && ! mq.matches && liste.scrollWidth > liste.clientWidth ) {
			liste.scrollLeft = actif.offsetLeft - ( liste.clientWidth - actif.offsetWidth ) / 2;
		}
	}

	/* ---------------------------------------------------------------------
	 * 5. Après un filtre ou un changement de page (adresse « #lpg-albums ») : arriver directement sur la liste
	 * ------------------------------------------------------------------ */
	function arriveeListe() {
		var section = document.getElementById( 'lpg-albums' );
		if ( ! section || '#lpg-albums' !== window.location.hash ) {
			return;
		}
		// Le thème active un défilement « doux » qui s'interrompt au chargement : saut immédiat, puis réglage rendu.
		var aller = function () {
			var racine = document.documentElement;
			var avant = racine.style.scrollBehavior;
			racine.style.scrollBehavior = 'auto';
			window.scrollTo( 0, Math.max( 0, section.getBoundingClientRect().top + window.pageYOffset ) );
			racine.style.scrollBehavior = avant;
		};
		aller();
		// La page peut encore bouger (polices, images) : on réaligne pendant 2,5 s au plus,
		// et on s'arrête dès que le visiteur fait défiler lui-même.
		var fini = false;
		var stop = function () {
			fini = true;
		};
		[ 'wheel', 'touchstart', 'keydown', 'mousedown' ].forEach( function ( t ) {
			window.addEventListener( t, stop, { once: true, passive: true } );
		} );
		var realigner = function () {
			if ( ! fini && Math.abs( section.getBoundingClientRect().top ) > 2 ) {
				aller();
			}
		};
		if ( window.ResizeObserver ) {
			var ro = new window.ResizeObserver( realigner );
			ro.observe( document.body );
			window.setTimeout( function () {
				ro.disconnect();
				stop();
			}, 2500 );
		}
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( realigner );
		}
		window.addEventListener( 'load', realigner, { once: true } );
	}

	function demarrer() {
		visionneuse();
		apercus();
		bandeau();
		filtres();
		arriveeListe();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', demarrer );
	} else {
		demarrer();
	}
}() );
