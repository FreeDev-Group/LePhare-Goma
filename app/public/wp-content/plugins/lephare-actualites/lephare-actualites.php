<?php
/**
 * Plugin Name:       LePhare – Actualités
 * Description:       Rubrique Actualités, sobre et typographique : page /actualites/ (widget Elementor « Actualités » ou shortcode [lephare_actualites]) et gabarit de lecture des articles (hors catégorie Prédications).
 * Version:           3.7.1
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            FREEDEV – LePhare/Goma
 * License:           GPL-2.0-or-later
 * Text Domain:       lephare-actualites
 *
 * Issue #07, version 3 « sobre et premium » : fond blanc, typographie Jost, photos, beaucoup d'espace.
 * Les actualités sont des Articles WordPress (doc d'Aaron, #13). La catégorie « Prédications »
 * (slug sermons) et ses sous-catégories sont toujours exclues : ces articles gardent l'affichage #06.
 *
 * Filtres de la page (dans l'adresse, fonctionnent sans JavaScript) : ?lpa_cat=… &lpa_q=… &lpa_page=2
 */

defined( 'ABSPATH' ) || exit;

const LEPHARE_ACTU_VERSION = '3.7.1';

/* =========================================================================
 * 1. Outils
 * ====================================================================== */

/**
 * IDs des catégories qui ne sont jamais des actualités : « Prédications » (sermons) et ses sous-catégories,
 * plus celles ajoutées par d'autres extensions via le filtre « lephare_actu_categories_exclues »
 * (par exemple la catégorie « Galerie » de l'extension lephare-galerie, #08).
 *
 * @return int[]
 */
function lephare_actu_ids_sermons() {
	static $ids = null;
	if ( null === $ids ) {
		$ids = array();
		$cat = get_category_by_slug( 'sermons' );
		if ( $cat ) {
			$enfants = get_term_children( $cat->term_id, 'category' );
			$ids     = array_merge( array( (int) $cat->term_id ), is_wp_error( $enfants ) ? array() : array_map( 'intval', $enfants ) );
		}
		$ids = array_values( array_unique( array_map( 'intval', (array) apply_filters( 'lephare_actu_categories_exclues', $ids ) ) ) );
	}
	return $ids;
}

/**
 * Cet article est-il une actualité (article hors Prédications) ?
 *
 * @param int $post_id Article.
 * @return bool
 */
function lephare_actu_est_actualite( $post_id ) {
	if ( 'post' !== get_post_type( $post_id ) ) {
		return false;
	}
	$sermons = lephare_actu_ids_sermons();
	return ! $sermons || ! has_category( $sermons, $post_id );
}

/**
 * Catégories proposées comme actualités : [ slug => WP_Term ], hors Prédications.
 * Si $slugs est fourni, seulement ces catégories (dans cet ordre).
 *
 * @param string[] $slugs Slugs autorisés (vide = toutes).
 * @param bool     $vides Garder les catégories vides ?
 * @return WP_Term[]
 */
function lephare_actu_categories( $slugs = array(), $vides = false ) {
	$termes = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => ! $vides,
			'exclude'    => lephare_actu_ids_sermons(),
			'orderby'    => 'name',
		)
	);
	if ( is_wp_error( $termes ) ) {
		return array();
	}
	$par_slug = array();
	foreach ( $termes as $t ) {
		$par_slug[ $t->slug ] = $t;
	}
	if ( ! $slugs ) {
		return $par_slug;
	}
	$res = array();
	foreach ( $slugs as $s ) {
		if ( isset( $par_slug[ $s ] ) ) {
			$res[ $s ] = $par_slug[ $s ];
		}
	}
	return $res;
}

/**
 * Catégorie principale d'un article (la première hors Prédications, de préférence une de la page), ou null.
 *
 * @param int      $post_id Article.
 * @param string[] $slugs   Catégories de la page.
 * @return WP_Term|null
 */
function lephare_actu_categorie_principale( $post_id, $slugs = array() ) {
	$cats = array_filter(
		get_the_category( $post_id ),
		static function ( $c ) {
			return ! in_array( (int) $c->term_id, lephare_actu_ids_sermons(), true );
		}
	);
	foreach ( $cats as $c ) {
		if ( $slugs && in_array( $c->slug, $slugs, true ) ) {
			return $c;
		}
	}
	return $cats ? reset( $cats ) : null;
}

/**
 * Date en français : « 9 novembre 2026 ».
 *
 * @param int $timestamp Horodatage.
 * @return string
 */
function lephare_actu_date_fr( $timestamp ) {
	$mois = array( 1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' );
	$t    = (int) $timestamp;
	return wp_date( 'j', $t ) . ' ' . $mois[ (int) wp_date( 'n', $t ) ] . ' ' . wp_date( 'Y', $t );
}

/**
 * Temps de lecture en minutes (200 mots par minute, au moins 1).
 *
 * @param string $contenu Contenu brut.
 * @return int
 */
function lephare_actu_minutes( $contenu ) {
	$texte = trim( wp_strip_all_tags( strip_shortcodes( (string) $contenu ) ) );
	$mots  = '' === $texte ? 0 : count( preg_split( '/\s+/u', $texte ) );
	return max( 1, (int) ceil( $mots / 200 ) );
}

/**
 * Données d'un article prêtes à afficher (aucune requête si les caches sont amorcés).
 *
 * @param WP_Post  $p     Article.
 * @param string[] $slugs Catégories de la page.
 * @return array
 */
function lephare_actu_donnees( $p, $slugs = array() ) {
	$cat = lephare_actu_categorie_principale( $p->ID, $slugs );
	$ts  = get_post_timestamp( $p );
	return array(
		'id'        => $p->ID,
		'titre'     => get_the_title( $p ),
		'url'       => get_permalink( $p ),
		'date_iso'  => wp_date( 'c', $ts ),
		'date_txt'  => lephare_actu_date_fr( $ts ),
		'minutes'   => lephare_actu_minutes( $p->post_content ),
		'extrait'   => has_excerpt( $p ) ? $p->post_excerpt : wp_trim_words( wp_strip_all_tags( preg_replace( '~</p>|<br\s*/?>~i', ' ', strip_shortcodes( $p->post_content ) ) ), 32, '…' ),
		'categorie' => $cat ? $cat->name : '',
		'cat_slug'  => $cat ? $cat->slug : '',
		'image'     => (int) get_post_thumbnail_id( $p ),
	);
}

/**
 * Image d'article : taille adaptée, srcset et sizes de WordPress ; chargement différé sauf image principale.
 *
 * @param int    $image_id   Pièce jointe.
 * @param string $taille     Taille WordPress.
 * @param string $sizes      Attribut sizes.
 * @param bool   $principale Image principale (sans lazy, priorité haute).
 * @return string
 */
function lephare_actu_image( $image_id, $taille, $sizes, $principale = false ) {
	if ( ! $image_id ) {
		return '<span class="lpa-image-vide" aria-hidden="true"></span>';
	}
	$attr = array( 'sizes' => $sizes, 'decoding' => 'async', 'loading' => $principale ? 'eager' : 'lazy' );
	if ( $principale ) {
		$attr['fetchpriority'] = 'high';
	}
	return wp_get_attachment_image( $image_id, $taille, false, $attr );
}

/**
 * Ligne « Catégorie · date · temps de lecture ».
 *
 * @param array $d         Données.
 * @param bool  $categorie Afficher la catégorie ?
 * @return string
 */
function lephare_actu_meta( $d, $categorie = true ) {
	$out = '<p class="lpa-meta">';
	if ( $categorie && $d['categorie'] ) {
		$out .= '<span class="lpa-meta__cat">' . esc_html( $d['categorie'] ) . '</span>';
	}
	$out .= '<time datetime="' . esc_attr( $d['date_iso'] ) . '">' . esc_html( $d['date_txt'] ) . '</time>';
	$out .= '<span>' . (int) $d['minutes'] . ' min de lecture</span>';
	return $out . '</p>';
}

/**
 * Carte d'article (grille et « À lire aussi ») : image 3/2, catégorie, titre, date · lecture. Toute la carte est cliquable.
 *
 * @param array  $d       Données.
 * @param string $niveau  Balise du titre.
 * @param bool   $extrait Afficher l'extrait (page Actualités) ?
 * @return string
 */
function lephare_actu_carte( $d, $niveau = 'h3', $extrait = false, $avec_extrait = true, $suite = 'Lire la suite' ) {
	$out  = '<li class="lpa-carte"><article class="lpa-carte__inner">';
	$out .= '<div class="lpa-carte__media">' . lephare_actu_image( $d['image'], 'medium_large', '(max-width: 767px) 100vw, (max-width: 1024px) 50vw, 380px' );
	if ( $extrait ) {
		// Page Actualités : pastille ronde avec l'icône de la catégorie, posée sur le bas de la photo.
		$out .= '<span class="lpa-carte__icone" aria-hidden="true">' . lephare_actu_icone_cat( $d['cat_slug'] ?? '' ) . '</span>';
	}
	$out .= '</div>';
	if ( $d['categorie'] ) {
		$out .= '<p class="lpa-carte__cat">' . esc_html( $d['categorie'] ) . '</p>';
	}
	$out .= '<' . $niveau . ' class="lpa-carte__titre"><a class="lpa-lien-etendu" href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['titre'] ) . '</a></' . $niveau . '>';
	if ( ! $extrait ) {
		return $out . lephare_actu_meta( $d, false ) . '</article></li>';
	}
	if ( $avec_extrait && '' !== $d['extrait'] ) {
		$out .= '<p class="lpa-carte__extrait">' . esc_html( $d['extrait'] ) . '</p>';
	}
	// Pied de carte : date · lecture à gauche, « Lire la suite » à droite (indication visuelle : le lien est le titre).
	$out .= '<div class="lpa-carte__pied">' . lephare_actu_meta( $d, false ) . ( '' !== $suite ? '<span class="lpa-carte__suite" aria-hidden="true">' . esc_html( $suite ) . lephare_actu_picto( 'fleche' ) . '</span>' : '' ) . '</div>';
	return $out . '</article></li>';
}

/**
 * Icône d'une catégorie (choisie d'après son slug ; icône « journal » par défaut). Décorative.
 *
 * @param string $slug Slug de la catégorie.
 * @return string
 */
function lephare_actu_icone_cat( $slug ) {
	$traces = array(
		'annonce'    => '<path d="M4 10v4a1 1 0 0 0 1 1h2l5 4V5L7 9H5a1 1 0 0 0-1 1z"/><path d="M16 9a4 4 0 0 1 0 6M18.5 6.5a7.5 7.5 0 0 1 0 11"/>',
		'temoignage' => '<path d="M20 12a7.5 7.5 0 0 1-11 6.6L4 20l1.4-4.4A7.5 7.5 0 1 1 20 12z"/><path d="M9 11.5h.01M12 11.5h.01M15 11.5h.01"/>',
		'eglise'     => '<circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><circle cx="17" cy="9.5" r="2.5"/><path d="M15.5 14.2A4.5 4.5 0 0 1 21 18.5"/>',
		'journal'    => '<rect x="4" y="4.5" width="16" height="15" rx="2"/><path d="M8 9h8M8 12.5h8M8 16h5"/>',
	);
	$cle = 'journal';
	if ( false !== strpos( $slug, 'annonce' ) ) {
		$cle = 'annonce';
	} elseif ( false !== strpos( $slug, 'temoign' ) ) {
		$cle = 'temoignage';
	} elseif ( false !== strpos( $slug, 'eglise' ) || false !== strpos( $slug, 'vie' ) ) {
		$cle = 'eglise';
	}
	return '<svg class="lpa-picto" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $traces[ $cle ] . '</svg>';
}

/* =========================================================================
 * 2. Styles et scripts (chargés seulement là où ils servent)
 * ====================================================================== */

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_register_style( 'lephare-actualites', plugins_url( 'assets/lephare-actualites.css', __FILE__ ), array(), LEPHARE_ACTU_VERSION );
		wp_register_script( 'lephare-actualites', plugins_url( 'assets/lephare-actualites.js', __FILE__ ), array(), LEPHARE_ACTU_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		if ( ! is_singular() ) {
			return;
		}
		$id      = get_queried_object_id();
		$contenu = (string) get_post_field( 'post_content', $id ) . (string) get_post_meta( $id, '_elementor_data', true );
		if ( lephare_actu_est_actualite( $id ) || false !== strpos( $contenu, 'lephare_actualites' ) || false !== strpos( $contenu, 'lephare-actualites' ) ) {
			wp_enqueue_style( 'lephare-actualites' );
			wp_enqueue_script( 'lephare-actualites' );
		}
	}
);

/* =========================================================================
 * 3. Page Actualités : shortcode [lephare_actualites] et widget Elementor
 * ====================================================================== */

/**
 * Réglages de la page Actualités, partie par partie (widget Elementor « Actualités » ou attributs du shortcode).
 * Chaque champ : [ type, libellé, valeur par défaut, vide autorisé (= élément masqué), condition Elementor ].
 * Types : texte, zone, oui (interrupteur), nombre, media, categories.
 *
 * @return array
 */
function lephare_actu_schema_page() {
	return array(
		'bandeau'   => array(
			'label'  => 'Bandeau (haut de page)',
			'champs' => array(
				'surtitre'     => array( 'texte', 'Petite étiquette au-dessus du titre (vide = masquée)', 'Église LePhare/Goma', true ),
				'titre'        => array( 'texte', 'Titre de la page (H1)', 'Actualités' ),
				'intro'        => array( 'zone', 'Phrase d’introduction (vide = masquée)', 'Les nouvelles de l’église LePhare/Goma : la vie de la communauté, les annonces et les témoignages.', true ),
				'hero_bouton'  => array( 'texte', 'Texte du bouton vers la liste (vide = bouton masqué)', 'Parcourir les articles', true ),
				'hero_reperes' => array( 'oui', 'Afficher les repères (nombre d’articles, dernière publication)', true ),
				'txt_publies'  => array( 'texte', 'Repère : « articles publiés » (pluriel)', 'articles publiés', false, array( 'hero_reperes' => 'yes' ) ),
				'txt_publie'   => array( 'texte', 'Repère : « article publié » (singulier)', 'article publié', false, array( 'hero_reperes' => 'yes' ) ),
				'txt_derniere' => array( 'texte', 'Repère : « Dernière publication »', 'Dernière publication', false, array( 'hero_reperes' => 'yes' ) ),
				'hero_photo'   => array( 'oui', 'Photo en arrière-plan du bandeau', true ),
				'image_fond'   => array( 'media', 'Photo de fond (vide = photo d’un article récent)', 0, false, array( 'hero_photo' => 'yes' ) ),
				'hero_collage' => array( 'oui', 'Collage de photos d’articles (grand écran)', true ),
				'hero_formes'  => array( 'oui', 'Formes décoratives (chevrons, triangles, arc)', true ),
				'hero_vague'   => array( 'oui', 'Vague blanche en bas du bandeau', true ),
			),
		),
		'rubriques' => array(
			'label'  => 'Cartes des catégories',
			'champs' => array(
				'rubriques'    => array( 'oui', 'Afficher une carte par catégorie sous le bandeau', true ),
				'mot_article'  => array( 'texte', 'Mot « article » (singulier)', 'article', false, array( 'rubriques' => 'yes' ) ),
				'mot_articles' => array( 'texte', 'Mot « articles » (pluriel)', 'articles', false, array( 'rubriques' => 'yes' ) ),
			),
		),
		'une'       => array(
			'label'  => 'À la une',
			'champs' => array(
				'une'            => array( 'oui', 'Afficher l’article le plus récent « À la une »', true ),
				'une_surtitre'   => array( 'texte', 'Petite étiquette (vide = masquée)', 'Dernière publication', true, array( 'une' => 'yes' ) ),
				'une_titre'      => array( 'texte', 'Titre de la section', 'À la une', false, array( 'une' => 'yes' ) ),
				'une_bouton'     => array( 'texte', 'Texte du bouton vert', 'Lire l’article', false, array( 'une' => 'yes' ) ),
				'une_lien_liste' => array( 'texte', 'Lien vers la liste (vide = masqué)', 'Toutes les actualités', true, array( 'une' => 'yes' ) ),
				'une_calendrier' => array( 'oui', 'Pavé « calendrier » sur la photo', true, false, array( 'une' => 'yes' ) ),
			),
		),
		'liste'     => array(
			'label'  => 'Liste des articles',
			'champs' => array(
				'categories'     => array( 'categories', 'Catégories affichées (vide = toutes ; Prédications toujours exclue)', '' , true ),
				'par_page'       => array( 'nombre', 'Articles par page (un multiple de 3 remplit les rangées)', 9 ),
				'liste_surtitre' => array( 'texte', 'Petite étiquette (vide = masquée)', 'Toutes les actualités', true ),
				'titre_fil'      => array( 'texte', 'Titre de la liste', 'Derniers articles' ),
				'liste_consigne' => array( 'texte', 'Phrase d’aide à droite (vide = masquée)', 'Filtrez par catégorie ou recherchez un mot-clé.', true ),
				'barre'          => array( 'oui', 'Zone de filtres (catégories)', true ),
				'txt_tout'       => array( 'texte', 'Onglet « Tout »', 'Tout', false, array( 'barre' => 'yes' ) ),
				'recherche'      => array( 'oui', 'Champ de recherche', true, false, array( 'barre' => 'yes' ) ),
				'txt_recherche'  => array( 'texte', 'Texte dans le champ de recherche', 'Rechercher', false, array( 'barre' => 'yes', 'recherche' => 'yes' ) ),
				'carte_extrait'  => array( 'oui', 'Extrait dans les cartes', true ),
				'carte_suite'    => array( 'texte', 'Lien en bas des cartes (vide = masqué)', 'Lire la suite', true ),
			),
		),
		'messages'  => array(
			'label'  => 'Messages',
			'champs' => array(
				'txt_vide'      => array( 'texte', 'Aucun article publié', 'Aucune actualité n’est encore publiée.' ),
				'txt_aucun'     => array( 'texte', 'Aucun résultat de recherche', 'Aucun article ne correspond à votre recherche.' ),
				'txt_page_vide' => array( 'texte', 'Page de liste vide', 'Cette page ne contient aucun article.' ),
			),
		),
		'effets'    => array(
			'label'  => 'Animations',
			'champs' => array(
				'animations' => array( 'oui', 'Animations douces (apparition, compteur, photos qui flottent). Toujours coupées si l’appareil demande moins de mouvement.', true ),
			),
		),
	);
}

/**
 * Options par défaut, tirées du schéma.
 *
 * @return array
 */
function lephare_actu_options_defaut() {
	$o = array();
	foreach ( lephare_actu_schema_page() as $section ) {
		foreach ( $section['champs'] as $cle => $c ) {
			$o[ $cle ] = $c[2];
		}
	}
	return $o;
}

/**
 * Nettoie les réglages reçus d'un schéma (texte, zone, oui, nombre, media, categories, couleur).
 *
 * @param array $brut   Réglages bruts.
 * @param array $schema Schéma (sections => champs).
 * @return array
 */
function lephare_actu_nettoyer( $brut, $schema ) {
	$o = array();
	foreach ( $schema as $section ) {
		foreach ( $section['champs'] as $cle => $c ) {
			list( $type, , $defaut ) = $c;
			$vide = ! empty( $c[3] );
			$v    = $brut[ $cle ] ?? $defaut;
			switch ( $type ) {
				case 'oui':
					$o[ $cle ] = is_bool( $v ) ? $v : in_array( strtolower( (string) $v ), array( '1', 'yes', 'oui', 'true', 'on' ), true );
					break;
				case 'nombre':
				case 'media':
					$o[ $cle ] = is_array( $v ) ? (int) ( $v['id'] ?? 0 ) : (int) $v;
					break;
				case 'couleur':
					$o[ $cle ] = sanitize_hex_color( (string) $v ) ? sanitize_hex_color( (string) $v ) : $defaut;
					break;
				default:
					$v         = 'zone' === $type ? sanitize_textarea_field( (string) $v ) : sanitize_text_field( is_array( $v ) ? implode( ',', $v ) : (string) $v );
					$o[ $cle ] = ( '' === $v && ! $vide ) ? $defaut : $v;
			}
		}
	}
	return $o;
}

/**
 * Nettoie les options de la page Actualités.
 *
 * @param array $brut Options brutes.
 * @return array
 */
function lephare_actu_options( $brut ) {
	$o             = lephare_actu_nettoyer( $brut, lephare_actu_schema_page() );
	$o['par_page'] = max( 1, min( 24, $o['par_page'] ) );
	$o['slugs']    = array_values( array_filter( array_map( 'sanitize_title', explode( ',', $o['categories'] ) ) ) );
	return $o;
}

/**
 * Réglage courant (page Actualités ou page d'un article) : lephare_actu_opt( $tableau ) le définit, lephare_actu_opt( 'cle' ) le lit.
 *
 * @param string|array $cle Clé, ou tableau complet à mémoriser.
 * @return mixed
 */
function lephare_actu_opt( $cle ) {
	static $o = array();
	if ( is_array( $cle ) ) {
		$o = $cle;
		return null;
	}
	if ( array_key_exists( $cle, $o ) ) {
		return $o[ $cle ];
	}
	$defauts = lephare_actu_options_defaut() + lephare_actu_reglages_article_defaut();
	return $defauts[ $cle ] ?? '';
}

/**
 * Filtres lus dans l'adresse (GET, lecture seule : fonctionnent sans JavaScript).
 *
 * @param array $cats Catégories de la page [ slug => terme ].
 * @return array
 */
function lephare_actu_filtres( $cats ) {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtres publics en lecture seule.
	$cat = isset( $_GET['lpa_cat'] ) ? sanitize_title( wp_unslash( $_GET['lpa_cat'] ) ) : '';
	return array(
		'cat'  => isset( $cats[ $cat ] ) ? $cat : '',
		'q'    => isset( $_GET['lpa_q'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_GET['lpa_q'] ) ), 0, 100 ) : '',
		'page' => isset( $_GET['lpa_page'] ) ? max( 1, absint( $_GET['lpa_page'] ) ) : 1,
	);
	// phpcs:enable
}

/**
 * Arguments de requête de base : articles publiés, hors Prédications, dans les catégories de la page.
 *
 * @param array $o    Options.
 * @param array $cats Catégories de la page.
 * @param array $f    Filtres.
 * @return array
 */
function lephare_actu_args( $o, $cats, $f ) {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'category__not_in'    => lephare_actu_ids_sermons(),
	);
	if ( '' !== $f['cat'] ) {
		$args['category__in'] = array( (int) $cats[ $f['cat'] ]->term_id );
	} elseif ( $o['slugs'] ) {
		$args['category__in'] = $cats ? array_map( static fn( $t ) => (int) $t->term_id, array_values( $cats ) ) : array( 0 );
	}
	if ( '' !== $f['q'] ) {
		$args['s'] = $f['q'];
	}
	return $args;
}

/**
 * Nombre total d'articles de la rubrique (une requête, mémorisée pendant l'affichage).
 *
 * @param array $o      Options.
 * @param array $toutes Catégories de la page.
 * @return int
 */
function lephare_actu_total( $o, $toutes ) {
	static $memo = array();
	$cle = md5( wp_json_encode( $o['slugs'] ) );
	if ( ! isset( $memo[ $cle ] ) ) {
		$q            = new WP_Query( array_merge( lephare_actu_args( $o, $toutes, array( 'cat' => '', 'q' => '', 'page' => 1 ) ), array( 'posts_per_page' => 1, 'fields' => 'ids' ) ) );
		$memo[ $cle ] = (int) $q->found_posts;
	}
	return $memo[ $cle ];
}

add_shortcode( 'lephare_actualites', 'lephare_actu_shortcode' );

/**
 * Shortcode : [lephare_actualites categories="slug-a,slug-b" par_page="9" une="oui"].
 *
 * @param array|string $atts Attributs.
 * @return string
 */
function lephare_actu_shortcode( $atts ) {
	return lephare_actu_rendu( (array) $atts );
}

/**
 * Rendu de la page Actualités.
 *
 * a. En-tête : « Actualités » (H1) + introduction.
 * b. À la une : l'article le plus récent (sans filtre, page 1).
 * c. Barre de filtres collante (catégories + recherche).
 * d. Grille des autres articles (jamais l'article à la une : aucun doublon), pagination.
 *
 * @param array $brut Options brutes.
 * @return string
 */
function lephare_actu_rendu( $brut = array() ) {
	wp_enqueue_style( 'lephare-actualites' );
	wp_enqueue_script( 'lephare-actualites' );

	$o = lephare_actu_options( $brut );
	lephare_actu_opt( $o );
	$cats   = lephare_actu_categories( $o['slugs'] );        // Catégories non vides.
	$toutes = lephare_actu_categories( $o['slugs'], true );  // Catégories de la page, même vides.
	$f      = lephare_actu_filtres( $toutes );
	$filtre = ( '' !== $f['cat'] || '' !== $f['q'] );
	$page   = get_permalink();
	$total  = lephare_actu_total( $o, $toutes );

	// Article le plus récent de la rubrique (sans filtre) : sert à « À la une » et à la date de dernière publication.
	$recent = get_posts( array_merge( lephare_actu_args( $o, $toutes, array( 'cat' => '', 'q' => '', 'page' => 1 ) ), array( 'posts_per_page' => 1, 'no_found_rows' => true ) ) );
	$recent = $recent ? $recent[0] : null;

	$html = '<div class="lpa' . ( $o['animations'] ? '' : ' lpa--calme' ) . '">';

	// a. En-tête : fil d'Ariane, titre, introduction et repères réels (nombre d'articles, de catégories, dernière publication).
	// Photo de fond du bandeau : celle choisie dans le widget, sinon l'image du 2e article le plus récent
	// (le 1er est déjà « À la une »), sinon celle du plus récent. Toujours une vraie photo de la Médiathèque.
	// Collage du bandeau : les photos des 3e, 4e et 5e articles récents (jamais celle « À la une » ni celle du fond).
	$avec_image = get_posts( array_merge( lephare_actu_args( $o, $toutes, array( 'cat' => '', 'q' => '', 'page' => 1 ) ), array( 'posts_per_page' => 5, 'no_found_rows' => true, 'fields' => 'ids', 'meta_key' => '_thumbnail_id' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- 5 articles au plus.
	$photos     = array_map( static fn( $id ) => (int) get_post_thumbnail_id( $id ), $avec_image );
	$photo      = (int) $o['image_fond'];
	if ( ! $photo || ! wp_attachment_is_image( $photo ) ) {
		$photo = $photos[1] ?? ( $photos[0] ?? 0 );
	}
	$collage = array_values( array_diff( array_slice( $photos, 2, 3 ), array( $photo ) ) );
	$photo   = $o['hero_photo'] ? $photo : 0;
	$collage = ( $o['hero_collage'] && count( $collage ) >= 2 ) ? $collage : array();
	$html   .= lephare_actu_entete( $o, $total, $o['rubriques'] ? $cats : array(), $recent, $page, $photo, $collage );

	// b. À la une : l'article le plus récent ; il est ensuite retiré de la grille sur toutes les pages sans filtre.
	$exclure = array();
	if ( $o['une'] && ! $filtre && $recent ) {
		$exclure = array( $recent->ID );
		if ( 1 === $f['page'] ) {
			$html .= lephare_actu_une( lephare_actu_donnees( $recent, $o['slugs'] ), $total > 1 );
		}
	}

	// c + d. Barre de filtres collante, grille, pagination.
	$q = new WP_Query( array_merge( lephare_actu_args( $o, $toutes, $f ), array( 'posts_per_page' => $o['par_page'], 'paged' => $f['page'], 'post__not_in' => $exclure ) ) );
	update_post_thumbnail_cache( $q );

	$titre = $o['titre_fil'];
	if ( '' !== $f['cat'] ) {
		$titre = $toutes[ $f['cat'] ]->name;
	}
	if ( '' !== $f['q'] ) {
		$titre = 'Résultats pour « ' . $f['q'] . ' »';
	}
	$n = (int) $q->found_posts;

	// Peu d'articles : sans filtre, si l'article à la une est le seul, on n'affiche ni barre ni grille vide.
	if ( ! $filtre && ! $q->posts && $exclure && 1 === $f['page'] ) {
		wp_reset_postdata();
		return $html . '</div>';
	}

	// Liste : titre, puis la zone de filtres (dans le flux de la page : elle ne flotte jamais sur les articles), puis la grille.
	$html .= '<section class="lpa-liste" id="lpa-journal" aria-labelledby="lpa-liste-titre"><div class="lpa-cadre lpa-liste__cadre">';
	$html .= '<div class="lpa-liste__entete"><div class="lpa-liste__titres">' . ( '' !== $o['liste_surtitre'] ? '<p class="lpa-surtitre">' . esc_html( $o['liste_surtitre'] ) . '</p>' : '' ) . '<h2 class="lpa-liste__titre" id="lpa-liste-titre">' . esc_html( $titre ) . '</h2></div>';
	$html .= '<div class="lpa-liste__aide">' . ( '' !== $o['liste_consigne'] ? '<p class="lpa-liste__consigne">' . esc_html( $o['liste_consigne'] ) . '</p>' : '' );
	// Nombre de résultats : seulement après un filtre ou une recherche (annoncé aux lecteurs d'écran).
	$html .= '<p class="lpa-resultats" role="status" aria-live="polite">' . esc_html( $filtre ? $n . ' ' . ( $n > 1 ? 'résultats' : 'résultat' ) : '' ) . '</p></div></div>';
	if ( $o['barre'] ) {
		$html .= lephare_actu_barre( $o, $cats, $f, $page, $total );
	}

	if ( ! $q->posts ) {
		if ( $filtre ) {
			$html .= lephare_actu_vide( $o['txt_aucun'], $page );
		} elseif ( $f['page'] > 1 ) {
			$html .= lephare_actu_vide( $o['txt_page_vide'], $page );
		} else {
			$html .= lephare_actu_vide( $o['txt_vide'], '' );
		}
	} else {
		$compte = count( $q->posts );
		// 1 ou 2 cartes : grille adaptée pour éviter un grand vide.
		$classe = 1 === $compte ? ' lpa-grille--une-carte' : ( 2 === $compte ? ' lpa-grille--deux' : '' );
		$html  .= '<ul class="lpa-grille' . $classe . '" role="list">';
		foreach ( $q->posts as $p ) {
			$html .= lephare_actu_carte( lephare_actu_donnees( $p, $o['slugs'] ), 'h3', true, $o['carte_extrait'], $o['carte_suite'] );
		}
		$html .= '</ul>' . lephare_actu_pagination( $q, $f, $page );
	}
	$html .= '</div></section>';
	wp_reset_postdata();

	return $html . '</div>';
}

/**
 * Petite icône en ligne (décorative).
 *
 * @param string $nom calendrier | horloge | fleche | bas.
 * @return string
 */
function lephare_actu_picto( $nom ) {
	$traces = array(
		'calendrier' => '<rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
		'horloge'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
		'fleche'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'bas'        => '<path d="M12 5v14M6 13l6 6 6-6"/>',
	);
	return '<svg class="lpa-picto" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $traces[ $nom ] ?? '' ) . '</svg>';
}

/**
 * En-tête de la page (inspiré du modèle « Cosion ») : bandeau bleu marine posé sur une vraie photo de la
 * Médiathèque (assombrie, léger zoom lent), formes vert doux décoratives, surtitre, titre H1, introduction,
 * bouton vert doux vers la liste et repères réels (nombre d'articles, date de la dernière publication).
 * Sous le bandeau, une carte par catégorie (icône, nom, nombre d'articles, part des articles) qui chevauche
 * le bandeau et ouvre le filtre correspondant. Rien n'est inventé.
 *
 * @param array        $o      Options.
 * @param int          $total  Nombre d'articles de la rubrique.
 * @param WP_Term[]    $cats   Catégories non vides [ slug => terme ].
 * @param WP_Post|null $recent Article le plus récent.
 * @param string       $page   Adresse de la page.
 * @param int          $photo  Image de fond du bandeau (pièce jointe), 0 = aucune.
 * @return string
 */
function lephare_actu_entete( $o, $total, $cats, $recent, $page, $photo = 0, $collage = array() ) {
	$out = '<header class="lpa-hero' . ( $photo ? ' lpa-hero--photo' : '' ) . ( $collage ? ' lpa-hero--collage' : '' ) . '">';
	if ( $photo ) {
		// Photo décorative (texte alternatif vide) : le sens est porté par le titre et l'introduction.
		$out .= '<div class="lpa-hero__photo" aria-hidden="true">' . wp_get_attachment_image( $photo, 'full', false, array( 'alt' => '', 'sizes' => '100vw', 'loading' => 'eager', 'decoding' => 'async' ) ) . '</div>';
	}
	if ( $o['hero_formes'] ) {
		$out .= '<div class="lpa-hero__deco" aria-hidden="true"><span class="lpa-hero__chevron"></span><span class="lpa-hero__chevron lpa-hero__chevron--2"></span><span class="lpa-hero__triangles"><i></i><i></i><i></i></span><span class="lpa-hero__arc"></span></div>';
	}
	$out .= '<div class="lpa-cadre lpa-hero__inner"><div class="lpa-hero__texte">';
	if ( '' !== $o['surtitre'] ) {
		$out .= '<p class="lpa-surtitre lpa-surtitre--sombre">' . esc_html( $o['surtitre'] ) . '</p>';
	}
	$out .= '<h1 class="lpa-entete__titre">' . esc_html( $o['titre'] ) . '</h1>';
	if ( '' !== $o['intro'] ) {
		$out .= '<p class="lpa-entete__intro">' . esc_html( $o['intro'] ) . '</p>';
	}
	$out .= '<div class="lpa-hero__actions">';
	if ( $total > 1 && '' !== $o['hero_bouton'] ) {
		$out .= '<a class="lpa-bouton-doux scroll-ignore" href="#lpa-journal">' . esc_html( $o['hero_bouton'] ) . lephare_actu_picto( 'bas' ) . '</a>';
	}
	if ( $total > 0 && $o['hero_reperes'] ) {
		$out .= '<ul class="lpa-reperes" role="list" aria-label="La rubrique en bref">';
		$out .= '<li><strong data-lpa-compteur="' . (int) $total . '">' . (int) $total . '</strong> ' . esc_html( $total > 1 ? $o['txt_publies'] : $o['txt_publie'] ) . '</li>';
		if ( $recent ) {
			$ts   = get_post_timestamp( $recent );
			$out .= '<li>' . esc_html( $o['txt_derniere'] ) . ' : <time datetime="' . esc_attr( wp_date( 'c', $ts ) ) . '">' . esc_html( lephare_actu_date_fr( $ts ) ) . '</time></li>';
		}
		$out .= '</ul>';
	}
	$out .= '</div></div>';
	// Collage de vraies photos d'articles (décoratif), en cadres inclinés qui flottent doucement. Ordinateur seulement.
	if ( $collage ) {
		$out .= '<div class="lpa-hero__collage" aria-hidden="true"><span class="lpa-hero__anneau"></span>';
		foreach ( array_slice( $collage, 0, 3 ) as $i => $id ) {
			$out .= '<span class="lpa-hero__cadre lpa-hero__cadre--' . ( $i + 1 ) . '">' . wp_get_attachment_image( $id, 'medium_large', false, array( 'alt' => '', 'sizes' => '320px', 'loading' => 'eager', 'decoding' => 'async' ) ) . '</span>';
		}
		$out .= '</div>';
	}
	// Vague blanche en bas du bandeau : transition douce vers le contenu.
	$out .= '</div>';
	if ( $o['hero_vague'] ) {
		$out .= '<svg class="lpa-hero__vague" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 90V52C240 8 480 0 720 22s480 58 720 14v54H0z" fill="#fff"/></svg>';
	}
	$out .= '</header>';

	// Cartes des catégories (comme les cartes de services du modèle), chevauchant le bas du bandeau.
	if ( count( $cats ) > 1 ) {
		$out .= '<nav class="lpa-rubriques lpa-cadre" aria-label="Parcourir par catégorie"><ul role="list">';
		foreach ( $cats as $slug => $t ) {
			$n    = (int) $t->count;
			$out .= '<li class="lpa-rubrique-carte"><span class="lpa-rubrique-carte__icone" aria-hidden="true">' . lephare_actu_icone_cat( $slug ) . '</span>';
			$out .= '<span class="lpa-rubrique-carte__texte"><a class="lpa-rubrique-carte__lien scroll-ignore" href="' . esc_url( add_query_arg( 'lpa_cat', $slug, $page ) . '#lpa-journal' ) . '">' . esc_html( $t->name ) . '</a>';
			$out .= '<span class="lpa-rubrique-carte__nb">' . $n . ' ' . esc_html( $n > 1 ? $o['mot_articles'] : $o['mot_article'] ) . '</span>';
			// Part de la catégorie dans la rubrique (même information que le nombre, en visuel).
			$part = $total > 0 ? max( 4, min( 100, (int) round( 100 * $n / $total ) ) ) : 0;
			$out .= '<span class="lpa-rubrique-carte__part" aria-hidden="true"><span style="width:' . $part . '%"></span></span></span>';
			$out .= '<span class="lpa-rubrique-carte__fleche" aria-hidden="true">' . lephare_actu_picto( 'fleche' ) . '</span></li>';
		}
		$out .= '</ul></nav>';
	}
	return $out;
}

/**
 * À la une : bloc éditorial en deux colonnes (photo réelle à gauche, texte à droite ; empilé sur tablette et mobile).
 * Badge « À la une » sur la photo, catégorie, date et temps de lecture avec pictogrammes, titre, extrait,
 * bouton vert « Lire l'article » (le seul lien de la carte, qui couvre toute la carte) et accès direct à la liste.
 *
 * @param array $d          Données.
 * @param bool  $avec_liste D'autres articles suivent (affiche « Toutes les actualités ») ?
 * @return string
 */
function lephare_actu_une( $d, $avec_liste = true ) {
	$o    = array(
		'surtitre'   => lephare_actu_opt( 'une_surtitre' ),
		'titre'      => lephare_actu_opt( 'une_titre' ),
		'bouton'     => lephare_actu_opt( 'une_bouton' ),
		'liste'      => lephare_actu_opt( 'une_lien_liste' ),
		'calendrier' => lephare_actu_opt( 'une_calendrier' ),
	);
	$out  = '<section class="lpa-une lpa-cadre" aria-labelledby="lpa-une-titre">';
	$out .= '<div class="lpa-section-tete">' . ( '' !== $o['surtitre'] ? '<p class="lpa-surtitre">' . esc_html( $o['surtitre'] ) . '</p>' : '' ) . '<h2 class="lpa-section-tete__titre" id="lpa-une-titre">' . esc_html( $o['titre'] ) . '</h2></div>';
	// Pavé « calendrier » sur la photo (décoratif : la date est aussi écrite sous le titre).
	$mois  = array( 1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.' );
	$ts    = strtotime( $d['date_iso'] );
	$pave  = '<span class="lpa-une__pave" aria-hidden="true"><b>' . esc_html( wp_date( 'd', $ts ) ) . '</b><span>' . esc_html( $mois[ (int) wp_date( 'n', $ts ) ] ) . '</span><small>' . esc_html( wp_date( 'Y', $ts ) ) . '</small></span>';
	$out  .= '<article class="lpa-une__article"><div class="lpa-une__media">' . lephare_actu_image( $d['image'], 'full', '(max-width: 1024px) 100vw, 720px', true ) . ( $o['calendrier'] ? $pave : '' ) . '</div>';
	$out .= '<div class="lpa-une__texte">';
	if ( $d['categorie'] ) {
		$out .= '<p class="lpa-une__cat"><span class="screen-reader-text">Catégorie : </span>' . esc_html( $d['categorie'] ) . '</p>';
	}
	$out .= '<ul class="lpa-une__infos" role="list">';
	$out .= '<li>' . lephare_actu_picto( 'calendrier' ) . '<time datetime="' . esc_attr( $d['date_iso'] ) . '">' . esc_html( $d['date_txt'] ) . '</time></li>';
	$out .= '<li>' . lephare_actu_picto( 'horloge' ) . (int) $d['minutes'] . ' min de lecture</li></ul>';
	$out .= '<h3 class="lpa-une__titre">' . esc_html( $d['titre'] ) . '</h3>';
	$out .= '<p class="lpa-une__extrait">' . esc_html( $d['extrait'] ) . '</p>';
	$out .= '<div class="lpa-une__actions">';
	// Le bouton est le lien de l'article ; son nom accessible reprend le titre. Sa zone cliquable couvre toute la carte.
	$out .= '<a class="lpa-bouton lpa-une__lien" href="' . esc_url( $d['url'] ) . '">' . esc_html( $o['bouton'] ) . '<span class="screen-reader-text"> : ' . esc_html( $d['titre'] ) . '</span><span class="lpa-bouton__icone">' . lephare_actu_picto( 'fleche' ) . '</span></a>';
	if ( $avec_liste && '' !== $o['liste'] ) {
		$out .= '<a class="lpa-une__secondaire scroll-ignore" href="#lpa-journal">' . lephare_actu_picto( 'bas' ) . esc_html( $o['liste'] ) . '</a>';
	}
	return $out . '</div></div></article></section>';
}

/**
 * Barre de filtres : onglets de catégories (texte simple, catégories vides masquées) + recherche.
 * Zone distincte placée dans le flux, au-dessus de la grille : elle ne flotte jamais sur les articles.
 *
 * @param array  $o     Options.
 * @param array  $cats  Catégories non vides.
 * @param array  $f     Filtres.
 * @param string $page  Adresse de la page.
 * @param int    $total Total d'articles.
 * @return string
 */
function lephare_actu_barre( $o, $cats, $f, $page, $total ) {
	$items = array( '' => $o['txt_tout'] );
	foreach ( $cats as $slug => $t ) {
		$items[ $slug ] = $t->name;
	}
	if ( '' !== $f['cat'] && ! isset( $items[ $f['cat'] ] ) ) {
		$terme              = get_category_by_slug( $f['cat'] );
		$items[ $f['cat'] ] = $terme ? $terme->name : $f['cat'];
	}
	$garder = '' !== $f['q'] ? array( 'lpa_q' => rawurlencode( $f['q'] ) ) : array();
	$out    = '<div class="lpa-barre"><div class="lpa-barre__inner"><nav class="lpa-onglets" aria-label="Catégories d’actualités"><ul role="list">';
	foreach ( $items as $slug => $nom ) {
		$actif = $slug === $f['cat'];
		$url   = add_query_arg( array_merge( $garder, $slug ? array( 'lpa_cat' => $slug ) : array() ), $page ) . '#lpa-journal';
		// « scroll-ignore » : sinon Kadence intercepte les liens « #… » de la même page et le filtre ne s'applique pas.
		$out .= '<li><a class="lpa-onglet scroll-ignore' . ( $actif ? ' lpa-onglet--actif' : '' ) . '" href="' . esc_url( $url ) . '"' . ( $actif ? ' aria-current="page"' : '' ) . '>' . esc_html( $nom ) . '</a></li>';
	}
	$out .= '</ul></nav>';
	if ( $o['recherche'] ) {
		$out .= '<form class="lpa-recherche" method="get" action="' . esc_url( $page ) . '#lpa-journal" role="search" aria-label="Rechercher dans les actualités">';
		if ( '' !== $f['cat'] ) {
			$out .= '<input type="hidden" name="lpa_cat" value="' . esc_attr( $f['cat'] ) . '">';
		}
		$out .= '<label class="screen-reader-text" for="lpa_q">Rechercher une actualité</label>';
		$out .= '<input type="search" id="lpa_q" name="lpa_q" value="' . esc_attr( $f['q'] ) . '" placeholder="' . esc_attr( $o['txt_recherche'] ) . '" autocomplete="off">';
		$out .= '<button type="submit" class="lpa-recherche__bouton"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg><span class="screen-reader-text">Lancer la recherche</span></button></form>';
	}
	return $out . '</div></div>';
}

/**
 * Pagination sobre et centrée : Précédent · 1 2 3 · Suivant (aria-current, filtres conservés).
 *
 * @param WP_Query $q    Requête.
 * @param array    $f    Filtres.
 * @param string   $page Adresse.
 * @return string
 */
function lephare_actu_pagination( $q, $f, $page ) {
	$n = (int) $q->max_num_pages;
	if ( $n < 2 ) {
		return '';
	}
	$garder = array_map( 'rawurlencode', array_filter( array( 'lpa_cat' => $f['cat'], 'lpa_q' => $f['q'] ) ) );
	$url    = static function ( $i ) use ( $garder, $page ) {
		return add_query_arg( array_merge( $garder, $i > 1 ? array( 'lpa_page' => $i ) : array() ), $page ) . '#lpa-journal';
	};
	$c   = $f['page'];
	$out = '<nav class="lpa-pagination" aria-label="Pagination des actualités"><ul role="list">';
	if ( $c > 1 ) {
		$out .= '<li><a class="lpa-pagination__pas scroll-ignore" href="' . esc_url( $url( $c - 1 ) ) . '" rel="prev">Précédent</a></li>';
	}
	// Pages affichées : la première, la dernière, la page en cours et ses voisines ; « … » entre deux trous.
	$pages = array_unique( array_filter( array( 1, $c - 1, $c, $c + 1, $n ), static fn( $i ) => $i >= 1 && $i <= $n ) );
	sort( $pages );
	$avant = 0;
	foreach ( $pages as $i ) {
		if ( $i - $avant > 1 ) {
			$out .= '<li class="lpa-pagination__ellipse" aria-hidden="true">…</li>';
		}
		$avant = $i;
		$out  .= $i === $c
			? '<li><span class="lpa-pagination__num" aria-current="page"><span class="screen-reader-text">Page </span>' . $i . '</span></li>'
			: '<li><a class="lpa-pagination__num scroll-ignore" href="' . esc_url( $url( $i ) ) . '"><span class="screen-reader-text">Page </span>' . $i . '</a></li>';
	}
	if ( $c < $n ) {
		$out .= '<li><a class="lpa-pagination__pas scroll-ignore" href="' . esc_url( $url( $c + 1 ) ) . '" rel="next">Suivant</a></li>';
	}
	return $out . '</ul></nav>';
}

/**
 * État vide.
 *
 * @param string $message Message.
 * @param string $reset   Adresse pour tout afficher ('' = lien vers Prédications).
 * @return string
 */
function lephare_actu_vide( $message, $reset ) {
	$out = '<div class="lpa-vide"><p class="lpa-vide__texte">' . esc_html( $message ) . '</p>';
	$out .= $reset
		? '<p><a class="scroll-ignore" href="' . esc_url( $reset ) . '#lpa-journal">Voir toutes les actualités</a></p>'
		: '<p>En attendant, découvrez les <a href="' . esc_url( home_url( '/predications-ressources/' ) ) . '">prédications et ressources</a>.</p>';
	return $out . '</div>';
}

/* =========================================================================
 * 4. Page d'un article (hors Prédications) : gabarit de lecture sobre
 *    Remplace le contenu de Kadence (action kadence_single) seulement pour les actualités ;
 *    header, footer et styles du thème restent ceux du site. Aucun fichier du thème n'est modifié.
 * ====================================================================== */

add_action(
	'wp',
	static function () {
		if ( is_admin() || ! is_singular( 'post' ) || ! lephare_actu_est_actualite( get_queried_object_id() ) ) {
			return;
		}
		if ( has_action( 'kadence_single', 'Kadence\single_markup' ) ) {
			remove_action( 'kadence_single', 'Kadence\single_markup' );
			add_action( 'kadence_single', 'lephare_actu_article' );
			add_filter(
				'body_class',
				static function ( $classes ) {
					$classes[] = 'lpa-page-article';
					return $classes;
				}
			);
			// Header non transparent sur l'article (filtre prévu par Kadence ; réglage global inchangé).
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
 * Réglages de la page d'un article (écran WordPress « Articles → Page d'un article »).
 * Même format que le schéma de la page Actualités.
 *
 * @return array
 */
function lephare_actu_schema_article() {
	return array(
		'bandeau' => array(
			'label'  => 'Bandeau',
			'champs' => array(
				'art_retour'    => array( 'texte', 'Lien de retour vers la liste', 'Toutes les actualités' ),
				'art_categorie' => array( 'oui', 'Afficher la catégorie (pastille)', true ),
				'art_parties'   => array( 'oui', 'Afficher le nombre de parties (quand le sommaire est affiché)', true ),
				'art_mot_parties' => array( 'texte', 'Mot « parties »', 'parties' ),
				'art_formes'    => array( 'oui', 'Formes décoratives vert doux', true ),
			),
		),
		'outils'  => array(
			'label'  => 'Colonne d’outils de lecture',
			'champs' => array(
				'art_progression'    => array( 'oui', 'Barre de progression fine en haut de l’écran', true ),
				'art_anneau'         => array( 'oui', 'Anneau « % lu » (grand écran)', true ),
				'art_lu'             => array( 'texte', 'Mot « lu » à côté du pourcentage', 'lu' ),
				'art_sommaire'       => array( 'oui', 'Sommaire « Dans cet article »', true ),
				'art_sommaire_titre' => array( 'texte', 'Titre du sommaire', 'Dans cet article' ),
				'art_sommaire_min'   => array( 'nombre', 'Nombre minimum d’intertitres (H2) pour afficher le sommaire', 3 ),
				'art_partage'        => array( 'oui', 'Bloc de partage', true ),
				'art_partage_titre'  => array( 'texte', 'Titre du bloc de partage', 'Partager' ),
				'art_whatsapp'       => array( 'oui', 'Bouton WhatsApp', true ),
				'art_facebook'       => array( 'oui', 'Bouton Facebook', true ),
				'art_copier'         => array( 'oui', 'Bouton « Copier le lien »', true ),
				'art_copier_txt'     => array( 'texte', 'Texte du bouton « Copier le lien »', 'Copier le lien' ),
			),
		),
		'fin'     => array(
			'label'  => 'Fin de l’article',
			'champs' => array(
				'art_etiquettes'     => array( 'oui', 'Étiquettes en pastilles', true ),
				'art_voisins'        => array( 'oui', 'Article précédent / suivant', true ),
				'art_prec'           => array( 'texte', 'Libellé « Article précédent »', 'Article précédent' ),
				'art_suiv'           => array( 'texte', 'Libellé « Article suivant »', 'Article suivant' ),
				'art_aussi'          => array( 'oui', 'Section « À lire aussi »', true ),
				'art_aussi_nb'       => array( 'nombre', 'Nombre d’articles suggérés (1 à 6)', 3 ),
				'art_aussi_surtitre' => array( 'texte', 'Petite étiquette (vide = masquée)', 'Continuer la lecture', true ),
				'art_aussi_titre'    => array( 'texte', 'Titre (articles de la même catégorie)', 'À lire aussi' ),
				'art_recent_titre'   => array( 'texte', 'Titre (si la catégorie n’a pas d’autre article)', 'Dernières actualités' ),
				'art_aussi_lien_cat' => array( 'texte', 'Lien vers la catégorie (%s = nom de la catégorie)', 'Tous les articles « %s »' ),
				'art_aussi_lien'     => array( 'texte', 'Lien vers toutes les actualités', 'Toutes les actualités' ),
				'art_carte_extrait'  => array( 'oui', 'Extrait dans les cartes', true ),
				'art_carte_suite'    => array( 'texte', 'Lien en bas des cartes (vide = masqué)', 'Lire la suite', true ),
			),
		),
		'style'   => array(
			'label'  => 'Couleurs et animations',
			'champs' => array(
				'art_c_bandeau' => array( 'couleur', 'Fond du bandeau', '#071b38' ),
				'art_c_accent'  => array( 'couleur', 'Vert doux (accents, barre de progression)', '#7fb894' ),
				'art_c_bouton'  => array( 'couleur', 'Vert des boutons et de l’anneau', '#1d7a45' ),
				'art_c_fond'    => array( 'couleur', 'Fond de la section « À lire aussi »', '#eef2f6' ),
				'art_animations' => array( 'oui', 'Animations douces (apparition des cartes). Toujours coupées si l’appareil demande moins de mouvement.', true ),
			),
		),
	);
}

/**
 * Valeurs par défaut des réglages de la page d'un article.
 *
 * @return array
 */
function lephare_actu_reglages_article_defaut() {
	$o = array();
	foreach ( lephare_actu_schema_article() as $section ) {
		foreach ( $section['champs'] as $cle => $champ ) {
			$o[ $cle ] = $champ[2];
		}
	}
	return $o;
}

/**
 * Réglages enregistrés de la page d'un article (option « lephare_actu_article »), complétés par les valeurs par défaut.
 *
 * @return array
 */
function lephare_actu_reglages_article() {
	$o                 = lephare_actu_nettoyer( (array) get_option( 'lephare_actu_article', array() ), lephare_actu_schema_article() );
	$o['art_aussi_nb'] = max( 1, min( 6, (int) $o['art_aussi_nb'] ) );
	$o['art_sommaire_min'] = max( 1, min( 10, (int) $o['art_sommaire_min'] ) );
	return $o;
}

add_action(
	'admin_init',
	static function () {
		register_setting(
			'lephare_actu_article',
			'lephare_actu_article',
			array(
				'type'              => 'array',
				'sanitize_callback' => static function ( $brut ) {
					// Les cases décochées ne sont pas envoyées : on les remet à « non ».
					$brut = is_array( $brut ) ? $brut : array();
					foreach ( lephare_actu_schema_article() as $section ) {
						foreach ( $section['champs'] as $cle => $champ ) {
							if ( 'oui' === $champ[0] && ! isset( $brut[ $cle ] ) ) {
								$brut[ $cle ] = '0';
							}
						}
					}
					return lephare_actu_nettoyer( wp_unslash( $brut ), lephare_actu_schema_article() );
				},
				'default'           => array(),
			)
		);
	}
);

add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'edit.php', 'Page d’un article', 'Page d’un article', 'manage_options', 'lephare-actu-article', 'lephare_actu_ecran_article' );
	}
);

/**
 * Écran « Articles → Page d'un article » : un formulaire WordPress classique (Settings API, nonce inclus).
 */
function lephare_actu_ecran_article() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = lephare_actu_reglages_article();
	echo '<div class="wrap"><h1>Page d’un article</h1>';
	echo '<p>Ces réglages s’appliquent à toutes les actualités (articles hors Prédications). Le contenu de chaque article (titre, chapeau = extrait, image mise en avant et sa légende, texte, catégories, étiquettes) se modifie dans l’article lui-même. La page /actualites/ se règle dans Elementor (widget « Actualités »).</p>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'lephare_actu_article' );
	foreach ( lephare_actu_schema_article() as $section ) {
		echo '<h2>' . esc_html( $section['label'] ) . '</h2><table class="form-table" role="presentation"><tbody>';
		foreach ( $section['champs'] as $cle => $champ ) {
			$id  = 'lpa-' . $cle;
			$nom = 'lephare_actu_article[' . $cle . ']';
			echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $champ[1] ) . '</label></th><td>';
			switch ( $champ[0] ) {
				case 'oui':
					echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nom ) . '" value="1"' . checked( $o[ $cle ], true, false ) . '>';
					break;
				case 'nombre':
					echo '<input type="number" class="small-text" min="1" max="10" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nom ) . '" value="' . (int) $o[ $cle ] . '">';
					break;
				case 'couleur':
					echo '<input type="color" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nom ) . '" value="' . esc_attr( $o[ $cle ] ) . '"> <code>' . esc_html( $champ[2] ) . '</code> par défaut';
					break;
				default:
					echo '<input type="text" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nom ) . '" value="' . esc_attr( $o[ $cle ] ) . '">';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}
	submit_button( 'Enregistrer les réglages' );
	echo '</form></div>';
}

/**
 * Ajoute un identifiant aux H2 du contenu et renvoie [ contenu, titres ].
 *
 * @param string $html Contenu.
 * @return array
 */
function lephare_actu_titres( $html ) {
	$titres = array();
	$vus    = array();
	$html   = preg_replace_callback(
		'~<h2([^>]*)>(.*?)</h2>~is',
		static function ( $m ) use ( &$titres, &$vus ) {
			$texte = trim( wp_strip_all_tags( $m[2] ) );
			if ( '' === $texte ) {
				return $m[0];
			}
			if ( preg_match( '~\sid=["\']([^"\']+)["\']~', $m[1], $id ) ) {
				$ancre = $id[1];
				$attrs = $m[1];
			} else {
				$ancre = 'section-' . ( sanitize_title( $texte ) ?: count( $titres ) + 1 );
				while ( isset( $vus[ $ancre ] ) ) {
					$ancre .= '-2';
				}
				$attrs = $m[1] . ' id="' . esc_attr( $ancre ) . '"';
			}
			$vus[ $ancre ] = true;
			$titres[]      = array( $ancre, $texte );
			return '<h2' . $attrs . '>' . $m[2] . '</h2>';
		},
		$html
	);
	return array( $html, $titres );
}

/**
 * Partage : boutons WhatsApp, Facebook et « Copier le lien » (icône + texte). Aucun script externe, aucun suivi.
 *
 * @param string $url   Adresse de l'article.
 * @param string $titre Titre.
 * @return string
 */
function lephare_actu_partage( $url, $titre ) {
	$icones = array(
		'whatsapp' => '<path d="M20 11.6a8 8 0 0 1-11.8 7l-4.2 1.1 1.1-4A8 8 0 1 1 20 11.6z"/><path d="M9 8.5c.2 2.7 2.8 5.3 5.5 5.6l1.2-1.3-1.8-.9-.8.8a4.2 4.2 0 0 1-2.3-2.3l.8-.8-.9-1.8z"/>',
		'facebook' => '<path d="M14 8h2.5V4.5H14a3.5 3.5 0 0 0-3.5 3.5v2.5H8V14h2.5v6.5H14V14h2.5l.5-3.5h-3V8.5A.5.5 0 0 1 14 8z"/>',
		'lien'     => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
	);
	$svg = static function ( $nom ) use ( $icones ) {
		return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icones[ $nom ] . '</svg>';
	};
	$out = '<div class="lpa-partage lpa-art-partage"><p class="lpa-art-bloc__titre">' . esc_html( lephare_actu_opt( 'art_partage_titre' ) ) . '</p><ul class="lpa-art-partage__liste" role="list">';
	if ( lephare_actu_opt( 'art_whatsapp' ) ) {
		$out .= '<li><a class="lpa-art-partage__btn" href="' . esc_url( 'https://wa.me/?text=' . rawurlencode( $titre . ' ' . $url ), array( 'https' ) ) . '" target="_blank" rel="noopener noreferrer">' . $svg( 'whatsapp' ) . 'WhatsApp<span class="screen-reader-text"> (nouvel onglet)</span></a></li>';
	}
	if ( lephare_actu_opt( 'art_facebook' ) ) {
		$out .= '<li><a class="lpa-art-partage__btn" href="' . esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ), array( 'https' ) ) . '" target="_blank" rel="noopener noreferrer">' . $svg( 'facebook' ) . 'Facebook<span class="screen-reader-text"> (nouvel onglet)</span></a></li>';
	}
	if ( lephare_actu_opt( 'art_copier' ) ) {
		$out .= '<li><button type="button" class="lpa-art-partage__btn lpa-partage__copier" data-lpa-copier="' . esc_attr( $url ) . '">' . $svg( 'lien' ) . esc_html( lephare_actu_opt( 'art_copier_txt' ) ) . '</button></li>';
	}
	$out .= '</ul>';
	return $out . '<p class="lpa-partage__statut" role="status" aria-live="polite" data-lpa-statut></p></div>';
}

/**
 * Carte « article voisin » (précédent / suivant) avec miniature.
 *
 * @param WP_Post $p     Article.
 * @param string  $sens  prev | next.
 * @return string
 */
function lephare_actu_voisin( $p, $sens ) {
	$suivant = 'next' === $sens;
	$image   = (int) get_post_thumbnail_id( $p );
	$out     = '<li class="lpa-art-voisin lpa-art-voisin--' . ( $suivant ? 'suiv' : 'prec' ) . '"><a href="' . esc_url( get_permalink( $p ) ) . '" rel="' . esc_attr( $sens ) . '">';
	$out    .= '<span class="lpa-art-voisin__media" aria-hidden="true">' . ( $image ? wp_get_attachment_image( $image, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) ) : '' ) . '</span>';
	$out    .= '<span class="lpa-art-voisin__texte"><span class="lpa-art-voisin__label">' . esc_html( lephare_actu_opt( $suivant ? 'art_suiv' : 'art_prec' ) ) . '</span>';
	$out    .= '<span class="lpa-art-voisin__titre">' . esc_html( get_the_title( $p ) ) . '</span></span>';
	$out    .= '<span class="lpa-art-voisin__fleche" aria-hidden="true">' . lephare_actu_picto( 'fleche' ) . '</span></a></li>';
	return $out;
}

/**
 * Catégories choisies dans le widget « Actualités » de la page /actualites/ (lecture seule des réglages Elementor),
 * pour que les suggestions restent dans la rubrique. Vide si aucune n'est choisie.
 *
 * @return string[]
 */
function lephare_actu_slugs_page() {
	static $slugs = null;
	if ( null !== $slugs ) {
		return $slugs;
	}
	$slugs = array();
	$page  = get_page_by_path( 'actualites' );
	$data  = $page ? json_decode( (string) get_post_meta( $page->ID, '_elementor_data', true ), true ) : null;
	$pile  = is_array( $data ) ? $data : array();
	while ( $pile ) {
		$el = array_pop( $pile );
		if ( 'lephare-actualites' === ( $el['widgetType'] ?? '' ) ) {
			$c     = $el['settings']['categories'] ?? array();
			$slugs = array_values( array_filter( array_map( 'sanitize_title', is_array( $c ) ? $c : explode( ',', (string) $c ) ) ) );
			break;
		}
		foreach ( $el['elements'] ?? array() as $enfant ) {
			$pile[] = $enfant;
		}
	}
	return $slugs;
}

/**
 * Affichage d'un article (appelé par Kadence via l'action kadence_single).
 */
function lephare_actu_article() {
	while ( have_posts() ) {
		the_post();
		$post  = get_post();
		$d     = lephare_actu_donnees( $post );
		$cat   = lephare_actu_categorie_principale( $post->ID );
		$liste = get_page_by_path( 'actualites' );
		$liste = $liste ? get_permalink( $liste ) : home_url( '/' );

		list( $contenu, $titres ) = lephare_actu_titres( apply_filters( 'the_content', get_the_content() ) );

		$r = lephare_actu_reglages_article();
		lephare_actu_opt( $r );
		$exclus   = lephare_actu_ids_sermons();
		$sommaire = $r['art_sommaire'] && count( $titres ) >= $r['art_sommaire_min'];
		$partage  = $r['art_partage'] && ( $r['art_whatsapp'] || $r['art_facebook'] || $r['art_copier'] );
		$outils   = $r['art_anneau'] || $sommaire || $partage;
		// Couleurs choisies dans « Articles → Page d'un article ».
		$couleurs = '--lpa-encre-bandeau:' . $r['art_c_bandeau'] . ';--lpa-sauge:' . $r['art_c_accent'] . ';--lpa-vert-bouton:' . $r['art_c_bouton'] . ';--lpa-fond-section:' . $r['art_c_fond'];
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- fonctions du plugin (échappées à l'intérieur) et the_content.

		// Grille unique de 1080 px : bandeau, photo, texte + colonne d'outils, fin d'article. Tous les bords gauches sont alignés.
		echo '<div id="primary" class="content-area lpa-article-zone lpa-art" style="' . esc_attr( $couleurs ) . '">' . ( $r['art_progression'] ? '<div class="lpa-progression" aria-hidden="true"><span></span></div>' : '' ) . '<div id="main" class="site-main">';
		echo '<article id="post-' . (int) $post->ID . '" class="' . esc_attr( implode( ' ', get_post_class( 'lpa-article' ) ) ) . '">';

		// 1. Bandeau : retour, catégorie, titre, chapeau, date et temps de lecture.
		echo '<header class="lpa-art-hero">' . ( $r['art_formes'] ? '<div class="lpa-art-hero__deco" aria-hidden="true"><span></span><span></span></div>' : '' ) . '<div class="lpa-art-cadre lpa-art-hero__inner">';
		echo '<a class="lpa-art-retour" href="' . esc_url( $liste ) . '"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>' . esc_html( $r['art_retour'] ) . '</a>';
		if ( $cat && $r['art_categorie'] ) {
			echo '<p class="lpa-art-cat"><a href="' . esc_url( add_query_arg( 'lpa_cat', $cat->slug, $liste ) . '#lpa-journal' ) . '"><span class="screen-reader-text">Catégorie : </span>' . esc_html( $cat->name ) . '</a></p>';
		}
		echo '<h1 class="lpa-article__titre">' . esc_html( get_the_title() ) . '</h1>';
		if ( has_excerpt() ) {
			echo '<p class="lpa-article__chapeau">' . esc_html( get_the_excerpt() ) . '</p>';
		}
		echo '<ul class="lpa-art-infos" role="list"><li>' . lephare_actu_picto( 'calendrier' ) . '<time datetime="' . esc_attr( $d['date_iso'] ) . '">' . esc_html( $d['date_txt'] ) . '</time></li>';
		echo '<li>' . lephare_actu_picto( 'horloge' ) . (int) $d['minutes'] . ' min de lecture</li>';
		if ( $sommaire && $r['art_parties'] ) {
			echo '<li>' . count( $titres ) . ' ' . esc_html( $r['art_mot_parties'] ) . '</li>';
		}
		echo '</ul></div></header>';

		// 2. Photo principale qui chevauche le bandeau, avec légende (légende de la Médiathèque, sinon texte alternatif).
		if ( $d['image'] ) {
			$legende = wp_get_attachment_caption( $d['image'] );
			if ( ! $legende ) {
				$legende = get_post_meta( $d['image'], '_wp_attachment_image_alt', true );
			}
			echo '<figure class="lpa-art-cadre lpa-art-image"><div class="lpa-art-image__cadre">' . lephare_actu_image( $d['image'], 'full', '(max-width: 1128px) 100vw, 1080px', true ) . '</div>';
			if ( $legende ) {
				echo '<figcaption>' . esc_html( $legende ) . '</figcaption>';
			}
			echo '</figure>';
		}

		// 3. Texte (680 px) + colonne d'outils collante sur ordinateur : progression, sommaire actif, partage.
		echo '<div class="lpa-art-cadre lpa-art-corps' . ( $d['image'] ? '' : ' lpa-art-corps--sans-image' ) . ( $outils ? '' : ' lpa-art-corps--seul' ) . '">';
		if ( $outils ) {
			echo '<aside class="lpa-art-outils" aria-label="Outils de lecture">';
		}
		if ( $r['art_anneau'] ) {
			echo '<div class="lpa-art-bloc lpa-art-avancement" aria-hidden="true"><svg class="lpa-art-anneau" viewBox="0 0 52 52" width="52" height="52"><circle cx="26" cy="26" r="22" class="lpa-art-anneau__fond"/><circle cx="26" cy="26" r="22" class="lpa-art-anneau__trait" data-lpa-anneau/></svg>';
			echo '<span class="lpa-art-avancement__texte"><strong><span data-lpa-pourcent>0</span> %</strong> ' . esc_html( $r['art_lu'] ) . '<span>' . (int) $d['minutes'] . ' min de lecture</span></span></div>';
		}
		if ( $sommaire ) {
			echo '<nav class="lpa-art-bloc lpa-art-sommaire" aria-labelledby="lpa-sommaire-titre"><p class="lpa-art-bloc__titre" id="lpa-sommaire-titre">' . esc_html( $r['art_sommaire_titre'] ) . '</p><ol>';
			foreach ( $titres as $i => $t ) {
				echo '<li><a class="scroll-ignore" href="#' . esc_attr( $t[0] ) . '"><span class="lpa-art-sommaire__num" aria-hidden="true">' . sprintf( '%02d', $i + 1 ) . '</span>' . esc_html( $t[1] ) . '</a></li>';
			}
			echo '</ol></nav>';
		}
		if ( $partage ) {
			echo '<div class="lpa-art-bloc">' . lephare_actu_partage( get_permalink(), get_the_title() ) . '</div>';
		}
		if ( $outils ) {
			echo '</aside>';
		}

		echo '<div class="lpa-lecture">';
		echo $contenu;
		wp_link_pages();
		$tags = $r['art_etiquettes'] ? get_the_tags() : false;
		if ( $tags ) {
			echo '<p class="lpa-etiquettes"><span class="screen-reader-text">Étiquettes : </span>';
			foreach ( $tags as $t ) {
				echo '<a href="' . esc_url( get_tag_link( $t ) ) . '" rel="tag">' . esc_html( $t->name ) . '</a>';
			}
			echo '</p>';
		}
		echo '</div></div>';

		// 4. Précédent / suivant : cartes avec miniature (même catégorie, jamais une prédication).
		$prec = $r['art_voisins'] ? get_adjacent_post( (bool) $cat, $exclus, true ) : null;
		$suiv = $r['art_voisins'] ? get_adjacent_post( (bool) $cat, $exclus, false ) : null;
		if ( $prec || $suiv ) {
			echo '<nav class="lpa-art-cadre lpa-art-voisins" aria-label="Articles précédent et suivant"><ul role="list">';
			echo $prec ? lephare_actu_voisin( $prec, 'prev' ) : '';
			echo $suiv ? lephare_actu_voisin( $suiv, 'next' ) : '';
			echo '</ul></nav>';
		}
		echo '</article>';

		// 5. À lire aussi : 3 articles de la même catégorie ; si elle n'en a pas d'autre, les plus récents des
		//    catégories de la page Actualités. Mêmes cartes que la page Actualités, sur fond alterné.
		$base  = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $r['art_aussi_nb'],
			'category__not_in'    => $exclus,
			'post__not_in'        => array( $post->ID ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		$meme  = true;
		$aussi = ( $r['art_aussi'] && $cat ) ? new WP_Query( $base + array( 'cat' => $cat->term_id ) ) : null;
		if ( $r['art_aussi'] && ( ! $aussi || ! $aussi->posts ) ) {
			$meme  = false;
			$ids   = array_map( static fn( $t ) => (int) $t->term_id, array_values( lephare_actu_categories( lephare_actu_slugs_page() ) ) );
			$aussi = new WP_Query( $base + ( $ids ? array( 'category__in' => $ids ) : array() ) );
		}
		if ( $aussi && $aussi->posts ) {
			update_post_thumbnail_cache( $aussi );
			$nb   = count( $aussi->posts );
			$tout = $meme
				? '<a class="lpa-art-aussi__tout" href="' . esc_url( add_query_arg( 'lpa_cat', $cat->slug, $liste ) . '#lpa-journal' ) . '">' . esc_html( sprintf( $r['art_aussi_lien_cat'], $cat->name ) ) . lephare_actu_picto( 'fleche' ) . '</a>'
				: '<a class="lpa-art-aussi__tout" href="' . esc_url( $liste ) . '">' . esc_html( $r['art_aussi_lien'] ) . lephare_actu_picto( 'fleche' ) . '</a>';
			echo '<section class="lpa lpa-art-aussi' . ( $r['art_animations'] ? '' : ' lpa--calme' ) . '" style="' . esc_attr( $couleurs ) . '" aria-labelledby="lpa-aussi-titre"><div class="lpa-art-cadre">';
			echo '<div class="lpa-art-aussi__tete"><div>' . ( '' !== $r['art_aussi_surtitre'] ? '<p class="lpa-surtitre">' . esc_html( $r['art_aussi_surtitre'] ) . '</p>' : '' ) . '<h2 class="lpa-art-aussi__titre" id="lpa-aussi-titre">' . esc_html( $meme ? $r['art_aussi_titre'] : $r['art_recent_titre'] ) . '</h2></div>' . $tout . '</div>';
			echo '<ul class="lpa-grille' . ( 1 === $nb ? ' lpa-grille--une-carte' : ( 2 === $nb ? ' lpa-grille--deux' : '' ) ) . '" role="list">';
			foreach ( $aussi->posts as $p ) {
				echo lephare_actu_carte( lephare_actu_donnees( $p ), 'h3', true, $r['art_carte_extrait'], $r['art_carte_suite'] );
			}
			echo '</ul></div></section>';
		}
		// phpcs:enable

		echo '</div></div>';
	}
}

/* =========================================================================
 * 5. Widget Elementor « Actualités » (catégorie LePhare)
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
		require_once __DIR__ . '/includes/class-lephare-actualites-widget.php';
		$widgets_manager->register( new LePhare_Actualites_Widget() );
	}
);
