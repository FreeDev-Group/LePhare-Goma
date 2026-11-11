<?php
/**
 * Plugin Name:       LePhare – Liste des événements
 * Description:       Shortcode [lephare_evenements] qui affiche les événements de The Events Calendar (à la une, à venir, passés) pour la page Événements construite avec Elementor.
 * Version:           3.5.2
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Requires Plugins:  the-events-calendar
 * Author:            FREEDEV – LePhare/Goma
 * License:           GPL-2.0-or-later
 * Text Domain:       lephare-evenements
 *
 * Utilisation (widget « Shortcode » d'Elementor) :
 *   [lephare_evenements quand="a-la-une" style="vertical"]           Prochain événement (carte image en haut).
 *   [lephare_evenements quand="a-la-une"]                            Prochain événement (carte horizontale).
 *   [lephare_evenements quand="a-venir" decaler="1" style="mini"]    Liste « À suivre » (colonne latérale).
 *   [lephare_evenements quand="a-venir" decaler="1"]                 Événements suivants en grille de cartes.
 *   [lephare_evenements quand="passes" style="compact"]              Événements passés, liste compacte.
 *
 * Les événements sont gérés par les admins dans « Événements » (The Events Calendar) :
 * titre, date, heure, lieu, description, image à la une. Rien n'est écrit en dur ici.
 *
 * La mise en page (sections, titres, couleurs, boutons, animations d'entrée) est faite avec les
 * réglages natifs d'Elementor. Ce plugin ne met en forme que le contenu généré par le shortcode.
 * Les cartes apparaissent en cascade au défilement (assets/lephare-evenements.js), sauf si
 * l'utilisateur a demandé moins de mouvements (prefers-reduced-motion) ou sans JavaScript.
 */

defined( 'ABSPATH' ) || exit;

const LEPHARE_EVTS_VERSION = '3.5.2';

/**
 * Feuille de style + script d'animation : chargés uniquement sur les pages qui utilisent le shortcode.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_register_style(
			'lephare-evenements',
			plugins_url( 'assets/lephare-evenements.css', __FILE__ ),
			array(),
			LEPHARE_EVTS_VERSION
		);
		wp_register_script(
			'lephare-evenements',
			plugins_url( 'assets/lephare-evenements.js', __FILE__ ),
			array(),
			LEPHARE_EVTS_VERSION,
			array( 'in_footer' => true, 'strategy' => 'defer' )
		);

		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			$content = (string) get_post_field( 'post_content', $post_id ) . (string) get_post_meta( $post_id, '_elementor_data', true );
			if ( false !== strpos( $content, 'lephare_evenements' ) ) {
				wp_enqueue_style( 'lephare-evenements' );
				wp_enqueue_script( 'lephare-evenements' );
			}
		}
	}
);

add_shortcode( 'lephare_evenements', 'lephare_evts_shortcode' );

/**
 * Rendu du shortcode.
 *
 * @param array|string $atts Attributs.
 * @return string
 */
function lephare_evts_shortcode( $atts ) {
	if ( ! function_exists( 'tribe_events' ) ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'quand'   => 'a-venir', // a-la-une | a-venir | passes.
			'nombre'  => 9,
			'decaler' => 0,
			'style'   => 'grille', // grille | compact | mini | vertical (à la une).
			'titre'   => 'h3',     // Niveau des titres des cartes de la grille (h3 | h4).
		),
		$atts,
		'lephare_evenements'
	);

	wp_enqueue_style( 'lephare-evenements' );
	wp_enqueue_script( 'lephare-evenements' );

	$quand   = sanitize_key( $atts['quand'] );
	$style   = sanitize_key( $atts['style'] );
	$nombre  = max( 1, min( 50, (int) $atts['nombre'] ) );
	$decaler = max( 0, (int) $atts['decaler'] );

	if ( 'a-la-une' === $quand ) {
		$events = lephare_evts_requete( false, 1, 0 );
		return $events
			? lephare_evts_carte_une( $events[0], 'vertical' === $style )
			: lephare_evts_vide( 'Aucun événement n’est programmé pour le moment.' );
	}

	if ( 'apercu' === $quand ) {
		return lephare_evts_apercu();
	}

	if ( 'resume' === $quand ) {
		return 'passes' === $style ? lephare_evts_resume_passes() : lephare_evts_resume();
	}

	if ( 'mini' === $style && 'passes' !== $quand ) {
		$events = lephare_evts_requete( false, $nombre, $decaler );
		if ( empty( $events ) ) {
			return lephare_evts_vide( 'Aucun autre événement programmé pour le moment.', true );
		}
		$html = '<ul class="lph-mini" role="list">';
		foreach ( $events as $rang => $event ) {
			$html .= lephare_evts_mini( $event, $rang );
		}
		return $html . '</ul>';
	}

	$passes = ( 'passes' === $quand );
	$events = lephare_evts_requete( $passes, $nombre, $decaler );

	if ( empty( $events ) ) {
		if ( $passes ) {
			$msg = 'Aucun événement passé pour le moment.';
		} elseif ( $decaler > 0 ) {
			$msg = 'Aucun autre événement à venir pour le moment.';
		} else {
			$msg = 'Aucun événement à venir pour le moment.';
		}
		return lephare_evts_vide( $msg );
	}

	// Événements passés en cartes avec image (style « cartes »).
	if ( $passes && 'cartes' === $style ) {
		$html = '<ul class="lph-grille lph-grille--passes" role="list">';
		foreach ( $events as $rang => $event ) {
			$html .= lephare_evts_carte( $event, $rang, sanitize_key( $atts['titre'] ), true );
		}
		return $html . '</ul>';
	}

	if ( $passes || 'compact' === $atts['style'] ) {
		$html = '<ul class="lph-liste" role="list">';
		foreach ( $events as $rang => $event ) {
			$html .= lephare_evts_ligne( $event, $passes, $rang );
		}
		return $html . '</ul>';
	}

	$html = '<ul class="lph-grille" role="list">';
	foreach ( $events as $rang => $event ) {
		$html .= lephare_evts_carte( $event, $rang, sanitize_key( $atts['titre'] ) );
	}
	return $html . '</ul>';
}

/**
 * Récupère les événements publiés.
 *
 * @param bool $passes  Événements passés ?
 * @param int  $nombre  Nombre maximum.
 * @param int  $decaler Nombre d'événements à sauter.
 * @return WP_Post[]
 */
function lephare_evts_requete( $passes, $nombre, $decaler ) {
	$query = tribe_events()->where( 'status', 'publish' )->per_page( $nombre + $decaler );
	if ( $passes ) {
		$query = $query->where( 'ends_before', 'now' )->order_by( 'event_date', 'DESC' );
	} else {
		$query = $query->where( 'ends_after', 'now' )->order_by( 'event_date', 'ASC' );
	}
	return array_slice( (array) $query->all(), $decaler, $nombre );
}

/**
 * Données d'un événement prêtes à afficher.
 *
 * @param WP_Post $event Événement.
 * @return array
 */
function lephare_evts_donnees( $event ) {
	$id      = $event->ID;
	$debut   = lephare_evts_date( get_post_meta( $id, '_EventStartDate', true ) );
	$fin     = lephare_evts_date( get_post_meta( $id, '_EventEndDate', true ) );
	$journee = function_exists( 'tribe_event_is_all_day' ) && tribe_event_is_all_day( $id );

	$heure = '';
	if ( $journee ) {
		$heure = 'Toute la journée';
	} elseif ( $debut ) {
		$heure = lephare_evts_heure( $debut );
		if ( $fin && $fin > $debut && $fin->format( 'Y-m-d' ) === $debut->format( 'Y-m-d' ) ) {
			$heure .= ' – ' . lephare_evts_heure( $fin );
		}
	}

	$jour_complet = $debut ? lephare_evts_jour( $debut ) : '';
	if ( $debut && $fin && $fin->format( 'Y-m-d' ) !== $debut->format( 'Y-m-d' ) ) {
		$jour_complet = 'Du ' . $jour_complet . ' au ' . lephare_evts_jour( $fin );
	}

	$lieu = function_exists( 'tribe_get_venue' ) ? wp_strip_all_tags( (string) tribe_get_venue( $id ) ) : '';
	if ( $lieu && function_exists( 'tribe_get_city' ) ) {
		$ville = wp_strip_all_tags( (string) tribe_get_city( $id ) );
		if ( $ville && false === strpos( $lieu, $ville ) ) {
			$lieu .= ', ' . $ville;
		}
	}

	$extrait = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_strip_all_tags( strip_shortcodes( $event->post_content ) );

	return array(
		'id'           => $id,
		'url'          => get_permalink( $id ),
		'titre'        => get_the_title( $id ),
		'titre_id'     => 'lph-evt-' . $id . '-' . wp_unique_id(),
		'debut'        => $debut,
		'jour_complet' => $jour_complet ? lephare_evts_majuscule( $jour_complet ) : '',
		'datetime'     => $debut ? $debut->format( $journee ? 'Y-m-d' : 'Y-m-d\TH:i' ) : '',
		'heure'        => $heure,
		'lieu'         => $lieu,
		'extrait'      => wp_trim_words( $extrait, 30, '…' ),
	);
}

/**
 * Badge date (« 22 » + « NOV »). Masqué aux lecteurs d'écran : la date complète est donnée en texte.
 *
 * @param DateTimeImmutable|null $debut Début.
 * @param string                 $class Classe supplémentaire.
 * @return string
 */
function lephare_evts_badge( $debut, $class = '' ) {
	if ( ! $debut ) {
		return '';
	}
	$mois = array( 1 => 'JANV', 'FÉVR', 'MARS', 'AVR', 'MAI', 'JUIN', 'JUIL', 'AOÛT', 'SEPT', 'OCT', 'NOV', 'DÉC' );
	return '<span class="lph-badge ' . esc_attr( $class ) . '" aria-hidden="true">'
		. '<span class="lph-badge__jour">' . esc_html( $debut->format( 'j' ) ) . '</span>'
		. '<span class="lph-badge__mois">' . esc_html( $mois[ (int) $debut->format( 'n' ) ] ) . '</span>'
		. '</span>';
}

/**
 * Lignes « icône + texte » (date, heure, lieu).
 *
 * @param array $d       Données.
 * @param array $champs  Champs à afficher.
 * @return string
 */
function lephare_evts_meta( $d, $champs ) {
	$out = '<ul class="lph-meta" role="list">';
	foreach ( $champs as $champ ) {
		if ( 'date' === $champ && $d['jour_complet'] ) {
			$out .= '<li>' . lephare_evts_icone( 'calendrier' ) . '<time datetime="' . esc_attr( $d['datetime'] ) . '">' . esc_html( $d['jour_complet'] ) . '</time></li>';
		} elseif ( 'date-sr' === $champ && $d['jour_complet'] ) {
			$out .= '<li class="screen-reader-text"><time datetime="' . esc_attr( $d['datetime'] ) . '">' . esc_html( $d['jour_complet'] ) . '</time></li>';
		} elseif ( 'heure' === $champ && $d['heure'] ) {
			$out .= '<li>' . lephare_evts_icone( 'horloge' ) . '<span>' . esc_html( $d['heure'] ) . '</span></li>';
		} elseif ( 'lieu' === $champ && $d['lieu'] ) {
			$out .= '<li>' . lephare_evts_icone( 'repere' ) . '<span>' . esc_html( $d['lieu'] ) . '</span></li>';
		} elseif ( 'lieu-toujours' === $champ ) {
			// Ligne « Lieu » toujours affichée : lieu de l'événement, ou « Lieu à confirmer » si aucun lieu n'est renseigné.
			$out .= '<li class="lph-meta__lieu' . ( $d['lieu'] ? '' : ' lph-meta__lieu--a-confirmer' ) . '">' . lephare_evts_icone( 'repere' )
				. '<span><span class="screen-reader-text">Lieu : </span>' . esc_html( $d['lieu'] ? $d['lieu'] : 'Lieu à confirmer' ) . '</span></li>';
		}
	}
	return $out . '</ul>';
}

/**
 * Image à la une (texte alternatif issu de la Médiathèque).
 *
 * @param int    $id     Événement.
 * @param string $taille Taille.
 * @param string $sizes  Attribut sizes.
 * @return string
 */
function lephare_evts_image( $id, $taille, $sizes ) {
	if ( ! has_post_thumbnail( $id ) ) {
		return '<span class="lph-image-vide" aria-hidden="true">' . lephare_evts_icone( 'calendrier' ) . '</span>';
	}
	return get_the_post_thumbnail( $id, $taille, array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => $sizes ) );
}

/**
 * Prochain événement : grande carte (horizontale ou image en haut). Un seul lien : le bouton.
 *
 * @param WP_Post $event    Événement.
 * @param bool    $vertical Image au-dessus du texte ?
 * @return string
 */
function lephare_evts_carte_une( $event, $vertical = false ) {
	$d = lephare_evts_donnees( $event );

	$out  = '<div class="lph-une-cadre"><article class="lph-une' . ( $vertical ? ' lph-une--vertical' : '' ) . '" aria-labelledby="' . esc_attr( $d['titre_id'] ) . '" data-lph-reveal>';
	$out .= '<div class="lph-une__media">' . lephare_evts_image( $d['id'], 'large', $vertical ? '(max-width: 1024px) 100vw, 780px' : '(max-width: 900px) 100vw, 660px' ) . lephare_evts_badge( $d['debut'], 'lph-badge--grand' ) . '<span class="lph-une__etiquette">À la une</span></div>';
	$out .= '<div class="lph-une__contenu">';
	$rebours = lephare_evts_rebours( $d['debut'] );
	if ( $rebours ) {
		$out .= '<p class="lph-rebours"><span class="lph-rebours__point" aria-hidden="true"></span>' . esc_html( $rebours ) . '</p>';
	}
	$out .= '<h3 class="lph-une__titre" id="' . esc_attr( $d['titre_id'] ) . '">' . esc_html( $d['titre'] ) . '</h3>';
	$out .= lephare_evts_meta( $d, array( 'date', 'heure', 'lieu-toujours' ) );
	if ( $d['extrait'] ) {
		$out .= '<p class="lph-une__extrait">' . esc_html( $d['extrait'] ) . '</p>';
	}
	$out .= '<div class="lph-une__actions">';
	$out .= '<a class="lph-btn" href="' . esc_url( $d['url'] ) . '">Voir les détails<span class="screen-reader-text"> : ' . esc_html( $d['titre'] ) . '</span></a>';
	$ical = function_exists( 'tribe_get_single_ical_link' ) ? tribe_get_single_ical_link( $d['id'] ) : '';
	if ( $ical ) {
		$out .= '<a class="lph-btn-secondaire" href="' . esc_url( $ical ) . '">' . lephare_evts_icone( 'calendrier' ) . 'Ajouter à l’agenda<span class="screen-reader-text"> : ' . esc_html( $d['titre'] ) . ' (fichier iCal)</span></a>';
	}
	$out .= '</div>';
	$out .= '</div></article></div>';

	return $out;
}

/**
 * Résumé pour l'en-tête de section : nombre d'événements à venir + abonnement à l'agenda (iCal de TEC).
 *
 * @return string
 */
function lephare_evts_resume() {
	$total = (int) tribe_events()->where( 'status', 'publish' )->where( 'ends_after', 'now' )->count();
	$ical  = function_exists( 'tribe_get_ical_link' ) ? tribe_get_ical_link() : '';

	$out  = '<div class="lph-resume">';
	$out .= '<p class="lph-resume__total">' . lephare_evts_icone( 'calendrier' ) . '<span><strong>' . $total . '</strong> ' . ( $total > 1 ? 'événements à venir' : 'événement à venir' ) . '</span></p>';
	if ( $ical ) {
		$out .= '<a class="lph-resume__lien" href="' . esc_url( $ical ) . '">' . lephare_evts_icone( 'telecharger' ) . 'S’abonner à l’agenda<span class="screen-reader-text"> (fichier iCal pour Google Agenda, Apple Calendrier ou Outlook)</span></a>';
	}
	return $out . '</div>';
}

/**
 * Résumé pour l'en-tête « Événements passés » : nombre d'événements passés publiés.
 *
 * @return string
 */
function lephare_evts_resume_passes() {
	$total = (int) tribe_events()->where( 'status', 'publish' )->where( 'ends_before', 'now' )->count();
	return '<div class="lph-resume"><p class="lph-resume__total">' . lephare_evts_icone( 'horloge' ) . '<span><strong>' . $total . '</strong> ' . ( $total > 1 ? 'événements passés' : 'événement passé' ) . '</span></p></div>';
}

/**
 * Carte « Prochain rendez-vous » pour la bannière (fond sombre) : prochain événement réel
 * + nombre d'événements à venir. Un seul lien, étendu à toute la carte.
 *
 * @return string
 */
function lephare_evts_apercu() {
	$total  = (int) tribe_events()->where( 'status', 'publish' )->where( 'ends_after', 'now' )->count();
	$events = lephare_evts_requete( false, 1, 0 );

	$out = '<div class="lph-apercu" data-lph-reveal>';
	$out .= '<p class="lph-apercu__etiquette"><span class="lph-apercu__point" aria-hidden="true"></span>Prochain rendez-vous</p>';

	if ( $events ) {
		$d       = lephare_evts_donnees( $events[0] );
		$rebours = lephare_evts_rebours( $d['debut'] );
		$out    .= '<div class="lph-apercu__corps">';
		if ( $rebours ) {
			$out .= '<span class="lph-apercu__rebours">' . esc_html( $rebours ) . '</span>';
		}
		$out .= '<p class="lph-apercu__titre"><a class="lph-lien-etendu" href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['titre'] ) . '</a></p>';
		$out .= lephare_evts_meta( $d, array( 'date', 'heure', 'lieu' ) );
		$out .= '</div>';
		$out .= '<div class="lph-apercu__pied">';
		$out .= '<span class="lph-apercu__total"><strong>' . $total . '</strong> ' . ( $total > 1 ? 'événements à venir' : 'événement à venir' ) . '</span>';
		$out .= '<span class="lph-apercu__suite" aria-hidden="true">Voir <span class="lph-fleche">→</span></span>';
		$out .= '</div>';
	} else {
		$out .= '<p class="lph-apercu__vide">Aucun événement n’est programmé pour le moment.</p>';
	}

	return $out . '</div>';
}

/**
 * Carte de la grille. Toute la carte est cliquable via un seul lien (celui du titre).
 *
 * @param WP_Post $event  Événement.
 * @param int     $rang   Position dans la liste (décalage de l'animation).
 * @param string  $niveau Balise du titre (h3 par défaut, h4 sous un titre h3 comme « À suivre »).
 * @param bool    $passe  Événement passé (carte plus discrète, « Il y a N jours », bouton à contour).
 * @return string
 */
function lephare_evts_carte( $event, $rang = 0, $niveau = 'h3', $passe = false ) {
	$d      = lephare_evts_donnees( $event );
	$niveau = in_array( $niveau, array( 'h2', 'h3', 'h4' ), true ) ? $niveau : 'h3';

	$out  = '<li class="lph-carte' . ( $passe ? ' lph-carte--passe' : '' ) . '" data-lph-reveal data-lph-rang="' . (int) $rang . '"><article class="lph-carte__inner" aria-labelledby="' . esc_attr( $d['titre_id'] ) . '">';
	$out .= '<div class="lph-carte__media">' . lephare_evts_image( $d['id'], 'medium_large', '(max-width: 767px) 100vw, 380px' ) . lephare_evts_badge( $d['debut'] );
	if ( $passe ) {
		$out .= '<span class="lph-carte__statut">Terminé</span>';
	}
	$out .= '</div>';
	$out .= '<div class="lph-carte__contenu">';
	$out .= '<' . $niveau . ' class="lph-carte__titre" id="' . esc_attr( $d['titre_id'] ) . '"><a class="lph-lien-etendu" href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['titre'] ) . '</a></' . $niveau . '>';
	$out .= lephare_evts_meta( $d, $passe ? array( 'date', 'lieu' ) : array( 'date-sr', 'heure', 'lieu' ) );
	if ( $d['extrait'] ) {
		$out .= '<p class="lph-carte__extrait">' . esc_html( $d['extrait'] ) . '</p>';
	}
	$out .= '</div>';
	// Pied de carte : compte à rebours (ou « Il y a N jours ») + bouton visuel (toute la carte est déjà le lien).
	$out .= '<div class="lph-carte__pied">';
	$out .= '<span class="lph-carte__rebours">' . esc_html( $passe ? lephare_evts_depuis( $d['debut'] ) : lephare_evts_rebours( $d['debut'] ) ) . '</span>';
	$out .= '<span class="lph-carte__suite' . ( $passe ? ' lph-carte__suite--contour' : '' ) . '" aria-hidden="true">' . ( $passe ? 'Revoir' : 'Voir les détails' ) . ' <span class="lph-fleche">→</span></span>';
	$out .= '</div></article></li>';

	return $out;
}

/**
 * Élément de la liste « À suivre » : bloc date, titre, heure et lieu. Un seul lien, étendu.
 * Le titre est un h4 : la liste est placée sous un titre h3 « À suivre ».
 *
 * @param WP_Post $event Événement.
 * @param int     $rang  Position dans la liste.
 * @return string
 */
function lephare_evts_mini( $event, $rang = 0 ) {
	$d    = lephare_evts_donnees( $event );
	$mois = array( 1 => 'JANV', 'FÉVR', 'MARS', 'AVR', 'MAI', 'JUIN', 'JUIL', 'AOÛT', 'SEPT', 'OCT', 'NOV', 'DÉC' );

	$out  = '<li class="lph-mini__item" data-lph-reveal data-lph-rang="' . (int) $rang . '"><article class="lph-mini__inner" aria-labelledby="' . esc_attr( $d['titre_id'] ) . '">';
	if ( $d['debut'] ) {
		$out .= '<span class="lph-mini__date" aria-hidden="true"><span class="lph-mini__jour">' . esc_html( $d['debut']->format( 'j' ) ) . '</span><span class="lph-mini__mois">' . esc_html( $mois[ (int) $d['debut']->format( 'n' ) ] ) . '</span></span>';
	}
	$out .= '<div class="lph-mini__contenu">';
	$out .= '<h4 class="lph-mini__titre" id="' . esc_attr( $d['titre_id'] ) . '"><a class="lph-lien-etendu" href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['titre'] ) . '</a></h4>';
	$out .= lephare_evts_meta( $d, array( 'date-sr', 'heure', 'lieu' ) );
	$out .= '</div>';
	$out .= '<span class="lph-mini__fleche lph-fleche" aria-hidden="true">→</span>';
	$out .= '</article></li>';

	return $out;
}

/**
 * Ligne compacte (événements passés). Un seul lien, étendu à toute la ligne.
 *
 * @param WP_Post $event  Événement.
 * @param bool    $passes Événement passé ?
 * @param int     $rang   Position dans la liste (décalage de l'animation).
 * @return string
 */
function lephare_evts_ligne( $event, $passes, $rang = 0 ) {
	$d = lephare_evts_donnees( $event );

	$out  = '<li class="lph-ligne' . ( $passes ? ' lph-ligne--passe' : '' ) . '" data-lph-reveal data-lph-rang="' . (int) $rang . '"><article class="lph-ligne__inner" aria-labelledby="' . esc_attr( $d['titre_id'] ) . '">';
	$out .= '<div class="lph-ligne__media">' . lephare_evts_image( $d['id'], 'thumbnail', '120px' ) . '</div>';
	$out .= '<div class="lph-ligne__contenu">';
	if ( $d['jour_complet'] ) {
		$out .= '<p class="lph-ligne__date"><time datetime="' . esc_attr( $d['datetime'] ) . '">' . esc_html( $d['jour_complet'] ) . '</time></p>';
	}
	$out .= '<h3 class="lph-ligne__titre" id="' . esc_attr( $d['titre_id'] ) . '"><a class="lph-lien-etendu" href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['titre'] ) . '</a></h3>';
	$out .= lephare_evts_meta( $d, array( 'lieu' ) );
	$out .= '</div>';
	$out .= '<span class="lph-ligne__suite" aria-hidden="true">Voir <span class="lph-fleche">→</span></span>';
	$out .= '</article></li>';

	return $out;
}

/**
 * État vide.
 *
 * @param string $message Message.
 * @param bool   $petit   Version compacte (colonne latérale).
 * @return string
 */
function lephare_evts_vide( $message, $petit = false ) {
	return '<div class="lph-vide' . ( $petit ? ' lph-vide--petit' : '' ) . '" data-lph-reveal>' . lephare_evts_icone( 'calendrier' )
		. '<p class="lph-vide__texte">' . esc_html( $message ) . '</p>'
		. '<p class="lph-vide__lien">Une question ? <a href="' . esc_url( home_url( '/contact/' ) ) . '">Contactez-nous</a></p>'
		. '</div>';
}

/**
 * Icônes SVG décoratives (masquées aux lecteurs d'écran).
 *
 * @param string $nom horloge | repere | calendrier.
 * @return string
 */
function lephare_evts_icone( $nom ) {
	$chemins = array(
		'horloge'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'repere'     => '<path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'calendrier' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
		'telecharger' => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
	);
	if ( ! isset( $chemins[ $nom ] ) ) {
		return '';
	}
	return '<svg class="lph-icone" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $chemins[ $nom ] . '</svg>';
}

/**
 * Convertit une date TEC ("Y-m-d H:i:s", heure locale de l'événement).
 *
 * @param string $value Date.
 * @return DateTimeImmutable|null
 */
function lephare_evts_date( $value ) {
	if ( ! $value ) {
		return null;
	}
	$d = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value );
	return $d ?: null;
}

/**
 * « dimanche 22 novembre 2026 », en français quelle que soit la langue du site.
 *
 * @param DateTimeImmutable $d Date.
 * @return string
 */
function lephare_evts_jour( DateTimeImmutable $d ) {
	if ( class_exists( 'IntlDateFormatter' ) ) {
		$f = new IntlDateFormatter( 'fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, $d->getTimezone(), IntlDateFormatter::GREGORIAN, 'EEEE d MMMM y' );
		$s = $f->format( $d );
		if ( $s ) {
			return $s;
		}
	}
	$jours = array( 'dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi' );
	$mois  = array( 1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' );
	return $jours[ (int) $d->format( 'w' ) ] . ' ' . $d->format( 'j' ) . ' ' . $mois[ (int) $d->format( 'n' ) ] . ' ' . $d->format( 'Y' );
}

/**
 * Compte à rebours : « Aujourd'hui », « Demain », « Dans 17 jours ».
 *
 * @param DateTimeImmutable|null $debut Début.
 * @return string
 */
function lephare_evts_rebours( $debut ) {
	if ( ! $debut ) {
		return '';
	}
	$aujourdhui = DateTimeImmutable::createFromFormat( 'Y-m-d', current_time( 'Y-m-d' ) );
	if ( ! $aujourdhui ) {
		return '';
	}
	$jours = (int) $aujourdhui->setTime( 0, 0 )->diff( $debut->setTime( 0, 0 ) )->format( '%r%a' );
	if ( $jours < 0 ) {
		return 'En cours';
	}
	if ( 0 === $jours ) {
		return 'Aujourd’hui';
	}
	if ( 1 === $jours ) {
		return 'Demain';
	}
	return 'Dans ' . $jours . ' jours';
}

/**
 * Temps écoulé depuis un événement passé : « Hier », « Il y a 18 jours », « Il y a 3 mois ».
 *
 * @param DateTimeImmutable|null $debut Début.
 * @return string
 */
function lephare_evts_depuis( $debut ) {
	if ( ! $debut ) {
		return 'Terminé';
	}
	$aujourdhui = DateTimeImmutable::createFromFormat( 'Y-m-d', current_time( 'Y-m-d' ) );
	if ( ! $aujourdhui ) {
		return 'Terminé';
	}
	$jours = (int) $debut->setTime( 0, 0 )->diff( $aujourdhui->setTime( 0, 0 ) )->format( '%r%a' );
	if ( $jours <= 0 ) {
		return 'Aujourd’hui';
	}
	if ( 1 === $jours ) {
		return 'Hier';
	}
	if ( $jours < 60 ) {
		return 'Il y a ' . $jours . ' jours';
	}
	$mois = (int) floor( $jours / 30 );
	return $mois < 12 ? 'Il y a ' . $mois . ' mois' : 'Il y a plus d’un an';
}

/**
 * « 10 h 00 ».
 *
 * @param DateTimeImmutable $d Date.
 * @return string
 */
function lephare_evts_heure( DateTimeImmutable $d ) {
	return $d->format( 'G' ) . ' h ' . $d->format( 'i' );
}

/**
 * Majuscule initiale (UTF-8).
 *
 * @param string $s Texte.
 * @return string
 */
function lephare_evts_majuscule( $s ) {
	return mb_strtoupper( mb_substr( $s, 0, 1 ) ) . mb_substr( $s, 1 );
}
