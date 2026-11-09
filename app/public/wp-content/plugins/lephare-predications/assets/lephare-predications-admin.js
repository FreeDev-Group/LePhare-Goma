/**
 * LePhare – Prédications & Ressources (administration).
 *
 * 1. Boutons « Médiathèque… » de la Fiche prédication : ouvrent la Médiathèque WordPress
 *    filtrée par type (vidéo, audio, PDF) et remplissent le champ correspondant.
 * 2. La boîte « Fiche prédication » n'est affichée que si une catégorie Prédications
 *    (ou un de ses Types) est cochée. Fonctionne dans l'éditeur classique et dans Gutenberg.
 */
( function ( $ ) {
	'use strict';

	var reglages = window.lephPred || { categories: [], choisir: 'Utiliser ce fichier' };
	var categories = ( reglages.categories || [] ).map( function ( id ) {
		return parseInt( id, 10 );
	} );

	/* 1. Sélection dans la Médiathèque ----------------------------------- */
	$( document ).on( 'click', '.lph-choisir', function ( e ) {
		e.preventDefault();
		var bouton = $( this );
		var cible = $( '#' + bouton.data( 'cible' ) );
		var nom = bouton.data( 'nom' ) ? $( '#' + bouton.data( 'nom' ) ) : null;

		var cadre = wp.media( {
			title: bouton.data( 'titre' ),
			button: { text: reglages.choisir },
			library: { type: bouton.data( 'type' ) },
			multiple: false
		} );

		cadre.on( 'select', function () {
			var fichier = cadre.state().get( 'selection' ).first().toJSON();
			if ( nom ) {
				// PDF : on garde l'identifiant du fichier, on affiche son nom.
				cible.val( fichier.id ).trigger( 'change' );
				nom.val( fichier.filename || fichier.title );
			} else {
				cible.val( fichier.url ).trigger( 'change' );
			}
			bouton.trigger( 'focus' );
		} );

		cadre.open();
	} );

	$( document ).on( 'click', '.lph-retirer', function ( e ) {
		e.preventDefault();
		$( '#' + $( this ).data( 'cible' ) ).val( '' ).trigger( 'change' );
		$( '#' + $( this ).data( 'nom' ) ).val( '' );
	} );

	/* 2. Afficher la boîte seulement pour les prédications ------------- */
	function estPredication( ids ) {
		return ids.some( function ( id ) {
			return categories.indexOf( parseInt( id, 10 ) ) !== -1;
		} );
	}

	function basculer( visible ) {
		var boite = document.getElementById( 'lph-fiche-predication' );
		if ( boite ) {
			boite.style.display = visible ? '' : 'none';
		}
	}

	if ( ! categories.length ) {
		return;
	}

	// Éditeur classique.
	function casesCochees() {
		return $( '#categorychecklist input:checked' ).map( function () {
			return this.value;
		} ).get();
	}

	$( function () {
		if ( $( '#categorychecklist' ).length ) {
			basculer( estPredication( casesCochees() ) );
			$( document ).on( 'change', '#categorychecklist input', function () {
				basculer( estPredication( casesCochees() ) );
			} );
		}
	} );

	// Éditeur de blocs (Gutenberg).
	if ( window.wp && wp.data && wp.data.select && wp.data.subscribe ) {
		var dernier = null;
		wp.data.subscribe( function () {
			var editeur = wp.data.select( 'core/editor' );
			if ( ! editeur || typeof editeur.getEditedPostAttribute !== 'function' ) {
				return;
			}
			var ids = editeur.getEditedPostAttribute( 'categories' ) || [];
			var visible = estPredication( ids );
			if ( visible !== dernier ) {
				dernier = visible;
				basculer( visible );
			}
		} );
	}
}( jQuery ) );
