<?php
/**
 * Plugin Name:       LePhare – Galerie
 * Description:       Galerie média (#08) : page /galerie/ (widget Elementor « Galerie » ou shortcode [lephare_galerie]), albums classés par catégorie et visionneuse plein écran maison. Un album = un Article de la catégorie « Galerie » (doc #13 d'Aaron : pas de type de contenu personnalisé).
 * Version:           1.1.1
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            FREEDEV – LePhare/Goma
 * License:           GPL-2.0-or-later
 * Text Domain:       lephare-galerie
 *
 * Gestion par les admins, sans code :
 * Articles → Ajouter, cocher une sous-catégorie de « Galerie » (Cultes, Conférences, Activités des jeunes,
 * Activités, Événements), ajouter un bloc « Galerie » et y choisir les photos dans la Médiathèque,
 * choisir l'image mise en avant (couverture), publier.
 *
 * Filtres de la page (dans l'adresse, fonctionnent sans JavaScript) : ?lpg_cat=… &lpg_page=2
 */

defined( 'ABSPATH' ) || exit;

const LEPHARE_GAL_VERSION = '1.1.1';

/* =========================================================================
 * 1. Catégories et albums
 * ====================================================================== */

/**
 * Ordre d'affichage conseillé des sous-catégories (les autres suivent, par nom).
 *
 * @return string[]
 */
function lephare_gal_ordre_categories() {
	return array( 'cultes', 'conferences', 'activites-des-jeunes', 'activites', 'evenements' );
}

/**
 * Catégorie parente « Galerie » (slug galerie), ou null.
 *
 * @return WP_Term|null
 */
function lephare_gal_parent() {
	$t = get_category_by_slug( 'galerie' );
	return $t instanceof WP_Term ? $t : null;
}

/**
 * IDs de « Galerie » et de toutes ses sous-catégories.
 *
 * @return int[]
 */
function lephare_gal_ids_categories() {
	static $ids = null;
	if ( null === $ids ) {
		$ids    = array();
		$parent = lephare_gal_parent();
		if ( $parent ) {
			$enfants = get_term_children( $parent->term_id, 'category' );
			$ids     = array_merge( array( (int) $parent->term_id ), is_wp_error( $enfants ) ? array() : array_map( 'intval', $enfants ) );
		}
	}
	return $ids;
}

/**
 * Sous-catégories de « Galerie » : [ slug => WP_Term ], dans l'ordre conseillé.
 *
 * @param bool     $vides Garder les catégories sans album ?
 * @param string[] $slugs Restreindre à ces slugs (dans cet ordre) ; vide = toutes.
 * @return WP_Term[]
 */
function lephare_gal_categories( $vides = false, $slugs = array() ) {
	$parent = lephare_gal_parent();
	if ( ! $parent ) {
		return array();
	}
	$termes = get_terms( array( 'taxonomy' => 'category', 'parent' => $parent->term_id, 'hide_empty' => false, 'orderby' => 'name' ) );
	if ( is_wp_error( $termes ) ) {
		return array();
	}
	$par_slug = array();
	foreach ( $termes as $t ) {
		if ( $vides || lephare_gal_compter( array( (int) $t->term_id ) ) > 0 ) {
			$par_slug[ $t->slug ] = $t;
		}
	}
	$ordre = $slugs ? $slugs : array_merge( lephare_gal_ordre_categories(), array_keys( $par_slug ) );
	$res   = array();
	foreach ( $ordre as $s ) {
		if ( isset( $par_slug[ $s ] ) && ! isset( $res[ $s ] ) ) {
			$res[ $s ] = $par_slug[ $s ];
		}
	}
	return $res;
}

/**
 * Nombre d'albums publiés dans des catégories (sous-catégories comprises).
 *
 * @param int[] $ids Catégories.
 * @return int
 */
function lephare_gal_compter( $ids ) {
	static $memo = array();
	$cle = implode( ',', $ids );
	if ( ! isset( $memo[ $cle ] ) ) {
		$q            = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'category__in' => $ids ? $ids : array( 0 ), 'posts_per_page' => 1, 'fields' => 'ids', 'ignore_sticky_posts' => true ) );
		$memo[ $cle ] = (int) $q->found_posts;
	}
	return $memo[ $cle ];
}

/**
 * Cet article est-il un album (article d'une catégorie de la Galerie) ?
 *
 * @param int $post_id Article.
 * @return bool
 */
function lephare_gal_est_album( $post_id ) {
	$ids = lephare_gal_ids_categories();
	return $ids && 'post' === get_post_type( $post_id ) && has_category( $ids, $post_id );
}

/**
 * Photos d'un album, dans l'ordre choisi : blocs « Galerie » et « Image », puis shortcode [gallery ids="…"].
 *
 * @param WP_Post $p Album.
 * @return int[] IDs de pièces jointes (images).
 */
function lephare_gal_photos( $p ) {
	$ids     = array();
	$parcour = static function ( $blocs ) use ( &$parcour, &$ids ) {
		foreach ( $blocs as $b ) {
			if ( 'core/image' === $b['blockName'] && ! empty( $b['attrs']['id'] ) ) {
				$ids[] = (int) $b['attrs']['id'];
			}
			if ( 'core/gallery' === $b['blockName'] && ! empty( $b['attrs']['ids'] ) && empty( $b['innerBlocks'] ) ) {
				$ids = array_merge( $ids, array_map( 'intval', (array) $b['attrs']['ids'] ) );
			}
			if ( ! empty( $b['innerBlocks'] ) ) {
				$parcour( $b['innerBlocks'] );
			}
		}
	};
	$parcour( parse_blocks( (string) $p->post_content ) );
	if ( preg_match_all( '~\[gallery[^\]]*\bids="([\d,\s]+)"~', (string) $p->post_content, $m ) ) {
		foreach ( $m[1] as $liste ) {
			$ids = array_merge( $ids, array_map( 'intval', explode( ',', $liste ) ) );
		}
	}
	return array_values( array_unique( array_filter( $ids, 'wp_attachment_is_image' ) ) );
}

/**
 * Date en français : « 9 novembre 2026 ».
 *
 * @param int $ts Horodatage.
 * @return string
 */
function lephare_gal_date_fr( $ts ) {
	$mois = array( 1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' );
	return wp_date( 'j', $ts ) . ' ' . $mois[ (int) wp_date( 'n', $ts ) ] . ' ' . wp_date( 'Y', $ts );
}

/**
 * Données d'un album prêtes à afficher.
 *
 * @param WP_Post $p Album.
 * @return array
 */
function lephare_gal_donnees( $p ) {
	$cat    = null;
	$parent = lephare_gal_parent();
	foreach ( get_the_category( $p->ID ) as $c ) {
		if ( $parent && (int) $c->parent === (int) $parent->term_id ) {
			$cat = $c;
			break;
		}
	}
	$photos = lephare_gal_photos( $p );
	$ts     = get_post_timestamp( $p );
	$cover  = (int) get_post_thumbnail_id( $p );
	return array(
		'id'         => $p->ID,
		'titre'      => get_the_title( $p ),
		'url'        => get_permalink( $p ),
		'date_iso'   => wp_date( 'c', $ts ),
		'date_txt'   => lephare_gal_date_fr( $ts ),
		'categorie'  => $cat ? $cat->name : '',
		'cat_slug'   => $cat ? $cat->slug : '',
		'photos'     => $photos,
		'nb'         => count( $photos ),
		'couverture' => $cover ? $cover : ( $photos[0] ?? 0 ),
		'extrait'    => has_excerpt( $p ) ? $p->post_excerpt : '',
	);
}

/**
 * Texte alternatif d'une photo (celui de la Médiathèque, sinon le titre de l'album).
 *
 * @param int    $id    Pièce jointe.
 * @param string $repli Texte de repli.
 * @return string
 */
function lephare_gal_alt( $id, $repli = '' ) {
	$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	return '' !== $alt ? $alt : $repli;
}

/**
 * Petite icône en ligne (décorative).
 *
 * @param string $nom photos | calendrier | fleche | retour | gauche | droite | fermer.
 * @return string
 */
function lephare_gal_picto( $nom ) {
	$t = array(
		'photos'     => '<rect x="3" y="5" width="15" height="13" rx="2"/><path d="M7 21h12a2 2 0 0 0 2-2V9"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m18 15-4-4-7 7"/>',
		'calendrier' => '<rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
		'fleche'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'retour'     => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
		'gauche'     => '<path d="M15 5l-7 7 7 7"/>',
		'droite'     => '<path d="M9 5l7 7-7 7"/>',
		'fermer'     => '<path d="M6 6l12 12M18 6 6 18"/>',
		'lecture'    => '<path d="M8 5.5v13l10.5-6.5z" fill="currentColor" stroke="none"/>',
		'pause'      => '<path d="M8 5h3v14H8zM13 5h3v14h-3z" fill="currentColor" stroke="none"/>',
		'plein'      => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
	);
	return '<svg class="lpg-picto lpg-picto--' . esc_attr( $nom ) . '" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $t[ $nom ] ?? '' ) . '</svg>';
}

/* =========================================================================
 * 2. Les albums ne sont jamais des actualités, ni dans le flux RSS du blog
 * ====================================================================== */

add_filter(
	'lephare_actu_categories_exclues',
	static function ( $ids ) {
		return array_merge( (array) $ids, lephare_gal_ids_categories() );
	}
);

add_action(
	'pre_get_posts',
	static function ( $q ) {
		if ( is_admin() || ! $q->is_main_query() || ! ( $q->is_home() || $q->is_feed() ) || $q->is_category() ) {
			return;
		}
		$ids = lephare_gal_ids_categories();
		if ( $ids ) {
			$q->set( 'category__not_in', array_merge( (array) $q->get( 'category__not_in' ), $ids ) );
		}
	}
);

/* =========================================================================
 * 3. Styles et scripts (chargés seulement là où ils servent)
 * ====================================================================== */

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_register_style( 'lephare-galerie', plugins_url( 'assets/lephare-galerie.css', __FILE__ ), array(), LEPHARE_GAL_VERSION );
		wp_register_script( 'lephare-galerie', plugins_url( 'assets/lephare-galerie.js', __FILE__ ), array(), LEPHARE_GAL_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		if ( ! is_singular() ) {
			return;
		}
		$id      = get_queried_object_id();
		$contenu = (string) get_post_field( 'post_content', $id ) . (string) get_post_meta( $id, '_elementor_data', true );
		if ( lephare_gal_est_album( $id ) || false !== strpos( $contenu, 'lephare_galerie' ) || false !== strpos( $contenu, 'lephare-galerie' ) ) {
			wp_enqueue_style( 'lephare-galerie' );
			wp_enqueue_script( 'lephare-galerie' );
		}
	}
);

/* =========================================================================
 * 4. Réglages (même principe que la page Actualités : schéma → widget Elementor / écran WordPress)
 *    Champ : [ type, libellé, défaut, vide autorisé (= masqué), condition Elementor ].
 * ====================================================================== */

/**
 * Réglages de la page Galerie (widget Elementor « Galerie » ou attributs du shortcode).
 *
 * @return array
 */
function lephare_gal_schema_page() {
	return array(
		'bandeau'  => array(
			'label'  => 'Bandeau (haut de page)',
			'champs' => array(
				'surtitre'     => array( 'texte', 'Petite étiquette au-dessus du titre (vide = masquée)', 'Église LePhare/Goma', true ),
				'titre'        => array( 'texte', 'Titre de la page (H1)', 'Galerie' ),
				'intro'        => array( 'zone', 'Phrase d’introduction (vide = masquée)', 'La vie de l’église en images : cultes, conférences, activités et événements.', true ),
				'hero_photo'   => array( 'oui', 'Photo en arrière-plan du bandeau', true ),
				'image_fond'   => array( 'media', 'Photo de fond (vide = couverture de l’album le plus récent)', 0, false, array( 'hero_photo' => 'yes' ) ),
				'hero_reperes' => array( 'oui', 'Afficher les repères (nombre d’albums et de photos)', true ),
				'hero_diaporama' => array( 'oui', 'Faire défiler en fondu les couvertures des derniers albums (si aucune photo n’est choisie)', true, false, array( 'hero_photo' => 'yes' ) ),
			),
		),
		'tuiles'   => array(
			'label'  => 'Tuiles des catégories',
			'champs' => array(
				'tuiles'       => array( 'oui', 'Tuiles des catégories sous le bandeau (éventail de photos)', true ),
				'tuiles_titre' => array( 'texte', 'Nom de la zone (lu par les lecteurs d’écran)', 'Parcourir par catégorie', false, array( 'tuiles' => 'yes' ) ),
			),
		),
		'une'      => array(
			'label'  => 'Dernier album',
			'champs' => array(
				'une'          => array( 'oui', 'Mettre en avant le dernier album (mosaïque et diaporama)', true ),
				'une_surtitre' => array( 'texte', 'Petite étiquette (vide = masquée)', 'Dernier album', true, array( 'une' => 'yes' ) ),
				'une_bouton'   => array( 'texte', 'Bouton vers l’album', 'Voir l’album', false, array( 'une' => 'yes' ) ),
				'une_diapo'    => array( 'texte', 'Bouton du diaporama', 'Lancer le diaporama', false, array( 'une' => 'yes' ) ),
			),
		),
		'albums'   => array(
			'label'  => 'Albums',
			'champs' => array(
				'categories'   => array( 'categories', 'Catégories affichées, dans cet ordre (vide = toutes les sous-catégories de « Galerie »)', '', true ),
				'par_page'     => array( 'nombre', 'Albums par page (un multiple de 3 remplit les rangées)', 12 ),
				'titre_liste'  => array( 'texte', 'Titre de la liste', 'Tous les albums' ),
				'filtres'      => array( 'oui', 'Onglets de catégories', true ),
				'txt_tout'     => array( 'texte', 'Onglet « Tout »', 'Tout', false, array( 'filtres' => 'yes' ) ),
				'mot_album'    => array( 'texte', 'Mot « album » (singulier)', 'album' ),
				'mot_albums'   => array( 'texte', 'Mot « albums » (pluriel)', 'albums' ),
				'mot_photo'    => array( 'texte', 'Mot « photo » (singulier)', 'photo' ),
				'mot_photos'   => array( 'texte', 'Mot « photos » (pluriel)', 'photos' ),
				'apercu'       => array( 'oui', 'Aperçu des photos de l’album au survol d’une carte', true ),
			),
		),
		'messages' => array(
			'label'  => 'Messages',
			'champs' => array(
				'txt_vide'     => array( 'texte', 'Aucun album publié', 'Aucun album n’est encore publié.' ),
				'txt_cat_vide' => array( 'texte', 'Catégorie sans album', 'Aucun album dans cette catégorie pour le moment.' ),
				'txt_page_vide' => array( 'texte', 'Page de la liste au-delà de la dernière', 'Cette page ne contient aucun album.' ),
			),
		),
		'effets'   => array(
			'label'  => 'Animations',
			'champs' => array(
				'animations' => array( 'oui', 'Animations douces (fondu du bandeau, aperçus, éventails). Toujours coupées si l’appareil demande moins de mouvement.', true ),
			),
		),
	);
}

/**
 * Réglages de la page d'un album (écran WordPress « Articles → Page d'un album »).
 *
 * @return array
 */
function lephare_gal_schema_album() {
	return array(
		'album'       => array(
			'label'  => 'Page d’un album',
			'champs' => array(
				'alb_retour'     => array( 'texte', 'Lien de retour vers la galerie', 'Toute la galerie' ),
				'alb_titre_mur'  => array( 'texte', 'Titre (lu par les lecteurs d’écran) de la grille de photos', 'Photos de l’album' ),
				'alb_autres'     => array( 'oui', 'Section « Autres albums »', true ),
				'alb_autres_titre' => array( 'texte', 'Titre de la section « Autres albums »', 'Autres albums' ),
				'alb_autres_nb'  => array( 'nombre', 'Nombre d’albums suggérés (1 à 6)', 3 ),
				'alb_diapo'      => array( 'texte', 'Bouton du diaporama dans le bandeau (vide = masqué)', 'Lancer le diaporama', true ),
			),
		),
		'visionneuse' => array(
			'label'  => 'Visionneuse',
			'champs' => array(
				'vis_agrandir'  => array( 'texte', 'Texte lu pour ouvrir une photo (%1$d = numéro, %2$d = total)', 'Agrandir la photo %1$d sur %2$d' ),
				'vis_fermer'    => array( 'texte', 'Bouton Fermer', 'Fermer la visionneuse' ),
				'vis_prec'      => array( 'texte', 'Bouton Précédente', 'Photo précédente' ),
				'vis_suiv'      => array( 'texte', 'Bouton Suivante', 'Photo suivante' ),
				'vis_legende'   => array( 'oui', 'Afficher la légende (légende de la Médiathèque, sinon texte alternatif)', true ),
				'vis_bande'     => array( 'oui', 'Bande de vignettes en bas de la visionneuse', true ),
				'vis_bande_titre' => array( 'texte', 'Nom de la bande de vignettes (lu par les lecteurs d’écran)', 'Toutes les photos' ),
				'vis_photo'     => array( 'texte', 'Texte lu pour une vignette (%1$d = numéro)', 'Photo %1$d' ),
				'vis_lecture'   => array( 'texte', 'Bouton Lancer le diaporama', 'Lancer le diaporama' ),
				'vis_pause'     => array( 'texte', 'Bouton Pause', 'Mettre le diaporama en pause' ),
				'vis_duree'     => array( 'nombre', 'Durée de chaque photo dans le diaporama (secondes, 2 à 15)', 5 ),
				'vis_plein'     => array( 'texte', 'Bouton Plein écran', 'Plein écran' ),
			),
		),
	);
}

/**
 * Valeurs par défaut d'un schéma.
 *
 * @param array $schema Schéma.
 * @return array
 */
function lephare_gal_defauts( $schema ) {
	$o = array();
	foreach ( $schema as $section ) {
		foreach ( $section['champs'] as $cle => $c ) {
			$o[ $cle ] = $c[2];
		}
	}
	return $o;
}

/**
 * Nettoie des réglages selon un schéma.
 *
 * @param array $brut   Réglages bruts.
 * @param array $schema Schéma.
 * @return array
 */
function lephare_gal_nettoyer( $brut, $schema ) {
	$o = array();
	foreach ( $schema as $section ) {
		foreach ( $section['champs'] as $cle => $c ) {
			$type   = $c[0];
			$defaut = $c[2];
			$v      = $brut[ $cle ] ?? $defaut;
			switch ( $type ) {
				case 'oui':
					$o[ $cle ] = is_bool( $v ) ? $v : in_array( strtolower( (string) $v ), array( '1', 'yes', 'oui', 'true', 'on' ), true );
					break;
				case 'nombre':
				case 'media':
					$o[ $cle ] = is_array( $v ) ? (int) ( $v['id'] ?? 0 ) : (int) $v;
					break;
				default:
					$v         = 'zone' === $type ? sanitize_textarea_field( (string) $v ) : sanitize_text_field( is_array( $v ) ? implode( ',', $v ) : (string) $v );
					$o[ $cle ] = ( '' === $v && empty( $c[3] ) ) ? $defaut : $v;
			}
		}
	}
	return $o;
}

/**
 * Réglages de la page d'un album (option « lephare_gal_album »).
 *
 * @return array
 */
function lephare_gal_reglages_album() {
	$o                  = lephare_gal_nettoyer( (array) get_option( 'lephare_gal_album', array() ), lephare_gal_schema_album() );
	$o['alb_autres_nb'] = max( 1, min( 6, (int) $o['alb_autres_nb'] ) );
	$o['vis_duree']     = max( 2, min( 15, (int) $o['vis_duree'] ) );
	return $o;
}

add_action(
	'admin_init',
	static function () {
		register_setting(
			'lephare_gal_album',
			'lephare_gal_album',
			array(
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => static function ( $brut ) {
					$brut = is_array( $brut ) ? $brut : array();
					foreach ( lephare_gal_schema_album() as $section ) {
						foreach ( $section['champs'] as $cle => $c ) {
							if ( 'oui' === $c[0] && ! isset( $brut[ $cle ] ) ) {
								$brut[ $cle ] = '0';
							}
						}
					}
					return lephare_gal_nettoyer( wp_unslash( $brut ), lephare_gal_schema_album() );
				},
			)
		);
	}
);

add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'edit.php', 'Page d’un album', 'Page d’un album', 'manage_options', 'lephare-gal-album', 'lephare_gal_ecran_album' );
	}
);

/**
 * Écran « Articles → Page d'un album ».
 */
function lephare_gal_ecran_album() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = lephare_gal_reglages_album();
	echo '<div class="wrap"><h1>Page d’un album</h1>';
	echo '<p>Un album est un article de la catégorie « Galerie » (Cultes, Conférences, Activités des jeunes, Activités ou Événements). Ses photos se choisissent dans l’article avec le bloc « Galerie » ; sa couverture est l’image mise en avant. Ces réglages s’appliquent à toutes les pages d’album. La page /galerie/ se règle dans Elementor (widget « Galerie »).</p>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'lephare_gal_album' );
	foreach ( lephare_gal_schema_album() as $section ) {
		echo '<h2>' . esc_html( $section['label'] ) . '</h2><table class="form-table" role="presentation"><tbody>';
		foreach ( $section['champs'] as $cle => $c ) {
			$id  = 'lpg-' . $cle;
			$nom = 'lephare_gal_album[' . $cle . ']';
			echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $c[1] ) . '</label></th><td>';
			if ( 'oui' === $c[0] ) {
				echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nom ) . '" value="1"' . checked( $o[ $cle ], true, false ) . '>';
			} elseif ( 'nombre' === $c[0] ) {
				echo '<input type="number" class="small-text" min="1" max="15" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nom ) . '" value="' . (int) $o[ $cle ] . '">';
			} else {
				echo '<input type="text" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nom ) . '" value="' . esc_attr( $o[ $cle ] ) . '">';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}
	submit_button( 'Enregistrer les réglages' );
	echo '</form></div>';
}

/* =========================================================================
 * 5. Page Galerie : shortcode [lephare_galerie] et widget Elementor
 * ====================================================================== */

add_shortcode(
	'lephare_galerie',
	static function ( $atts ) {
		return lephare_gal_rendu( (array) $atts );
	}
);

/**
 * Pluriel simple.
 *
 * @param int    $n   Nombre.
 * @param string $un  Singulier.
 * @param string $plu Pluriel.
 * @return string
 */
function lephare_gal_nb( $n, $un, $plu ) {
	return (int) $n . ' ' . ( $n > 1 ? $plu : $un );
}

/**
 * Données d'une photo pour la visionneuse : grande image (srcset), vignette, texte alternatif, légende.
 *
 * @param int    $id      Pièce jointe.
 * @param string $repli   Texte alternatif de repli (titre de l'album).
 * @param bool   $legende Afficher la légende ?
 * @return array
 */
function lephare_gal_item( $id, $repli, $legende = true ) {
	$grand = wp_get_attachment_image_src( $id, 'full' );
	$petit = wp_get_attachment_image_src( $id, 'thumbnail' );
	$alt   = lephare_gal_alt( $id, $repli );
	$leg   = trim( (string) wp_get_attachment_caption( $id ) );
	return array(
		'src'      => $grand ? $grand[0] : '',
		'srcset'   => (string) wp_get_attachment_image_srcset( $id, 'full' ),
		'vignette' => $petit ? $petit[0] : '',
		'alt'      => $alt,
		'legende'  => $legende ? ( '' !== $leg ? $leg : $alt ) : '',
	);
}

/**
 * Visionneuse plein écran : boutons, compteur, barre de diaporama, bande de vignettes (remplie par le script)
 * et la liste des photos en JSON. Masquée tant qu'elle n'est pas ouverte.
 *
 * @param string $titre  Titre de l'album (nom de la fenêtre pour les lecteurs d'écran).
 * @param int[]  $photos Pièces jointes.
 * @param array  $r      Réglages (textes de la visionneuse).
 * @return string
 */
function lephare_gal_visionneuse_html( $titre, $photos, $r ) {
	$items = array();
	foreach ( $photos as $id ) {
		$items[] = lephare_gal_item( $id, $titre, $r['vis_legende'] );
	}
	$out  = '<div class="lpg-visionneuse" role="dialog" aria-modal="true" aria-labelledby="lpg-vis-titre" hidden data-lpg-visionneuse data-lpg-duree="' . (int) $r['vis_duree'] . '" data-lpg-txt-lecture="' . esc_attr( $r['vis_lecture'] ) . '" data-lpg-txt-pause="' . esc_attr( $r['vis_pause'] ) . '" data-lpg-txt-photo="' . esc_attr( $r['vis_photo'] ) . '">';
	$out .= '<h2 class="screen-reader-text" id="lpg-vis-titre">' . esc_html( $titre ) . '</h2>';
	$out .= '<div class="lpg-vis__haut"><p class="lpg-vis__compteur" aria-live="polite" data-lpg-compteur></p><div class="lpg-vis__outils">';
	$out .= '<button type="button" class="lpg-vis__btn lpg-vis__lecture" data-lpg-lecture aria-pressed="false" aria-label="' . esc_attr( $r['vis_lecture'] ) . '">' . lephare_gal_picto( 'lecture' ) . lephare_gal_picto( 'pause' ) . '</button>';
	$out .= '<button type="button" class="lpg-vis__btn lpg-vis__plein" data-lpg-plein aria-label="' . esc_attr( $r['vis_plein'] ) . '" hidden>' . lephare_gal_picto( 'plein' ) . '</button>';
	$out .= '<button type="button" class="lpg-vis__btn lpg-vis__fermer" data-lpg-fermer aria-label="' . esc_attr( $r['vis_fermer'] ) . '">' . lephare_gal_picto( 'fermer' ) . '</button></div></div>';
	$out .= '<div class="lpg-vis__progression" aria-hidden="true"><span data-lpg-progression></span></div>';
	$out .= '<button type="button" class="lpg-vis__btn lpg-vis__prec" data-lpg-prec aria-label="' . esc_attr( $r['vis_prec'] ) . '">' . lephare_gal_picto( 'gauche' ) . '</button>';
	$out .= '<figure class="lpg-vis__scene"><img class="lpg-vis__img" alt="" data-lpg-image><figcaption class="lpg-vis__legende" data-lpg-legende></figcaption></figure>';
	$out .= '<button type="button" class="lpg-vis__btn lpg-vis__suiv" data-lpg-suiv aria-label="' . esc_attr( $r['vis_suiv'] ) . '">' . lephare_gal_picto( 'droite' ) . '</button>';
	if ( $r['vis_bande'] && count( $items ) > 1 ) {
		$out .= '<div class="lpg-vis__bande" role="group" aria-label="' . esc_attr( $r['vis_bande_titre'] ) . '" data-lpg-bande></div>';
	}
	$out .= '<script type="application/json" data-lpg-items>' . wp_json_encode( $items, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
	return $out . '</div>';
}

/**
 * Carte d'album : couverture (avec aperçu des photos au survol), nombre de photos, catégorie, titre, date.
 * Effet « pile d'album » derrière la carte. Toute la carte est cliquable.
 *
 * @param array  $d      Données.
 * @param array  $o      Options (mots photo / photos, aperçu).
 * @param string $niveau Balise du titre.
 * @return string
 */
function lephare_gal_carte( $d, $o, $niveau = 'h3' ) {
	$sizes   = '(max-width: 767px) 100vw, (max-width: 1100px) 50vw, 380px';
	$img     = $d['couverture'] ? wp_get_attachment_image( $d['couverture'], 'medium_large', false, array( 'alt' => lephare_gal_alt( $d['couverture'], $d['titre'] ), 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => $sizes, 'class' => 'lpg-carte__couv' ) ) : '<span class="lpg-vide-image" aria-hidden="true"></span>';
	$apercu  = '';
	$suivant = array_values( array_diff( $d['photos'], array( $d['couverture'] ) ) );
	if ( ! empty( $o['apercu'] ) && $d['couverture'] && $suivant ) {
		// Aperçu au survol / au focus : jusqu'à 3 autres photos de l'album (décoratives : alt vide).
		foreach ( array_slice( $suivant, 0, 3 ) as $id ) {
			$apercu .= wp_get_attachment_image( $id, 'medium_large', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => $sizes, 'class' => 'lpg-carte__apercu', 'aria-hidden' => 'true' ) );
		}
		$points  = str_repeat( '<i></i>', min( 3, count( $suivant ) ) + 1 );
		$apercu .= '<span class="lpg-carte__points" aria-hidden="true">' . $points . '</span>';
	}
	$out  = '<li class="lpg-carte' . ( $d['nb'] > 1 ? ' lpg-carte--pile' : '' ) . '"><article class="lpg-carte__inner"' . ( $apercu ? ' data-lpg-apercu' : '' ) . '><div class="lpg-carte__media">' . $img . $apercu;
	$out .= '<span class="lpg-carte__nb">' . lephare_gal_picto( 'photos' ) . esc_html( lephare_gal_nb( $d['nb'], $o['mot_photo'], $o['mot_photos'] ) ) . '</span></div>';
	$out .= '<div class="lpg-carte__corps">';
	if ( $d['categorie'] ) {
		$out .= '<p class="lpg-carte__cat">' . esc_html( $d['categorie'] ) . '</p>';
	}
	$out .= '<' . $niveau . ' class="lpg-carte__titre"><a class="lpg-lien-etendu" href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['titre'] ) . '</a></' . $niveau . '>';
	$out .= '<p class="lpg-carte__date">' . lephare_gal_picto( 'calendrier' ) . '<time datetime="' . esc_attr( $d['date_iso'] ) . '">' . esc_html( $d['date_txt'] ) . '</time></p>';
	return $out . '</div></article></li>';
}

/**
 * Bandeau (même style que les pages Actualités et Événements).
 * Avec plusieurs photos : elles se succèdent en fondu lent (coupé si « réduire les animations »).
 *
 * @param array     $o      Options : surtitre, titre, intro.
 * @param int|int[] $photo  Photo(s) de fond (0 = aucune).
 * @param string    $avant  HTML placé avant le surtitre (lien de retour).
 * @param string    $apres  HTML placé après l'introduction (repères).
 * @param string    $classe Classe en plus.
 * @return string
 */
function lephare_gal_bandeau( $o, $photo, $avant = '', $apres = '', $classe = '' ) {
	$photos = array_values( array_filter( array_map( 'intval', (array) $photo ) ) );
	$out    = '<header class="lpg-hero' . ( $photos ? ' lpg-hero--photo' : '' ) . ( $classe ? ' ' . $classe : '' ) . '">';
	if ( $photos ) {
		$diapo = count( $photos ) > 1;
		$out  .= '<div class="lpg-hero__photo' . ( $diapo ? ' lpg-hero__photo--diapo' : '' ) . '" aria-hidden="true"' . ( $diapo ? ' style="--lpg-nb:' . count( $photos ) . '"' : '' ) . '>';
		foreach ( $photos as $i => $id ) {
			$attr = array( 'alt' => '', 'sizes' => '100vw', 'loading' => 'eager', 'fetchpriority' => 0 === $i ? 'high' : 'low', 'decoding' => 'async' );
			if ( $diapo ) {
				$attr['style'] = '--lpg-i:' . $i;
			}
			$out .= wp_get_attachment_image( $id, 'full', false, $attr );
		}
		$out .= '</div>';
	}
	$out .= '<div class="lpg-cadre lpg-hero__inner">' . $avant;
	if ( '' !== ( $o['surtitre'] ?? '' ) ) {
		$out .= $o['surtitre_html'] ?? '<p class="lpg-surtitre">' . esc_html( $o['surtitre'] ) . '</p>';
	}
	$out .= '<h1 class="lpg-hero__titre">' . esc_html( $o['titre'] ) . '</h1>';
	if ( '' !== ( $o['intro'] ?? '' ) ) {
		$out .= '<p class="lpg-hero__intro">' . esc_html( $o['intro'] ) . '</p>';
	}
	return $out . $apres . '</div></header>';
}

/**
 * Tuiles des catégories sous le bandeau : éventail de 3 vraies photos, nom, nombre d'albums et de photos.
 * Un clic ouvre la catégorie (même adresse que les onglets).
 *
 * @param WP_Term[] $cats   Catégories qui ont des albums.
 * @param array     $par    [ slug => [ albums, photos, ids de photos ] ].
 * @param array     $o      Options.
 * @param string    $page   Adresse de la page.
 * @param string    $actif  Catégorie filtrée.
 * @return string
 */
function lephare_gal_tuiles( $cats, $par, $o, $page, $actif ) {
	$out = '<nav class="lpg-tuiles lpg-cadre" aria-label="' . esc_attr( $o['tuiles_titre'] ) . '"><ul role="list">';
	foreach ( $cats as $slug => $t ) {
		$info = $par[ $slug ] ?? array( 0, 0, array() );
		$out .= '<li class="lpg-tuile' . ( $slug === $actif ? ' lpg-tuile--active' : '' ) . '"><span class="lpg-tuile__eventail" aria-hidden="true">';
		foreach ( array_slice( $info[2], 0, 3 ) as $id ) {
			$out .= wp_get_attachment_image( $id, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) );
		}
		$out .= '</span><span class="lpg-tuile__texte"><a class="lpg-tuile__lien scroll-ignore" href="' . esc_url( add_query_arg( 'lpg_cat', $slug, $page ) . '#lpg-albums' ) . '"' . ( $slug === $actif ? ' aria-current="page"' : '' ) . '>' . esc_html( $t->name ) . '</a>';
		$out .= '<span class="lpg-tuile__nb">' . esc_html( lephare_gal_nb( $info[0], $o['mot_album'], $o['mot_albums'] ) . ' · ' . lephare_gal_nb( $info[1], $o['mot_photo'], $o['mot_photos'] ) ) . '</span></span>';
		$out .= '<span class="lpg-tuile__fleche" aria-hidden="true">' . lephare_gal_picto( 'fleche' ) . '</span></li>';
	}
	return $out . '</ul></nav>';
}

/**
 * Dernier album mis en avant : mosaïque de 5 photos (chacune ouvre la visionneuse), texte, « Voir l'album »
 * et « Lancer le diaporama ».
 *
 * @param array $d Données de l'album.
 * @param array $o Options de la page.
 * @param array $r Réglages de la visionneuse.
 * @return string
 */
function lephare_gal_une( $d, $o, $r ) {
	$out  = '<section class="lpg-une lpg-cadre" aria-labelledby="lpg-une-titre">';
	$out .= '<div class="lpg-une__grille"><div class="lpg-une__mosaique lpg-une__mosaique--' . min( 5, $d['nb'] ) . '">';
	foreach ( array_slice( $d['photos'], 0, 5 ) as $i => $id ) {
		$item = lephare_gal_item( $id, $d['titre'], false );
		$out .= '<a class="lpg-une__tuile" href="' . esc_url( $item['src'] ) . '" data-lpg-index="' . (int) $i . '" aria-label="' . esc_attr( sprintf( $r['vis_agrandir'], $i + 1, $d['nb'] ) . ' : ' . $item['alt'] ) . '">';
		$out .= wp_get_attachment_image( $id, 0 === $i ? 'large' : 'medium_large', false, array( 'alt' => $item['alt'], 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => 0 === $i ? '(max-width: 1100px) 100vw, 600px' : '(max-width: 1100px) 50vw, 300px' ) );
		if ( 4 === $i && $d['nb'] > 5 ) {
			$out .= '<span class="lpg-une__plus" aria-hidden="true">+' . (int) ( $d['nb'] - 5 ) . '</span>';
		}
		$out .= '</a>';
	}
	$out .= '</div><div class="lpg-une__texte">';
	if ( '' !== $o['une_surtitre'] ) {
		$out .= '<p class="lpg-une__surtitre">' . esc_html( $o['une_surtitre'] ) . '</p>';
	}
	if ( $d['categorie'] ) {
		$out .= '<p class="lpg-carte__cat">' . esc_html( $d['categorie'] ) . '</p>';
	}
	$out .= '<h2 class="lpg-une__titre" id="lpg-une-titre"><a href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['titre'] ) . '</a></h2>';
	$out .= '<ul class="lpg-une__infos" role="list"><li>' . lephare_gal_picto( 'calendrier' ) . '<time datetime="' . esc_attr( $d['date_iso'] ) . '">' . esc_html( $d['date_txt'] ) . '</time></li><li>' . lephare_gal_picto( 'photos' ) . esc_html( lephare_gal_nb( $d['nb'], $o['mot_photo'], $o['mot_photos'] ) ) . '</li></ul>';
	if ( '' !== $d['extrait'] ) {
		$out .= '<p class="lpg-une__extrait">' . esc_html( $d['extrait'] ) . '</p>';
	}
	$out .= '<div class="lpg-une__actions"><a class="lpg-bouton" href="' . esc_url( $d['url'] ) . '">' . esc_html( $o['une_bouton'] ) . '<span class="screen-reader-text"> : ' . esc_html( $d['titre'] ) . '</span>' . lephare_gal_picto( 'fleche' ) . '</a>';
	$out .= '<button type="button" class="lpg-bouton-diapo" data-lpg-diaporama>' . lephare_gal_picto( 'lecture' ) . esc_html( $o['une_diapo'] ) . '</button></div>';
	return $out . '</div></div></section>';
}

/**
 * Rendu de la page Galerie : bandeau (photos qui se succèdent), tuiles des catégories, dernier album en mosaïque,
 * onglets, grille d'albums (aperçu au survol), pagination, visionneuse du dernier album.
 *
 * @param array $brut Options brutes.
 * @return string
 */
function lephare_gal_rendu( $brut = array() ) {
	wp_enqueue_style( 'lephare-galerie' );
	wp_enqueue_script( 'lephare-galerie' );

	$o        = lephare_gal_nettoyer( $brut, lephare_gal_schema_page() );
	$r        = lephare_gal_reglages_album();
	$par_page = max( 1, min( 48, (int) $o['par_page'] ) );
	$slugs    = array_values( array_filter( array_map( 'sanitize_title', explode( ',', (string) $o['categories'] ) ) ) );
	$cats     = lephare_gal_categories( false, $slugs );      // Catégories qui ont au moins un album.
	$toutes   = lephare_gal_categories( true, $slugs );       // Toutes (pour accepter un filtre vide).
	$page     = get_permalink();

	// Filtres lus dans l'adresse (lecture seule).
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtres publics en lecture seule.
	$f_cat  = isset( $_GET['lpg_cat'] ) ? sanitize_title( wp_unslash( $_GET['lpg_cat'] ) ) : '';
	$f_page = isset( $_GET['lpg_page'] ) ? max( 1, absint( $_GET['lpg_page'] ) ) : 1;
	// phpcs:enable
	if ( '' !== $f_cat && ! isset( $toutes[ $f_cat ] ) ) {
		$f_cat = '';
	}
	$ids_page = $slugs ? array_map( static fn( $t ) => (int) $t->term_id, array_values( $toutes ) ) : lephare_gal_ids_categories();

	// Tous les albums de la galerie (sans filtre) : repères, photos du bandeau, tuiles, dernier album.
	$tous      = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'category__in' => $ids_page ? $ids_page : array( 0 ), 'posts_per_page' => -1, 'no_found_rows' => true ) );
	$tous      = array_map( 'lephare_gal_donnees', $tous );
	$nb_photos = array_sum( wp_list_pluck( $tous, 'nb' ) );
	$par_cat   = array();
	foreach ( $tous as $d ) {
		if ( ! isset( $par_cat[ $d['cat_slug'] ] ) ) {
			$par_cat[ $d['cat_slug'] ] = array( 0, 0, array() );
		}
		$par_cat[ $d['cat_slug'] ][0]++;
		$par_cat[ $d['cat_slug'] ][1] += $d['nb'];
		$par_cat[ $d['cat_slug'] ][2]  = array_merge( $par_cat[ $d['cat_slug'] ][2], array_slice( $d['photos'], 0, 3 ) );
	}

	// Dernier album mis en avant (page 1, sans filtre). Il reste AUSSI dans la grille « Tous les albums » :
	// le nombre affiché, le compteur de l'onglet « Tout » et le nombre de cartes correspondent toujours.
	$une = ( $o['une'] && '' === $f_cat && 1 === $f_page && $tous && $tous[0]['nb'] ) ? $tous[0] : null;

	$ids = '' !== $f_cat ? array( (int) $toutes[ $f_cat ]->term_id ) : $ids_page;
	$q   = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'category__in'        => $ids ? $ids : array( 0 ),
			'posts_per_page'      => $par_page,
			'paged'               => $f_page,
			'ignore_sticky_posts' => true,
		)
	);
	update_post_thumbnail_cache( $q );
	$albums = array_map( 'lephare_gal_donnees', $q->posts );

	// Photo(s) du bandeau : celle du widget ; sinon les couvertures des albums récents (fondu) ou la plus récente.
	$photo = array();
	if ( $o['hero_photo'] ) {
		if ( $o['image_fond'] && wp_attachment_is_image( $o['image_fond'] ) ) {
			$photo = array( (int) $o['image_fond'] );
		} else {
			$photo = array_values( array_unique( array_filter( wp_list_pluck( array_slice( $tous, 0, $o['hero_diaporama'] ? 5 : 1 ), 'couverture' ) ) ) );
		}
	}
	$reperes = '';
	if ( $o['hero_reperes'] && $tous ) {
		$reperes = '<ul class="lpg-reperes" role="list" aria-label="La galerie en bref"><li><strong>' . count( $tous ) . '</strong> ' . esc_html( count( $tous ) > 1 ? $o['mot_albums'] : $o['mot_album'] ) . '</li><li><strong>' . (int) $nb_photos . '</strong> ' . esc_html( $nb_photos > 1 ? $o['mot_photos'] : $o['mot_photo'] ) . '</li></ul>';
	}

	$html  = '<div class="lpg' . ( $o['animations'] ? '' : ' lpg--calme' ) . '">';
	$html .= lephare_gal_bandeau( $o, $photo, '', $reperes, ( $o['tuiles'] && count( $cats ) > 1 ) ? 'lpg-hero--tuiles' : '' );
	if ( $o['tuiles'] && count( $cats ) > 1 ) {
		$html .= lephare_gal_tuiles( $cats, $par_cat, $o, $page, $f_cat );
	}
	if ( $une ) {
		$html .= lephare_gal_une( $une, $o, $r );
	}

	$titre = '' !== $f_cat ? $toutes[ $f_cat ]->name : $o['titre_liste'];
	$n     = (int) $q->found_posts;
	$html .= '<section class="lpg-albums" id="lpg-albums" aria-labelledby="lpg-albums-titre"><div class="lpg-cadre">';
	$html .= '<div class="lpg-tete"><h2 class="lpg-tete__titre" id="lpg-albums-titre">' . esc_html( $titre ) . '</h2>';
	$html .= '<p class="lpg-resultats" role="status" aria-live="polite">' . esc_html( lephare_gal_nb( $n, $o['mot_album'], $o['mot_albums'] ) ) . '</p></div>';

	// Onglets de catégories : liens simples (clavier, adresse partageable), catégories vides masquées.
	if ( $o['filtres'] && count( $cats ) > 1 ) {
		$html .= '<nav class="lpg-filtres" aria-label="Catégories de la galerie"><ul role="list">';
		$items = array( '' => array( $o['txt_tout'], count( $tous ) ) );
		foreach ( $cats as $slug => $t ) {
			$items[ $slug ] = array( $t->name, $par_cat[ $slug ][0] ?? 0 );
		}
		foreach ( $items as $slug => $it ) {
			$actif = $slug === $f_cat;
			$url   = ( $slug ? add_query_arg( 'lpg_cat', $slug, $page ) : $page ) . '#lpg-albums';
			$html .= '<li><a class="lpg-filtre scroll-ignore' . ( $actif ? ' lpg-filtre--actif' : '' ) . '" href="' . esc_url( $url ) . '"' . ( $actif ? ' aria-current="page"' : '' ) . '>' . esc_html( $it[0] ) . '<span class="lpg-filtre__nb"><span class="screen-reader-text"> (</span>' . (int) $it[1] . '<span class="screen-reader-text"> ' . esc_html( $it[1] > 1 ? $o['mot_albums'] : $o['mot_album'] ) . ')</span></span></a></li>';
		}
		$html .= '</ul></nav>';
	}

	if ( ! $albums ) {
		{
			$message = $f_page > 1 ? $o['txt_page_vide'] : ( '' !== $f_cat ? $o['txt_cat_vide'] : $o['txt_vide'] );
			$html   .= '<div class="lpg-vide"><p>' . esc_html( $message ) . '</p>';
			if ( '' !== $f_cat || $f_page > 1 ) {
				$html .= '<p><a class="scroll-ignore" href="' . esc_url( $page . '#lpg-albums' ) . '">' . esc_html( $o['txt_tout'] ) . '</a></p>';
			}
			$html .= '</div>';
		}
	} elseif ( $albums ) {
		$html .= '<ul class="lpg-grille" role="list">';
		foreach ( $albums as $d ) {
			$html .= lephare_gal_carte( $d, $o );
		}
		$html .= '</ul>';
		$html .= lephare_gal_pagination( (int) $q->max_num_pages, $f_page, $f_cat, $page );
	}
	$html .= '</div></section>';
	if ( $une ) {
		$html .= lephare_gal_visionneuse_html( $une['titre'], $une['photos'], $r );
	}
	$html .= '</div>';
	wp_reset_postdata();
	return $html;
}

/**
 * Pagination sobre : Précédent · 1 2 3 · Suivant (aria-current, catégorie conservée).
 *
 * @param int    $n    Nombre de pages.
 * @param int    $c    Page courante.
 * @param string $cat  Catégorie filtrée.
 * @param string $page Adresse de la page.
 * @return string
 */
function lephare_gal_pagination( $n, $c, $cat, $page ) {
	if ( $n < 2 ) {
		return '';
	}
	$url = static function ( $i ) use ( $cat, $page ) {
		$args = array_filter( array( 'lpg_cat' => $cat, 'lpg_page' => $i > 1 ? $i : '' ) );
		return add_query_arg( $args, $page ) . '#lpg-albums';
	};
	$out = '<nav class="lpg-pagination" aria-label="Pagination des albums"><ul role="list">';
	if ( $c > 1 ) {
		$out .= '<li><a class="scroll-ignore" href="' . esc_url( $url( $c - 1 ) ) . '" rel="prev">Précédent</a></li>';
	}
	for ( $i = 1; $i <= $n; $i++ ) {
		$out .= $i === $c
			? '<li><span aria-current="page"><span class="screen-reader-text">Page </span>' . $i . '</span></li>'
			: '<li><a class="scroll-ignore" href="' . esc_url( $url( $i ) ) . '"><span class="screen-reader-text">Page </span>' . $i . '</a></li>';
	}
	if ( $c < $n ) {
		$out .= '<li><a class="scroll-ignore" href="' . esc_url( $url( $c + 1 ) ) . '" rel="next">Suivant</a></li>';
	}
	return $out . '</ul></nav>';
}

/* =========================================================================
 * 6. Page d'un album : remplace le contenu de Kadence (action kadence_single) seulement pour les albums
 * ====================================================================== */

add_action(
	'wp',
	static function () {
		if ( is_admin() || ! is_singular( 'post' ) || ! lephare_gal_est_album( get_queried_object_id() ) ) {
			return;
		}
		if ( has_action( 'kadence_single', 'Kadence\single_markup' ) ) {
			remove_action( 'kadence_single', 'Kadence\single_markup' );
			add_action( 'kadence_single', 'lephare_gal_album' );
			add_filter(
				'body_class',
				static function ( $classes ) {
					$classes[] = 'lpg-page-album';
					return $classes;
				}
			);
			add_filter(
				'kadence_post_layout',
				static function ( $layout ) {
					if ( is_array( $layout ) ) {
						$layout['transparent'] = 'disable';
					}
					return $layout;
				},
				20
			);
		}
	}
);

/**
 * Largeur d'une photo dans la grille (en colonnes) pour que la dernière rangée remplisse toute la ligne,
 * à 4 colonnes (ordinateur), 3 (tablette) et 2 (mobile). Renvoie des variables CSS : « --lpg-s4:2;… ».
 *
 * @param int $i     Rang de la photo (0…).
 * @param int $total Nombre de photos.
 * @return string
 */
function lephare_gal_etendue( $i, $total ) {
	$css = '';
	foreach ( array( 4, 3, 2 ) as $cols ) {
		$reste = $total % $cols;
		$debut = $total - $reste;            // Première photo de la dernière rangée incomplète.
		$span  = 1;
		if ( $reste && $i >= $debut ) {
			$k    = $i - $debut;
			$span = intdiv( $cols, $reste ) + ( $k < $cols % $reste ? 1 : 0 );
		}
		$css .= '--lpg-s' . $cols . ':' . $span . ';';
	}
	return $css;
}

/**
 * Contenu de l'album hors photos (paragraphes, titres…) : les blocs Galerie / Image sont affichés dans la grille.
 *
 * @param WP_Post $p Album.
 * @return string
 */
function lephare_gal_texte_album( $p ) {
	$html = '';
	foreach ( parse_blocks( (string) $p->post_content ) as $b ) {
		if ( in_array( $b['blockName'], array( 'core/gallery', 'core/image' ), true ) || ( null === $b['blockName'] && '' === trim( $b['innerHTML'] ) ) ) {
			continue;
		}
		$html .= render_block( $b );
	}
	$html = preg_replace( '~\[gallery[^\]]*\]~', '', $html );
	return trim( $html );
}

/**
 * Affichage d'un album (appelé par Kadence via l'action kadence_single).
 */
function lephare_gal_album() {
	while ( have_posts() ) {
		the_post();
		$post  = get_post();
		$d     = lephare_gal_donnees( $post );
		$r     = lephare_gal_reglages_album();
		$po    = lephare_gal_defauts( lephare_gal_schema_page() );
		$liste = get_page_by_path( 'galerie' );
		$liste = $liste ? get_permalink( $liste ) : home_url( '/' );
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- fonctions de l'extension, échappées à l'intérieur.

		echo '<div id="primary" class="content-area lpg-zone"><div id="main" class="site-main">';
		echo '<article id="post-' . (int) $post->ID . '" class="' . esc_attr( implode( ' ', get_post_class( 'lpg lpg-album' ) ) ) . '">';

		// Bandeau : retour, catégorie, titre, description (extrait), date, nombre de photos et « Lancer le diaporama ».
		$retour = '<a class="lpg-retour" href="' . esc_url( $liste ) . '">' . lephare_gal_picto( 'retour' ) . esc_html( $r['alb_retour'] ) . '</a>';
		$cat    = $d['cat_slug'] ? '<p class="lpg-surtitre"><a href="' . esc_url( add_query_arg( 'lpg_cat', $d['cat_slug'], $liste ) . '#lpg-albums' ) . '"><span class="screen-reader-text">Catégorie : </span>' . esc_html( $d['categorie'] ) . '</a></p>' : '';
		$infos  = '<div class="lpg-hero__bas"><ul class="lpg-infos" role="list"><li>' . lephare_gal_picto( 'calendrier' ) . '<time datetime="' . esc_attr( $d['date_iso'] ) . '">' . esc_html( $d['date_txt'] ) . '</time></li><li>' . lephare_gal_picto( 'photos' ) . esc_html( lephare_gal_nb( $d['nb'], $po['mot_photo'], $po['mot_photos'] ) ) . '</li></ul>';
		if ( $d['nb'] > 1 && '' !== $r['alb_diapo'] ) {
			$infos .= '<button type="button" class="lpg-bouton-diapo lpg-bouton-diapo--clair" data-lpg-diaporama>' . lephare_gal_picto( 'lecture' ) . esc_html( $r['alb_diapo'] ) . '</button>';
		}
		$infos .= '</div>';
		echo lephare_gal_bandeau(
			array( 'surtitre' => $cat ? 'x' : '', 'surtitre_html' => $cat, 'titre' => $d['titre'], 'intro' => $d['extrait'] ),
			$d['couverture'],
			$retour,
			$infos,
			'lpg-hero--album'
		);

		// Texte éventuel de l'album (hors photos).
		$texte = lephare_gal_texte_album( $post );
		if ( '' !== $texte ) {
			echo '<div class="lpg-cadre"><div class="lpg-texte">' . $texte . '</div></div>';
		}

		// Grille de photos sans vide ; chaque vignette ouvre la visionneuse (sans JavaScript : l'image en grand).
		echo '<section class="lpg-photos" aria-labelledby="lpg-photos-titre"><div class="lpg-cadre"><h2 class="screen-reader-text" id="lpg-photos-titre">' . esc_html( $r['alb_titre_mur'] ) . '</h2>';
		if ( ! $d['photos'] ) {
			echo '<div class="lpg-vide"><p>Cet album ne contient pas encore de photo.</p></div>';
		} else {
			echo '<div class="lpg-mur-cadre"><ul class="lpg-mur" role="list" data-lpg-mur>';
			foreach ( $d['photos'] as $i => $id ) {
				$alt   = lephare_gal_alt( $id, $d['titre'] );
				$grand = wp_get_attachment_image_src( $id, 'full' );
				$etiq  = sprintf( $r['vis_agrandir'], $i + 1, $d['nb'] ) . ' : ' . $alt;
				echo '<li style="' . esc_attr( lephare_gal_etendue( $i, $d['nb'] ) ) . '"><a class="lpg-vignette" href="' . esc_url( $grand ? $grand[0] : '' ) . '" data-lpg-index="' . (int) $i . '" aria-label="' . esc_attr( $etiq ) . '">';
				echo wp_get_attachment_image( $id, 'medium_large', false, array( 'alt' => $alt, 'loading' => $i < 4 ? 'eager' : 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 50vw, (max-width: 1100px) 33vw, 300px' ) );
				echo '</a></li>';
			}
			echo '</ul></div>';
		}
		echo '</div></section>';
		echo '</article>';

		// Autres albums : même catégorie d'abord, puis les plus récents.
		if ( $r['alb_autres'] ) {
			$exclure = array( $post->ID );
			$autres  = array();
			$cat_obj = $d['cat_slug'] ? get_category_by_slug( $d['cat_slug'] ) : null;
			if ( $cat_obj ) {
				$autres = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'cat' => $cat_obj->term_id, 'post__not_in' => $exclure, 'posts_per_page' => $r['alb_autres_nb'], 'no_found_rows' => true ) );
			}
			if ( count( $autres ) < $r['alb_autres_nb'] ) {
				$exclure = array_merge( $exclure, wp_list_pluck( $autres, 'ID' ) );
				$autres  = array_merge( $autres, get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'category__in' => lephare_gal_ids_categories() ? lephare_gal_ids_categories() : array( 0 ), 'post__not_in' => $exclure, 'posts_per_page' => $r['alb_autres_nb'] - count( $autres ), 'no_found_rows' => true ) ) );
			}
			if ( $autres ) {
				echo '<section class="lpg lpg-autres" aria-labelledby="lpg-autres-titre"><div class="lpg-cadre"><h2 class="lpg-tete__titre" id="lpg-autres-titre">' . esc_html( $r['alb_autres_titre'] ) . '</h2><ul class="lpg-grille" role="list">';
				foreach ( $autres as $p ) {
					echo lephare_gal_carte( lephare_gal_donnees( $p ), array_merge( $po, array( 'apercu' => true ) ) );
				}
				echo '</ul></div></section>';
			}
		}

		// Visionneuse plein écran (vignettes, diaporama, plein écran).
		if ( $d['photos'] ) {
			echo lephare_gal_visionneuse_html( $d['titre'], $d['photos'], $r );
		}
		// phpcs:enable
		echo '</div></div>';
	}
}

/* =========================================================================
 * 7. Widget Elementor « Galerie » (catégorie LePhare)
 * ====================================================================== */

add_action(
	'elementor/elements/categories_registered',
	static function ( $elements_manager ) {
		$elements_manager->add_category( 'lephare', array( 'title' => 'LePhare', 'icon' => 'fa fa-plug' ) );
	}
);

add_action(
	'elementor/widgets/register',
	static function ( $widgets_manager ) {
		require_once __DIR__ . '/includes/class-lephare-galerie-widget.php';
		$widgets_manager->register( new LePhare_Galerie_Widget() );
	}
);
