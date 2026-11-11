<?php
/**
 * Plugin Name:       LePhare – Prédications & Ressources
 * Description:       Fiche « Prédication » sur les articles de la catégorie Prédications (slug sermons), shortcode [lephare_predications] pour la page /predications-ressources/ et affichage enrichi de chaque prédication (vidéo, audio, PDF, prédications liées).
 * Version:           1.3.2
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            FREEDEV – LePhare/Goma
 * License:           GPL-2.0-or-later
 * Text Domain:       lephare-predications
 *
 * Structure validée avec Aaron (#13) : les prédications sont des Articles WordPress de la catégorie
 * « Prédications » (slug sermons) ; le Type est une sous-catégorie (Prédication, Enseignement,
 * Étude biblique, Document). Aucun type de contenu personnalisé. Orateur, date, série, référence
 * biblique et médias sont des métadonnées de l'article (fiche « Prédication »).
 *
 * Widget Elementor « Prédications & ressources » (catégorie LePhare), ou shortcode : [lephare_predications par_page="9"]
 * Filtres par l'URL (fonctionnent sans JavaScript) : ?lph_type=… &lph_orateur=… &lph_serie=… &lph_q=… &lph_page=2
 */

defined( 'ABSPATH' ) || exit;

const LEPHARE_PRED_VERSION   = '1.3.2';
const LEPHARE_PRED_CATEGORIE = 'sermons';

/* =========================================================================
 * 1. Outils
 * ====================================================================== */

/**
 * ID de la catégorie « Prédications » (slug sermons), ou 0.
 *
 * @return int
 */
function lephare_pred_categorie_id() {
	$cat = get_category_by_slug( LEPHARE_PRED_CATEGORIE );
	return $cat ? (int) $cat->term_id : 0;
}

/**
 * IDs de la catégorie « Prédications » et de ses sous-catégories (Types).
 *
 * @return int[]
 */
function lephare_pred_categories_ids() {
	$parent = lephare_pred_categorie_id();
	if ( ! $parent ) {
		return array();
	}
	$enfants = get_term_children( $parent, 'category' );
	return array_merge( array( $parent ), is_wp_error( $enfants ) ? array() : array_map( 'intval', $enfants ) );
}

/**
 * Types = sous-catégories de « Prédications » : [ slug => nom ].
 *
 * @return array
 */
function lephare_pred_types() {
	$parent = lephare_pred_categorie_id();
	if ( ! $parent ) {
		return array();
	}
	$types = array();
	foreach ( get_terms( array( 'taxonomy' => 'category', 'parent' => $parent, 'hide_empty' => false, 'orderby' => 'term_id' ) ) as $t ) {
		$types[ $t->slug ] = $t->name;
	}
	return $types;
}

/**
 * Cet article est-il une prédication (catégorie Prédications ou une de ses sous-catégories) ?
 *
 * @param int $post_id Article.
 * @return bool
 */
function lephare_pred_est_predication( $post_id ) {
	$ids = lephare_pred_categories_ids();
	return $ids && 'post' === get_post_type( $post_id ) && has_category( $ids, $post_id );
}

/**
 * Type (sous-catégorie) d'une prédication : [ slug, nom ] ou null.
 *
 * @param int $post_id Article.
 * @return array|null
 */
function lephare_pred_type( $post_id ) {
	$types = lephare_pred_types();
	foreach ( get_the_category( $post_id ) as $c ) {
		if ( isset( $types[ $c->slug ] ) ) {
			return array( $c->slug, $c->name );
		}
	}
	return null;
}

/**
 * Champs de la fiche : clé de métadonnée => libellé.
 *
 * @return array
 */
function lephare_pred_champs() {
	return array(
		'_lph_orateur'   => 'Orateur',
		'_lph_date'      => 'Date de la prédication',
		'_lph_serie'     => 'Série',
		'_lph_reference' => 'Référence biblique',
		'_lph_video'     => 'Vidéo (lien YouTube ou vidéo de la Médiathèque)',
		'_lph_audio'     => 'Audio (Médiathèque ou URL)',
		'_lph_pdf'       => 'PDF à télécharger',
	);
}

/**
 * Données d'une prédication prêtes à afficher.
 *
 * @param int $post_id Article.
 * @return array
 */
function lephare_pred_donnees( $post_id ) {
	$date_brute = (string) get_post_meta( $post_id, '_lph_date', true );
	$date       = $date_brute ? DateTimeImmutable::createFromFormat( '!Y-m-d', $date_brute ) : false;
	$pdf_id     = (int) get_post_meta( $post_id, '_lph_pdf', true );
	$type       = lephare_pred_type( $post_id );

	return array(
		'id'        => $post_id,
		'titre'     => get_the_title( $post_id ),
		'url'       => get_permalink( $post_id ),
		'orateur'   => (string) get_post_meta( $post_id, '_lph_orateur', true ),
		'serie'     => (string) get_post_meta( $post_id, '_lph_serie', true ),
		'reference' => (string) get_post_meta( $post_id, '_lph_reference', true ),
		'video'     => (string) get_post_meta( $post_id, '_lph_video', true ),
		'audio'     => (string) get_post_meta( $post_id, '_lph_audio', true ),
		'pdf_id'    => ( $pdf_id && 'application/pdf' === get_post_mime_type( $pdf_id ) ) ? $pdf_id : 0,
		'date'      => $date ?: null,
		'date_iso'  => $date ? $date->format( 'Y-m-d' ) : get_the_date( 'Y-m-d', $post_id ),
		'date_txt'  => $date ? lephare_pred_date_fr( $date ) : lephare_pred_date_fr( new DateTimeImmutable( get_the_date( 'Y-m-d', $post_id ) ) ),
		'date_obj'  => $date ?: new DateTimeImmutable( get_the_date( 'Y-m-d', $post_id ) ),
		'type'      => $type,
		'extrait'   => wp_trim_words( has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_strip_all_tags( preg_replace( '~</p>|<br\s*/?>~i', ' ', strip_shortcodes( get_post_field( 'post_content', $post_id ) ) ) ), 24, '…' ),
	);
}

/**
 * « 18 octobre 2026 » (en français, quelle que soit la langue du site).
 *
 * @param DateTimeImmutable $d Date.
 * @return string
 */
function lephare_pred_date_fr( DateTimeImmutable $d ) {
	$mois = array( 1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' );
	return $d->format( 'j' ) . ' ' . $mois[ (int) $d->format( 'n' ) ] . ' ' . $d->format( 'Y' );
}

/**
 * ID d'une vidéo YouTube à partir d'un lien (watch, youtu.be, embed, shorts, live), ou ''.
 *
 * @param string $url Lien.
 * @return string
 */
function lephare_pred_youtube_id( $url ) {
	if ( preg_match( '~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * L'URL pointe-t-elle vers un fichier de ce site (Médiathèque) ?
 *
 * @param string $url URL.
 * @return bool
 */
function lephare_pred_url_locale( $url ) {
	$base = wp_get_upload_dir()['baseurl'];
	return 0 === strpos( set_url_scheme( $url, 'http' ), set_url_scheme( $base, 'http' ) );
}

/**
 * Icônes SVG décoratives.
 *
 * @param string $nom Nom.
 * @return string
 */
function lephare_pred_icone( $nom ) {
	$chemins = array(
		'orateur'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>',
		'calendrier' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
		'livre'      => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16"/>',
		'serie'      => '<rect x="3" y="4" width="18" height="5" rx="1.5"/><rect x="3" y="11" width="18" height="5" rx="1.5"/><path d="M6 20h12"/>',
		'video'      => '<rect x="3" y="5" width="13" height="14" rx="2.5"/><path d="M16 10l5-3v10l-5-3z"/>',
		'audio'      => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="5" height="7" rx="2"/><rect x="16" y="14" width="5" height="7" rx="2"/>',
		'pdf'        => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M12 11v6M9 14l3 3 3-3"/>',
		'recherche'  => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
		'lecture'    => '<path d="M8 5.5v13l10.5-6.5z" fill="currentColor" stroke="none"/>',
		'message'    => '<path d="M4 5h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H9l-5 4V6a1 1 0 0 1 1-1z"/>',
	);
	return isset( $chemins[ $nom ] ) ? '<svg class="lpp-icone" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $chemins[ $nom ] . '</svg>' : '';
}

/* =========================================================================
 * 2. Métadonnées (enregistrées et nettoyées par WordPress)
 * ====================================================================== */

add_action(
	'init',
	static function () {
		$auth = static function ( $allowed, $meta_key, $post_id ) {
			return current_user_can( 'edit_post', $post_id );
		};
		foreach ( array( '_lph_orateur', '_lph_serie', '_lph_reference' ) as $cle ) {
			register_post_meta( 'post', $cle, array( 'type' => 'string', 'single' => true, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => $auth ) );
		}
		register_post_meta( 'post', '_lph_date', array( 'type' => 'string', 'single' => true, 'sanitize_callback' => 'lephare_pred_nettoyer_date', 'auth_callback' => $auth ) );
		register_post_meta( 'post', '_lph_video', array( 'type' => 'string', 'single' => true, 'sanitize_callback' => 'lephare_pred_nettoyer_video', 'auth_callback' => $auth ) );
		register_post_meta( 'post', '_lph_audio', array( 'type' => 'string', 'single' => true, 'sanitize_callback' => 'lephare_pred_nettoyer_audio', 'auth_callback' => $auth ) );
		register_post_meta( 'post', '_lph_pdf', array( 'type' => 'integer', 'single' => true, 'sanitize_callback' => 'lephare_pred_nettoyer_pdf', 'auth_callback' => $auth ) );
	}
);

/**
 * Date AAAA-MM-JJ valide, sinon ''.
 *
 * @param string $v Valeur.
 * @return string
 */
function lephare_pred_nettoyer_date( $v ) {
	$v = sanitize_text_field( (string) $v );
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $v );
	return ( $d && $d->format( 'Y-m-d' ) === $v ) ? $v : '';
}

/**
 * Vidéo : lien YouTube valide, ou vidéo de la Médiathèque du site. Sinon ''.
 *
 * @param string $v URL.
 * @return string
 */
function lephare_pred_nettoyer_video( $v ) {
	$v = esc_url_raw( trim( (string) $v ), array( 'http', 'https' ) );
	if ( '' === $v ) {
		return '';
	}
	if ( lephare_pred_youtube_id( $v ) ) {
		return $v;
	}
	$ext = strtolower( pathinfo( (string) wp_parse_url( $v, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
	return ( lephare_pred_url_locale( $v ) && in_array( $ext, array( 'mp4', 'm4v', 'webm', 'ogv', 'mov' ), true ) ) ? $v : '';
}

/**
 * Audio : URL http(s) d'un fichier audio (mp3, m4a, ogg, wav). Sinon ''.
 *
 * @param string $v URL.
 * @return string
 */
function lephare_pred_nettoyer_audio( $v ) {
	$v = esc_url_raw( trim( (string) $v ), array( 'http', 'https' ) );
	if ( '' === $v ) {
		return '';
	}
	$ext = strtolower( pathinfo( (string) wp_parse_url( $v, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
	return in_array( $ext, array( 'mp3', 'm4a', 'ogg', 'oga', 'wav' ), true ) ? $v : '';
}

/**
 * PDF : ID d'un fichier PDF de la Médiathèque. Sinon 0.
 *
 * @param mixed $v ID.
 * @return int
 */
function lephare_pred_nettoyer_pdf( $v ) {
	$id = absint( $v );
	return ( $id && 'attachment' === get_post_type( $id ) && 'application/pdf' === get_post_mime_type( $id ) ) ? $id : 0;
}

/* =========================================================================
 * 3. Administration : boîte « Fiche prédication »
 * ====================================================================== */

add_action(
	'add_meta_boxes_post',
	static function () {
		add_meta_box( 'lph-fiche-predication', 'Fiche prédication', 'lephare_pred_boite', 'post', 'normal', 'high' );
	}
);

/**
 * Contenu de la boîte.
 *
 * @param WP_Post $post Article.
 */
function lephare_pred_boite( $post ) {
	wp_nonce_field( 'lph_fiche_predication', 'lph_fiche_nonce' );
	$v = array();
	foreach ( array_keys( lephare_pred_champs() ) as $cle ) {
		$v[ $cle ] = get_post_meta( $post->ID, $cle, true );
	}
	$pdf_id  = (int) $v['_lph_pdf'];
	$pdf_nom = $pdf_id ? basename( (string) get_attached_file( $pdf_id ) ) : '';

	// Valeurs déjà utilisées (suggestions).
	$series   = lephare_pred_valeurs( '_lph_serie' );
	$orateurs = lephare_pred_valeurs( '_lph_orateur' );
	?>
	<div class="lph-fiche">
		<p class="lph-fiche__aide">Remplissez cette fiche pour les articles de la catégorie <strong>Prédications</strong>. Choisissez aussi le <strong>Type</strong> dans les catégories (Prédication, Enseignement, Étude biblique ou Document).</p>
		<div class="lph-fiche__grille">
			<p>
				<label for="lph_orateur">Orateur</label>
				<input type="text" id="lph_orateur" name="lph_orateur" value="<?php echo esc_attr( $v['_lph_orateur'] ); ?>" list="lph_orateurs" class="widefat" autocomplete="off">
				<datalist id="lph_orateurs"><?php foreach ( $orateurs as $o ) { echo '<option value="' . esc_attr( $o ) . '">'; } ?></datalist>
			</p>
			<p>
				<label for="lph_date">Date de la prédication</label>
				<input type="date" id="lph_date" name="lph_date" value="<?php echo esc_attr( $v['_lph_date'] ); ?>" class="widefat">
			</p>
			<p>
				<label for="lph_serie">Série</label>
				<input type="text" id="lph_serie" name="lph_serie" value="<?php echo esc_attr( $v['_lph_serie'] ); ?>" list="lph_series" class="widefat" autocomplete="off">
				<datalist id="lph_series"><?php foreach ( $series as $s ) { echo '<option value="' . esc_attr( $s ) . '">'; } ?></datalist>
			</p>
			<p>
				<label for="lph_reference">Référence biblique</label>
				<input type="text" id="lph_reference" name="lph_reference" value="<?php echo esc_attr( $v['_lph_reference'] ); ?>" placeholder="ex. Jean 3.16" class="widefat">
			</p>
		</div>

		<p class="lph-fiche__media">
			<label for="lph_video">Vidéo : lien YouTube <span class="description">(ex. https://www.youtube.com/watch?v=…)</span> ou vidéo de la Médiathèque</label>
			<span class="lph-fiche__ligne">
				<input type="url" id="lph_video" name="lph_video" value="<?php echo esc_attr( $v['_lph_video'] ); ?>" class="widefat" placeholder="https://www.youtube.com/watch?v=…" aria-describedby="lph_video_aide">
				<button type="button" class="button lph-choisir" data-cible="lph_video" data-type="video" data-titre="Choisir une vidéo">Médiathèque…</button>
			</span>
			<span id="lph_video_aide" class="description">Liens acceptés : YouTube, ou fichier vidéo (mp4, webm, mov) de la Médiathèque.</span>
		</p>

		<p class="lph-fiche__media">
			<label for="lph_audio">Audio <span class="description">(fichier mp3, m4a, ogg ou wav)</span></label>
			<span class="lph-fiche__ligne">
				<input type="url" id="lph_audio" name="lph_audio" value="<?php echo esc_attr( $v['_lph_audio'] ); ?>" class="widefat" placeholder="https://…/fichier.mp3">
				<button type="button" class="button lph-choisir" data-cible="lph_audio" data-type="audio" data-titre="Choisir un fichier audio">Médiathèque…</button>
			</span>
		</p>

		<p class="lph-fiche__media">
			<label for="lph_pdf_nom">PDF à télécharger</label>
			<span class="lph-fiche__ligne">
				<input type="hidden" id="lph_pdf" name="lph_pdf" value="<?php echo esc_attr( $pdf_id ?: '' ); ?>">
				<input type="text" id="lph_pdf_nom" value="<?php echo esc_attr( $pdf_nom ); ?>" class="widefat" readonly placeholder="Aucun PDF choisi">
				<button type="button" class="button lph-choisir" data-cible="lph_pdf" data-type="application/pdf" data-titre="Choisir un PDF" data-nom="lph_pdf_nom">Choisir un PDF…</button>
				<button type="button" class="button-link lph-retirer" data-cible="lph_pdf" data-nom="lph_pdf_nom">Retirer</button>
			</span>
		</p>
	</div>
	<?php
}

/**
 * Valeurs distinctes déjà saisies pour une métadonnée, sur les prédications publiées ou en brouillon.
 *
 * @param string $cle Clé.
 * @return string[]
 */
function lephare_pred_valeurs( $cle ) {
	$ids = lephare_pred_categories_ids();
	if ( ! $ids ) {
		return array();
	}
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'category__in'   => $ids,
			'fields'         => 'ids',
			'posts_per_page' => 500,
			'no_found_rows'  => true,
		)
	);
	$valeurs = array();
	foreach ( $posts as $id ) {
		$val = trim( (string) get_post_meta( $id, $cle, true ) );
		if ( '' !== $val ) {
			$valeurs[ $val ] = $val;
		}
	}
	natcasesort( $valeurs );
	return array_values( $valeurs );
}

/**
 * Enregistrement : nonce, droits, autosauvegarde, nettoyage.
 */
add_action(
	'save_post_post',
	static function ( $post_id ) {
		if ( ! isset( $_POST['lph_fiche_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lph_fiche_nonce'] ) ), 'lph_fiche_predication' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$valeurs = array(
			'_lph_orateur'   => sanitize_text_field( wp_unslash( $_POST['lph_orateur'] ?? '' ) ),
			'_lph_date'      => lephare_pred_nettoyer_date( wp_unslash( $_POST['lph_date'] ?? '' ) ),
			'_lph_serie'     => sanitize_text_field( wp_unslash( $_POST['lph_serie'] ?? '' ) ),
			'_lph_reference' => sanitize_text_field( wp_unslash( $_POST['lph_reference'] ?? '' ) ),
			'_lph_video'     => lephare_pred_nettoyer_video( wp_unslash( $_POST['lph_video'] ?? '' ) ),
			'_lph_audio'     => lephare_pred_nettoyer_audio( wp_unslash( $_POST['lph_audio'] ?? '' ) ),
			'_lph_pdf'       => lephare_pred_nettoyer_pdf( wp_unslash( $_POST['lph_pdf'] ?? 0 ) ),
		);
		foreach ( $valeurs as $cle => $val ) {
			if ( '' === $val || 0 === $val ) {
				delete_post_meta( $post_id, $cle );
			} else {
				update_post_meta( $post_id, $cle, $val );
			}
		}
	}
);

/**
 * Scripts et styles de l'écran d'édition des articles.
 */
add_action(
	'admin_enqueue_scripts',
	static function ( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'post' !== get_current_screen()->post_type ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'lephare-predications-admin', plugins_url( 'assets/lephare-predications-admin.js', __FILE__ ), array( 'jquery' ), LEPHARE_PRED_VERSION, true );
		wp_localize_script(
			'lephare-predications-admin',
			'lephPred',
			array(
				'categories' => lephare_pred_categories_ids(),
				'choisir'    => 'Utiliser ce fichier',
			)
		);
		wp_add_inline_style(
			'wp-admin',
			'.lph-fiche__aide{margin:0 0 12px;color:#50575e}.lph-fiche__grille{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:4px 16px}.lph-fiche label{display:block;font-weight:600;margin-bottom:4px}.lph-fiche__ligne{display:flex;gap:8px;align-items:center}.lph-fiche__ligne .widefat{flex:1}.lph-fiche__media>.description{display:block;margin-top:4px}.lph-fiche label .description{font-weight:400}'
		);
	}
);

/* =========================================================================
 * 4. Front : styles et script
 * ====================================================================== */

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_register_style( 'lephare-predications', plugins_url( 'assets/lephare-predications.css', __FILE__ ), array(), LEPHARE_PRED_VERSION );
		wp_register_script( 'lephare-predications', plugins_url( 'assets/lephare-predications.js', __FILE__ ), array(), LEPHARE_PRED_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );

		if ( is_singular() ) {
			$id      = get_queried_object_id();
			$contenu = (string) get_post_field( 'post_content', $id ) . (string) get_post_meta( $id, '_elementor_data', true );
			if ( false !== strpos( $contenu, 'lephare_predication' ) || false !== strpos( $contenu, 'lephare-predication' ) || lephare_pred_est_predication( $id ) ) {
				wp_enqueue_style( 'lephare-predications' );
				wp_enqueue_script( 'lephare-predications' );
			}
		}
	}
);

/* =========================================================================
 * 5. Shortcode [lephare_predications]
 * ====================================================================== */

add_shortcode( 'lephare_predications', 'lephare_pred_shortcode' );

/**
 * Lit les filtres de l'URL (sans JavaScript, via GET).
 *
 * @return array
 */
function lephare_pred_filtres() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtres publics en lecture seule.
	$types = lephare_pred_types();
	$type  = isset( $_GET['lph_type'] ) ? sanitize_title( wp_unslash( $_GET['lph_type'] ) ) : '';
	return array(
		'type'    => isset( $types[ $type ] ) ? $type : '',
		'orateur' => isset( $_GET['lph_orateur'] ) ? sanitize_text_field( wp_unslash( $_GET['lph_orateur'] ) ) : '',
		'serie'   => isset( $_GET['lph_serie'] ) ? sanitize_text_field( wp_unslash( $_GET['lph_serie'] ) ) : '',
		'q'       => isset( $_GET['lph_q'] ) ? sanitize_text_field( wp_unslash( $_GET['lph_q'] ) ) : '',
		'page'    => isset( $_GET['lph_page'] ) ? max( 1, absint( $_GET['lph_page'] ) ) : 1,
	);
	// phpcs:enable
}

/**
 * Requête des prédications publiées, triées par date de prédication puis date de publication.
 *
 * @param array $f      Filtres.
 * @param int   $nombre Par page.
 * @param int[] $exclure IDs à exclure.
 * @return WP_Query
 */
function lephare_pred_requete( $f, $nombre, $exclure = array() ) {
	$meta = array(
		'relation' => 'AND',
		array(
			'relation' => 'OR',
			'lph_date' => array( 'key' => '_lph_date', 'compare' => 'EXISTS' ),
			array( 'key' => '_lph_date', 'compare' => 'NOT EXISTS' ),
		),
	);
	if ( '' !== $f['orateur'] ) {
		$meta[] = array( 'key' => '_lph_orateur', 'value' => $f['orateur'] );
	}
	if ( '' !== $f['serie'] ) {
		$meta[] = array( 'key' => '_lph_serie', 'value' => $f['serie'] );
	}
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $nombre,
		'paged'               => $f['page'],
		'ignore_sticky_posts' => true,
		'meta_query'          => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'orderby'             => array( 'lph_date' => 'DESC', 'date' => 'DESC' ),
		'post__not_in'        => $exclure,
	);
	if ( '' !== $f['type'] ) {
		$args['category_name'] = $f['type'];
	} else {
		$args['cat'] = lephare_pred_categorie_id();
	}
	if ( '' !== $f['q'] ) {
		// Recherche maison (filtre posts_where ci-dessous) : titre, texte, extrait,
		// mais aussi orateur, série et référence biblique.
		$args['lph_recherche'] = $f['q'];
	}
	return new WP_Query( $args );
}

/**
 * Recherche dans le titre, le texte, l'extrait ET la fiche (orateur, série, référence biblique).
 * L'expression saisie est cherchée telle quelle (ex. « Jean 3 », « Série B »). Lecture seule, valeurs préparées.
 */
add_filter(
	'posts_where',
	static function ( $where, $query ) {
		$terme = trim( preg_replace( '/\s+/u', ' ', (string) $query->get( 'lph_recherche' ) ) );
		if ( '' === $terme ) {
			return $where;
		}
		global $wpdb;
		foreach ( array( mb_substr( $terme, 0, 100 ) ) as $mot ) {
			$like   = '%' . $wpdb->esc_like( $mot ) . '%';
			$where .= $wpdb->prepare(
				" AND ( {$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s
					OR EXISTS ( SELECT 1 FROM {$wpdb->postmeta} lph_m WHERE lph_m.post_id = {$wpdb->posts}.ID
						AND lph_m.meta_key IN ( '_lph_orateur', '_lph_serie', '_lph_reference' ) AND lph_m.meta_value LIKE %s ) )",
				$like,
				$like,
				$like,
				$like
			);
		}
		return $where;
	},
	10,
	2
);

/**
 * Options d'affichage de la liste (réglables dans le widget Elementor ou en attributs du shortcode).
 * Booléens : true / false. Textes : affichés tels quels (échappés).
 *
 * @return array
 */
function lephare_pred_options_defaut() {
	return array(
		'par_page'         => 9,
		'type_fixe'        => '',      // slug d'un Type : n'afficher que ce Type (ex. enseignement).
		'une'              => true,    // Prédication à la une.
		'filtres'          => true,    // Barre de filtres (formulaire).
		'recherche'        => true,
		'filtre_type'      => true,
		'filtre_orateur'   => true,
		'filtre_serie'     => true,
		'compteur'         => true,    // « 3 ressources disponibles ».
		'pagination'       => true,
		'orateur'          => true,    // Ligne orateur sur les cartes.
		'reference'        => true,    // Ligne référence biblique sur les cartes.
		'pastilles'        => true,    // Pastilles Vidéo / Audio / PDF.
		'extrait'          => true,    // Extrait sur la carte à la une.
		'niveau_titre'     => 'h3',
		'style'            => 'cartes',  // cartes | lignes (liste façon « épisodes »).
		'style_type'       => 'liste',   // Filtre Type : liste déroulante | onglets (pastilles).
		'txt_etiquette'    => 'À la une',
		'txt_bouton_une'   => 'Voir la prédication',
		'txt_bouton_carte' => 'Voir',
		'txt_recherche'    => 'Rechercher',
		'txt_placeholder'  => 'Titre, référence, mot-clé…',
		'txt_filtrer'      => 'Filtrer',
		'txt_reset'        => 'Réinitialiser',
		'txt_aucun'        => 'Aucune prédication ne correspond à votre recherche.',
		'txt_vide'         => 'Aucune prédication n’est encore publiée.',
	);
}

/**
 * Option en cours (pendant le rendu de la liste) ; valeurs par défaut ailleurs (page d'une prédication).
 *
 * @param string     $cle    Clé.
 * @param array|null $regler Nouvelles options (usage interne), ou null pour lire.
 * @return mixed
 */
function lephare_pred_opt( $cle, $regler = null ) {
	static $options = null;
	if ( 'regler' === $cle ) {
		$options = $regler;
		return null;
	}
	$o = $options ?? lephare_pred_options_defaut();
	return $o[ $cle ] ?? lephare_pred_options_defaut()[ $cle ] ?? null;
}

/**
 * Nettoie des options reçues (widget ou shortcode).
 *
 * @param array $brut Options brutes.
 * @return array
 */
function lephare_pred_options_nettoyer( $brut ) {
	$def = lephare_pred_options_defaut();
	$o   = array();
	foreach ( $def as $cle => $defaut ) {
		$v = $brut[ $cle ] ?? $defaut;
		if ( is_bool( $defaut ) ) {
			$o[ $cle ] = is_bool( $v ) ? $v : in_array( strtolower( (string) $v ), array( '1', 'yes', 'oui', 'true' ), true );
		} elseif ( is_int( $defaut ) ) {
			$o[ $cle ] = max( 1, min( 24, (int) $v ) );
		} else {
			$v         = sanitize_text_field( (string) $v );
			$o[ $cle ] = ( '' === $v && 0 === strpos( $cle, 'txt_' ) ) ? $defaut : $v;
		}
	}
	$o['niveau_titre'] = in_array( $o['niveau_titre'], array( 'h2', 'h3', 'h4' ), true ) ? $o['niveau_titre'] : 'h3';
	$o['style']        = 'lignes' === $o['style'] ? 'lignes' : 'cartes';
	$o['style_type']   = 'onglets' === $o['style_type'] ? 'onglets' : 'liste';
	$o['type_fixe']    = isset( lephare_pred_types()[ $o['type_fixe'] ] ) ? $o['type_fixe'] : '';
	return $o;
}

/**
 * Rendu du shortcode : [lephare_predications par_page="9" une="non" type_fixe="enseignement" …].
 *
 * @param array|string $atts Attributs.
 * @return string
 */
function lephare_pred_shortcode( $atts ) {
	return lephare_pred_rendu( (array) $atts );
}

/**
 * Rendu de la liste (shortcode et widget Elementor).
 *
 * @param array $brut Options brutes (voir lephare_pred_options_defaut()).
 * @return string
 */
function lephare_pred_rendu( $brut = array() ) {
	wp_enqueue_style( 'lephare-predications' );
	wp_enqueue_script( 'lephare-predications' );

	if ( ! lephare_pred_categorie_id() ) {
		return lephare_pred_vide( 'La catégorie « Prédications » n’existe pas encore.' );
	}

	$o = lephare_pred_options_nettoyer( $brut );
	lephare_pred_opt( 'regler', $o );

	$f = lephare_pred_filtres();
	if ( $o['type_fixe'] ) {
		$f['type'] = $o['type_fixe'];
	}
	// Un filtre choisi par le visiteur (le Type imposé par le réglage n'en est pas un).
	$filtre  = ( ( '' !== $f['type'] && ! $o['type_fixe'] ) || '' !== $f['orateur'] || '' !== $f['serie'] || '' !== $f['q'] );
	$html    = '<div class="lpp" id="lph-predications">';
	$exclure = array();

	// Prédication à la une : la plus récente. Affichée sans filtre et en page 1 seulement,
	// mais toujours retirée de la grille sans filtre (sinon la pagination se décalerait).
	if ( $o['une'] && ! $filtre ) {
		$une = lephare_pred_requete( array_merge( $f, array( 'page' => 1 ) ), 1 );
		if ( $une->have_posts() ) {
			$exclure = array( $une->posts[0]->ID );
			if ( 1 === $f['page'] ) {
				$html .= lephare_pred_carte_une( lephare_pred_donnees( $une->posts[0]->ID ) );
			}
		}
	}

	if ( $o['filtres'] ) {
		if ( 'onglets' === $o['style_type'] && $o['filtre_type'] && ! $o['type_fixe'] ) {
			// Barre d'outils : onglets Type + recherche et filtres compacts, dans un même bloc.
			$html .= '<div class="lpp-barre">' . lephare_pred_onglets( $f ) . lephare_pred_formulaire( $f ) . '</div>';
		} else {
			$html .= lephare_pred_formulaire( $f );
		}
	}

	if ( ! $o['pagination'] ) {
		$f['page'] = 1;
	}
	$q     = lephare_pred_requete( $f, $o['par_page'], $exclure );
	$total = (int) $q->found_posts;
	// Le nombre de résultats reste annoncé aux lecteurs d'écran même s'il est masqué à l'écran.
	$html .= '<p class="lpp-resultats' . ( $o['compteur'] ? '' : ' screen-reader-text' ) . '" role="status" aria-live="polite">' . esc_html( lephare_pred_libelle_resultats( $total + count( $exclure ), $filtre, count( $exclure ) ) ) . '</p>';

	if ( ! $q->have_posts() ) {
		if ( $filtre ) {
			$html .= lephare_pred_vide( $o['txt_aucun'], true );
		} elseif ( $f['page'] > 1 ) {
			$html .= lephare_pred_vide( 'Cette page ne contient aucune prédication.', true );
		} elseif ( ! $exclure ) {
			$html .= lephare_pred_vide( $o['txt_vide'] );
		}
	} else {
		$lignes = 'lignes' === $o['style'];
		$html  .= '<ul class="' . ( $lignes ? 'lpp-lignes' : 'lpp-grille' ) . '" role="list">';
		foreach ( $q->posts as $rang => $p ) {
			$d     = lephare_pred_donnees( $p->ID );
			$html .= $lignes ? lephare_pred_ligne( $d, $rang, $o['niveau_titre'] ) : lephare_pred_carte( $d, $rang, $o['niveau_titre'] );
		}
		$html .= '</ul>';
		if ( $o['pagination'] ) {
			$html .= lephare_pred_pagination( $q, $f );
		}
	}
	wp_reset_postdata();
	lephare_pred_opt( 'regler', null );

	return $html . '</div>';
}

/**
 * « 3 ressources » / « 1 résultat pour votre recherche ».
 *
 * @param int  $total  Nombre.
 * @param bool $filtre Filtres actifs ?
 * @param int  $une    Nombre à la une.
 * @return string
 */
function lephare_pred_libelle_resultats( $total, $filtre, $une ) {
	if ( $filtre ) {
		return $total . ' ' . ( $total > 1 ? 'résultats' : 'résultat' ) . ' pour votre recherche';
	}
	return $total . ' ' . ( $total > 1 ? 'ressources disponibles' : 'ressource disponible' );
}

/**
 * Formulaire de filtres (GET : fonctionne sans JavaScript).
 *
 * @param array $f Filtres.
 * @return string
 */
function lephare_pred_formulaire( $f ) {
	$action = get_permalink();
	$types  = lephare_pred_types();
	$out    = '<form class="lpp-filtres" method="get" action="' . esc_url( $action ) . '#lph-predications" role="search" aria-label="Filtrer les prédications et ressources">';

	$fixe   = (string) lephare_pred_opt( 'type_fixe' );
	$champs = 0;

	if ( lephare_pred_opt( 'recherche' ) ) {
		$out .= '<div class="lpp-filtres__champ lpp-filtres__champ--recherche"><label for="lph_q">' . esc_html( lephare_pred_opt( 'txt_recherche' ) ) . '</label>';
		$out .= '<span class="lpp-filtres__recherche">' . lephare_pred_icone( 'recherche' ) . '<input type="search" id="lph_q" name="lph_q" value="' . esc_attr( $f['q'] ) . '" placeholder="' . esc_attr( lephare_pred_opt( 'txt_placeholder' ) ) . '"></span></div>';
		$champs++;
	}
	if ( lephare_pred_opt( 'filtre_type' ) && ! $fixe ) {
		if ( 'onglets' === lephare_pred_opt( 'style_type' ) ) {
			// Le Type est choisi avec les onglets : on le garde quand on envoie le formulaire.
			if ( '' !== $f['type'] ) {
				$out .= '<input type="hidden" name="lph_type" value="' . esc_attr( $f['type'] ) . '">';
			}
		} else {
			// Seulement les Types qui ont au moins une prédication publiée (+ celui déjà choisi).
			$utiles = lephare_pred_types_utilises();
			if ( '' !== $f['type'] && isset( $types[ $f['type'] ] ) ) {
				$utiles[ $f['type'] ] = $types[ $f['type'] ];
			}
			$out .= lephare_pred_select( 'lph_type', 'Type', $utiles, $f['type'], 'Tous les types' );
			$champs++;
		}
	}
	if ( lephare_pred_opt( 'filtre_orateur' ) ) {
		$orateurs = lephare_pred_valeurs( '_lph_orateur' );
		$out     .= lephare_pred_select( 'lph_orateur', 'Orateur', array_combine( $orateurs, $orateurs ) ?: array(), $f['orateur'], 'Tous les orateurs' );
		$champs++;
	}
	if ( lephare_pred_opt( 'filtre_serie' ) ) {
		$series = lephare_pred_valeurs( '_lph_serie' );
		$out   .= lephare_pred_select( 'lph_serie', 'Série', array_combine( $series, $series ) ?: array(), $f['serie'], 'Toutes les séries' );
		$champs++;
	}
	if ( ! $champs ) {
		return '';
	}

	$out .= '<div class="lpp-filtres__actions"><button type="submit" class="lpp-bouton">' . esc_html( lephare_pred_opt( 'txt_filtrer' ) ) . '</button>';
	if ( ( '' !== $f['type'] && ! $fixe ) || '' !== $f['orateur'] || '' !== $f['serie'] || '' !== $f['q'] ) {
		$out .= '<a class="lpp-lien-reset scroll-ignore" href="' . esc_url( $action ) . '#lph-predications">' . esc_html( lephare_pred_opt( 'txt_reset' ) ) . '</a>';
	}
	$out .= '</div></form>';
	// Nombre de champs : la grille du formulaire s'adapte (moins de colonnes s'il y a moins de filtres).
	return str_replace( 'class="lpp-filtres"', 'class="lpp-filtres lpp-filtres--' . $champs . '"', $out );
}

/**
 * Liste déroulante de filtre.
 *
 * @param string $nom     Nom du paramètre.
 * @param string $label   Libellé.
 * @param array  $options valeur => texte.
 * @param string $actuel  Valeur sélectionnée.
 * @param string $tous    Libellé « tous ».
 * @return string
 */
function lephare_pred_select( $nom, $label, $options, $actuel, $tous ) {
	$out = '<div class="lpp-filtres__champ"><label for="' . esc_attr( $nom ) . '">' . esc_html( $label ) . '</label><select id="' . esc_attr( $nom ) . '" name="' . esc_attr( $nom ) . '" data-lpp-auto>';
	$out .= '<option value="">' . esc_html( $tous ) . '</option>';
	foreach ( $options as $val => $txt ) {
		$out .= '<option value="' . esc_attr( $val ) . '"' . selected( $actuel, $val, false ) . '>' . esc_html( $txt ) . '</option>';
	}
	return $out . '</select></div>';
}

/**
 * Pagination (liens qui conservent les filtres).
 *
 * @param WP_Query $q Requête.
 * @param array    $f Filtres.
 * @return string
 */
function lephare_pred_pagination( $q, $f ) {
	if ( $q->max_num_pages < 2 ) {
		return '';
	}
	$base = array_map(
		'rawurlencode',
		array_filter(
			array(
				'lph_type'    => $f['type'],
				'lph_orateur' => $f['orateur'],
				'lph_serie'   => $f['serie'],
				'lph_q'       => $f['q'],
			)
		)
	);
	$out = '<nav class="lpp-pagination" aria-label="Pagination des prédications"><ul role="list">';
	for ( $i = 1; $i <= $q->max_num_pages; $i++ ) {
		$url = add_query_arg( array_merge( $base, $i > 1 ? array( 'lph_page' => $i ) : array() ), get_permalink() ) . '#lph-predications';
		$out .= $i === $f['page']
			? '<li><span class="lpp-pagination__actuel" aria-current="page"><span class="screen-reader-text">Page </span>' . $i . '</span></li>'
			: '<li><a class="scroll-ignore" href="' . esc_url( $url ) . '"><span class="screen-reader-text">Page </span>' . $i . '</a></li>';
	}
	return $out . '</ul></nav>';
}

/**
 * Pastilles « Vidéo / Audio / PDF » disponibles.
 *
 * @param array $d Données.
 * @return string
 */
function lephare_pred_pastilles( $d ) {
	if ( ! lephare_pred_opt( 'pastilles' ) ) {
		return '';
	}
	$out = '';
	if ( $d['video'] ) {
		$out .= '<li class="lpp-pastille">' . lephare_pred_icone( 'video' ) . 'Vidéo</li>';
	}
	if ( $d['audio'] ) {
		$out .= '<li class="lpp-pastille">' . lephare_pred_icone( 'audio' ) . 'Audio</li>';
	}
	if ( $d['pdf_id'] ) {
		$out .= '<li class="lpp-pastille">' . lephare_pred_icone( 'pdf' ) . 'PDF</li>';
	}
	return $out ? '<ul class="lpp-pastilles" role="list" aria-label="Médias disponibles">' . $out . '</ul>' : '';
}

/**
 * Lignes orateur / date / référence.
 *
 * @param array $d       Données.
 * @param bool  $serie   Afficher la série ?
 * @return string
 */
function lephare_pred_meta( $d, $serie = false ) {
	$out = '<ul class="lpp-meta" role="list">';
	if ( $d['orateur'] && lephare_pred_opt( 'orateur' ) ) {
		$out .= '<li>' . lephare_pred_icone( 'orateur' ) . '<span><span class="screen-reader-text">Orateur : </span>' . esc_html( $d['orateur'] ) . '</span></li>';
	}
	$out .= '<li>' . lephare_pred_icone( 'calendrier' ) . '<span><span class="screen-reader-text">Date : </span><time datetime="' . esc_attr( $d['date_iso'] ) . '">' . esc_html( $d['date_txt'] ) . '</time></span></li>';
	if ( $d['reference'] && lephare_pred_opt( 'reference' ) ) {
		$out .= '<li>' . lephare_pred_icone( 'livre' ) . '<span><span class="screen-reader-text">Référence biblique : </span>' . esc_html( $d['reference'] ) . '</span></li>';
	}
	if ( $serie && $d['serie'] ) {
		$out .= '<li>' . lephare_pred_icone( 'serie' ) . '<span><span class="screen-reader-text">Série : </span>' . esc_html( $d['serie'] ) . '</span></li>';
	}
	return $out . '</ul>';
}

/**
 * Image à la une (alt de la Médiathèque) ou cadre neutre.
 *
 * @param int    $id     Article.
 * @param string $taille Taille.
 * @param string $sizes  Attribut sizes.
 * @param bool   $lazy   Chargement différé ?
 * @return string
 */
function lephare_pred_image( $id, $taille, $sizes, $lazy = true ) {
	if ( ! has_post_thumbnail( $id ) ) {
		return '<span class="lpp-image-vide" aria-hidden="true">' . lephare_pred_icone( 'livre' ) . '</span>';
	}
	return get_the_post_thumbnail( $id, $taille, array( 'loading' => $lazy ? 'lazy' : 'eager', 'decoding' => 'async', 'sizes' => $sizes ) );
}

/**
 * Prédication à la une (grande carte). Un seul lien : le bouton.
 *
 * @param array $d Données.
 * @return string
 */
function lephare_pred_carte_une( $d ) {
	$tid  = 'lpp-une-' . $d['id'];
	$out  = '<article class="lpp-une" aria-labelledby="' . esc_attr( $tid ) . '">';
	$h    = lephare_pred_opt( 'niveau_titre' );
	$out .= '<div class="lpp-une__media">' . lephare_pred_image( $d['id'], 'large', '(max-width: 900px) 100vw, 700px', false );
	if ( '' !== (string) lephare_pred_opt( 'txt_etiquette' ) ) {
		$out .= '<span class="lpp-etiquette">' . esc_html( lephare_pred_opt( 'txt_etiquette' ) ) . '</span>';
	}
	$out .= '</div><div class="lpp-une__contenu">';
	if ( $d['type'] ) {
		$out .= '<p class="lpp-type">' . esc_html( $d['type'][1] ) . '</p>';
	}
	$out .= '<' . $h . ' class="lpp-une__titre" id="' . esc_attr( $tid ) . '">' . esc_html( $d['titre'] ) . '</' . $h . '>';
	$out .= lephare_pred_meta( $d, true );
	if ( $d['extrait'] && lephare_pred_opt( 'extrait' ) ) {
		$out .= '<p class="lpp-une__extrait">' . esc_html( $d['extrait'] ) . '</p>';
	}
	$out .= lephare_pred_pastilles( $d );
	$out .= '<a class="lpp-bouton" href="' . esc_url( $d['url'] ) . '">' . esc_html( lephare_pred_opt( 'txt_bouton_une' ) ) . '<span class="screen-reader-text"> : ' . esc_html( $d['titre'] ) . '</span></a>';
	return $out . '</div></article>';
}

/**
 * Carte de la grille. Toute la carte est cliquable (un seul lien, celui du titre).
 *
 * @param array $d    Données.
 * @param int   $rang Rang (animation).
 * @param string $niveau Balise du titre.
 * @return string
 */
function lephare_pred_carte( $d, $rang = 0, $niveau = 'h3' ) {
	$tid  = 'lpp-carte-' . $d['id'] . '-' . wp_unique_id();
	$out  = '<li class="lpp-carte" data-lpp-reveal style="--lpp-delai:' . ( min( (int) $rang, 6 ) * 80 ) . 'ms"><article class="lpp-carte__inner" aria-labelledby="' . esc_attr( $tid ) . '">';
	$out .= '<div class="lpp-carte__media">' . lephare_pred_image( $d['id'], 'medium_large', '(max-width: 767px) 100vw, 380px' );
	if ( $d['type'] ) {
		$out .= '<span class="lpp-carte__type">' . esc_html( $d['type'][1] ) . '</span>';
	}
	$out .= '</div><div class="lpp-carte__contenu">';
	$out .= '<' . $niveau . ' class="lpp-carte__titre" id="' . esc_attr( $tid ) . '"><a class="lpp-lien-etendu" href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['titre'] ) . '</a></' . $niveau . '>';
	$out .= lephare_pred_meta( $d );
	$out .= '</div><div class="lpp-carte__pied">' . lephare_pred_pastilles( $d ) . '<span class="lpp-carte__suite" aria-hidden="true">' . esc_html( lephare_pred_opt( 'txt_bouton_carte' ) ) . ' <span class="lpp-fleche">→</span></span></div>';
	return $out . '</article></li>';
}

/**
 * État vide.
 *
 * @param string $message Message.
 * @param bool   $reset   Lien « Voir toutes les prédications » ?
 * @return string
 */
function lephare_pred_vide( $message, $reset = false ) {
	$out = '<div class="lpp-vide">' . lephare_pred_icone( 'recherche' ) . '<p class="lpp-vide__texte">' . esc_html( $message ) . '</p>';
	$out .= $reset
		? '<p class="lpp-vide__lien">Essayez un autre mot-clé ou <a class="scroll-ignore" href="' . esc_url( get_permalink() ) . '#lph-predications">affichez toutes les prédications</a>.</p>'
		: '<p class="lpp-vide__lien">Une question ? <a href="' . esc_url( home_url( '/contact/' ) ) . '">Contactez-nous</a></p>';
	return $out . '</div>';
}

/* =========================================================================
 * 5 bis. Concept « Médiathèque » : onglets Type, liste en lignes, dernière prédication, séries
 * ====================================================================== */

/**
 * Mois abrégé en français (« oct. »).
 *
 * @param DateTimeImmutable $d Date.
 * @return string
 */
function lephare_pred_mois_court( DateTimeImmutable $d ) {
	$mois = array( 1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.' );
	return $mois[ (int) $d->format( 'n' ) ];
}

/**
 * Nombre de prédications publiées (toutes, ou d'un Type).
 *
 * @param string $type Slug du Type, ou '' pour toutes.
 * @return int
 */
function lephare_pred_compter( $type = '' ) {
	$q = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'cat'            => $type ? 0 : lephare_pred_categorie_id(),
			'category_name'  => $type,
		)
	);
	return (int) $q->found_posts;
}

/**
 * Types (sous-catégories) qui ont au moins une prédication publiée : [ slug => nom ].
 *
 * @return array
 */
function lephare_pred_types_utilises() {
	$utiles = array();
	foreach ( lephare_pred_types() as $slug => $nom ) {
		if ( lephare_pred_compter( $slug ) > 0 ) {
			$utiles[ $slug ] = $nom;
		}
	}
	return $utiles;
}

/**
 * Onglets Type (liens : fonctionnent sans JavaScript, gardent les autres filtres).
 * Seulement les Types qui ont du contenu publié : un Type vide est masqué automatiquement.
 *
 * @param array $f Filtres.
 * @return string
 */
function lephare_pred_onglets( $f ) {
	$garder = array_map( 'rawurlencode', array_filter( array( 'lph_orateur' => $f['orateur'], 'lph_serie' => $f['serie'], 'lph_q' => $f['q'] ) ) );
	$base   = get_permalink();
	$items  = array( '' => array( 'Tout', lephare_pred_compter() ) );
	foreach ( lephare_pred_types_utilises() as $slug => $nom ) {
		$items[ $slug ] = array( $nom, lephare_pred_compter( $slug ) );
	}
	// Type vide demandé dans l'adresse (ancien lien) : on garde son onglet visible et actif.
	if ( '' !== $f['type'] && ! isset( $items[ $f['type'] ] ) && isset( lephare_pred_types()[ $f['type'] ] ) ) {
		$items[ $f['type'] ] = array( lephare_pred_types()[ $f['type'] ], 0 );
	}
	$out = '<nav class="lpp-onglets" aria-label="Types de ressources"><ul role="list">';
	foreach ( $items as $slug => $item ) {
		$url    = add_query_arg( array_merge( $garder, $slug ? array( 'lph_type' => $slug ) : array() ), $base ) . '#lph-predications';
		$actif  = $slug === $f['type'];
		$out   .= '<li><a class="scroll-ignore lpp-onglet' . ( $actif ? ' lpp-onglet--actif' : '' ) . '" href="' . esc_url( $url ) . '"' . ( $actif ? ' aria-current="page"' : '' ) . '>' . esc_html( $item[0] ) . ' <span class="lpp-onglet__nb"><span class="screen-reader-text">(</span>' . (int) $item[1] . '<span class="screen-reader-text"> ' . ( $item[1] > 1 ? 'ressources' : 'ressource' ) . ')</span></span></a></li>';
	}
	return $out . '</ul></nav>';
}

/**
 * Ligne « épisode » : date en colonne, type, titre, orateur · référence · série, médias, flèche.
 * Toute la ligne est cliquable (un seul lien : le titre).
 *
 * @param array  $d      Données.
 * @param int    $rang   Rang (animation).
 * @param string $niveau Balise du titre.
 * @return string
 */
function lephare_pred_ligne( $d, $rang = 0, $niveau = 'h3' ) {
	$tid    = 'lpp-ligne-' . $d['id'] . '-' . wp_unique_id();
	$media  = lephare_pred_media_principal( $d );
	$infos  = array();
	if ( $d['orateur'] && lephare_pred_opt( 'orateur' ) ) {
		$infos[] = '<li>' . lephare_pred_icone( 'orateur' ) . '<span><span class="screen-reader-text">Orateur : </span>' . esc_html( $d['orateur'] ) . '</span></li>';
	}
	if ( $d['reference'] && lephare_pred_opt( 'reference' ) ) {
		$infos[] = '<li>' . lephare_pred_icone( 'livre' ) . '<span><span class="screen-reader-text">Référence biblique : </span>' . esc_html( $d['reference'] ) . '</span></li>';
	}
	if ( $d['serie'] ) {
		$infos[] = '<li>' . lephare_pred_icone( 'serie' ) . '<span><span class="screen-reader-text">Série : </span>' . esc_html( $d['serie'] ) . '</span></li>';
	}
	$out  = '<li class="lpp-ligne" data-lpp-reveal style="--lpp-delai:' . ( min( (int) $rang, 6 ) * 60 ) . 'ms"><article class="lpp-ligne__inner" aria-labelledby="' . esc_attr( $tid ) . '">';

	// Vignette : photo, icône du média principal, date en badge.
	$out .= '<div class="lpp-ligne__vignette">' . lephare_pred_image( $d['id'], 'medium_large', '200px' );
	$out .= '<span class="lpp-ligne__media" aria-hidden="true">' . lephare_pred_icone( $media[0] ) . '</span>';
	$out .= '<time class="lpp-ligne__date" datetime="' . esc_attr( $d['date_iso'] ) . '"><span class="lpp-ligne__jour">' . esc_html( $d['date_obj']->format( 'j' ) ) . '</span><span class="lpp-ligne__mois">' . esc_html( lephare_pred_mois_court( $d['date_obj'] ) ) . '<span class="screen-reader-text"> ' . esc_html( $d['date_obj']->format( 'Y' ) ) . '</span></span></time></div>';

	// Texte.
	$out .= '<div class="lpp-ligne__corps"><div class="lpp-ligne__haut">';
	if ( $d['type'] ) {
		$out .= '<p class="lpp-ligne__type">' . esc_html( $d['type'][1] ) . '</p>';
	}
	if ( lephare_pred_est_nouveau( $d ) ) {
		$out .= '<p class="lpp-nouveau">Nouveau</p>';
	}
	$out .= lephare_pred_pastilles( $d ) . '</div>';
	$out .= '<' . $niveau . ' class="lpp-ligne__titre" id="' . esc_attr( $tid ) . '"><a class="lpp-lien-etendu" href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['titre'] ) . '</a></' . $niveau . '>';
	if ( $infos ) {
		$out .= '<ul class="lpp-ligne__infos" role="list">' . implode( '', $infos ) . '</ul>';
	}
	$out .= '</div>';

	// Actions : bouton principal (visuel : le lien est le titre) + téléchargement direct du PDF.
	$out .= '<div class="lpp-ligne__actions"><span class="lpp-ligne__action" aria-hidden="true">' . lephare_pred_icone( 'lecture' ) . esc_html( $media[1] ) . '</span>';
	if ( $d['pdf_id'] ) {
		$out .= '<a class="lpp-ligne__pdf" href="' . esc_url( wp_get_attachment_url( $d['pdf_id'] ) ) . '" download>' . lephare_pred_icone( 'pdf' ) . '<span>PDF</span><span class="screen-reader-text"> : télécharger « ' . esc_html( $d['titre'] ) . ' »</span></a>';
	}
	$out .= '</div>';
	return $out . '</article></li>';
}

/**
 * Média principal d'une prédication : [ icône, verbe ].
 *
 * @param array $d Données.
 * @return array
 */
function lephare_pred_media_principal( $d ) {
	if ( $d['video'] ) {
		return array( 'video', 'Regarder' );
	}
	if ( $d['audio'] ) {
		return array( 'audio', 'Écouter' );
	}
	if ( $d['pdf_id'] ) {
		return array( 'pdf', 'Lire' );
	}
	return array( 'livre', 'Découvrir' );
}

/**
 * « Nouveau » : prédication des 14 derniers jours.
 *
 * @param array $d Données.
 * @return bool
 */
function lephare_pred_est_nouveau( $d ) {
	$aujourdhui = new DateTimeImmutable( current_time( 'Y-m-d' ) );
	$jours      = (int) $d['date_obj']->diff( $aujourdhui )->format( '%r%a' );
	return $jours >= 0 && $jours <= 14;
}

/**
 * Carte « Dernière prédication » (bannière) : image 16/9, grand bouton lecture, titre, infos.
 * Un seul lien (le titre), toute la carte est cliquable.
 *
 * @param array $brut Options : surtitre, niveau_titre.
 * @return string
 */
function lephare_pred_vedette_html( $brut = array() ) {
	wp_enqueue_style( 'lephare-predications' );
	$surtitre = isset( $brut['surtitre'] ) && '' !== trim( (string) $brut['surtitre'] ) ? sanitize_text_field( $brut['surtitre'] ) : 'Dernière prédication';
	$h        = in_array( $brut['niveau_titre'] ?? 'h2', array( 'h2', 'h3' ), true ) ? ( $brut['niveau_titre'] ?? 'h2' ) : 'h2';
	if ( ! lephare_pred_categorie_id() ) {
		return '';
	}
	$q = lephare_pred_requete( array( 'type' => '', 'orateur' => '', 'serie' => '', 'q' => '', 'page' => 1 ), 1 );
	if ( ! $q->have_posts() ) {
		return '<div class="lpp-vedette lpp-vedette--vide"><p>Aucune prédication publiée pour le moment.</p></div>';
	}
	$d     = lephare_pred_donnees( $q->posts[0]->ID );
	$media = lephare_pred_media_principal( $d );
	$tid   = 'lpp-vedette-' . $d['id'];
	// Pile : deux cartes fantômes derrière la carte principale (effet « bibliothèque »).
	$out   = '<div class="lpp-vedette-pile"><article class="lpp-vedette" aria-labelledby="' . esc_attr( $tid ) . '">';
	$out  .= '<div class="lpp-vedette__media">' . lephare_pred_image( $d['id'], 'large', '(max-width: 1024px) 100vw, 600px', false );
	$out  .= '<span class="lpp-vedette__lecture" aria-hidden="true"><svg viewBox="0 0 24 24" width="30" height="30" focusable="false"><path d="M8 5.5v13l11-6.5z" fill="currentColor"/></svg></span>';
	$out  .= '<span class="lpp-etiquette">' . esc_html( $surtitre ) . '</span>';
	if ( lephare_pred_est_nouveau( $d ) ) {
		$out .= '<span class="lpp-nouveau lpp-nouveau--image">Nouveau</span>';
	}
	$out .= '</div><div class="lpp-vedette__corps">';
	if ( $d['type'] ) {
		$out .= '<p class="lpp-vedette__type">' . esc_html( $d['type'][1] ) . '</p>';
	}
	$out .= '<' . $h . ' class="lpp-vedette__titre" id="' . esc_attr( $tid ) . '"><a class="lpp-lien-etendu" href="' . esc_url( $d['url'] ) . '">' . esc_html( $d['titre'] ) . '</a></' . $h . '>';
	$infos = array();
	if ( $d['orateur'] ) {
		$infos[] = '<li>' . lephare_pred_icone( 'orateur' ) . '<span><span class="screen-reader-text">Orateur : </span>' . esc_html( $d['orateur'] ) . '</span></li>';
	}
	$infos[] = '<li>' . lephare_pred_icone( 'calendrier' ) . '<span><span class="screen-reader-text">Date : </span><time datetime="' . esc_attr( $d['date_iso'] ) . '">' . esc_html( $d['date_txt'] ) . '</time></span></li>';
	if ( $d['reference'] ) {
		$infos[] = '<li>' . lephare_pred_icone( 'livre' ) . '<span><span class="screen-reader-text">Référence biblique : </span>' . esc_html( $d['reference'] ) . '</span></li>';
	}
	$out .= '<ul class="lpp-vedette__infos" role="list">' . implode( '', $infos ) . '</ul>';
	$out .= '<div class="lpp-vedette__pied"><span class="lpp-vedette__action" aria-hidden="true">' . lephare_pred_icone( 'lecture' ) . esc_html( $media[1] ) . '</span>' . lephare_pred_pastilles( $d ) . '</div>';
	wp_reset_postdata();
	return $out . '</div></article></div>';
}

/**
 * Bande « Séries » : une carte par série (couverture = image de sa prédication la plus récente),
 * avec le nombre de ressources. Chaque carte filtre la liste sur cette série.
 *
 * @param array $brut Options : page (URL de la liste ; par défaut la page en cours).
 * @return string
 */
function lephare_pred_series_html( $brut = array() ) {
	wp_enqueue_style( 'lephare-predications' );
	$series = lephare_pred_valeurs( '_lph_serie' );
	if ( ! $series ) {
		return '<p class="lpp-series__vide">Les séries apparaîtront ici dès qu’une prédication aura une série.</p>';
	}
	$page = ! empty( $brut['page'] ) ? esc_url_raw( $brut['page'] ) : get_permalink();
	$out  = '<ul class="lpp-series" role="list">';
	foreach ( $series as $rang => $serie ) {
		$ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'cat'            => lephare_pred_categorie_id(),
				'meta_key'       => '_lph_serie', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $serie, // phpcs:ignore WordPress.DB.SlowDBQuery
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		if ( ! $ids ) {
			continue;
		}
		$couverture = 0;
		foreach ( $ids as $id ) {
			if ( has_post_thumbnail( $id ) ) {
				$couverture = $id;
				break;
			}
		}
		$n    = count( $ids );
		$url  = add_query_arg( 'lph_serie', rawurlencode( $serie ), $page ) . '#lph-predications';
		$out .= '<li class="lpp-serie" data-lpp-reveal style="--lpp-delai:' . ( min( (int) $rang, 6 ) * 80 ) . 'ms"><a class="lpp-serie__lien scroll-ignore" href="' . esc_url( $url ) . '">';
		$out .= '<span class="lpp-serie__couverture">' . ( $couverture ? get_the_post_thumbnail( $couverture, 'medium_large', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ) : '' ) . '</span>';
		$out .= '<span class="lpp-serie__index" aria-hidden="true">' . str_pad( (string) ( $rang + 1 ), 2, '0', STR_PAD_LEFT ) . '</span>';
		$out .= '<span class="lpp-serie__texte"><span class="lpp-serie__surtitre">Série · ' . $n . ' ' . ( $n > 1 ? 'ressources' : 'ressource' ) . '</span><span class="lpp-serie__nom">' . esc_html( $serie ) . '</span>';
		$out .= '<span class="lpp-serie__voir" aria-hidden="true">Voir la série <span class="lpp-fleche">→</span></span></span>';
		$out .= '</a></li>';
	}
	return $out . '</ul>';
}

add_shortcode( 'lephare_predication_vedette', 'lephare_pred_vedette_html' );
add_shortcode( 'lephare_predications_series', 'lephare_pred_series_html' );

/**
 * Chiffres clés (bannière) : nombre de ressources, de séries et d'orateurs, calculés en direct.
 *
 * @return string
 */
function lephare_pred_chiffres_html() {
	wp_enqueue_style( 'lephare-predications' );
	if ( ! lephare_pred_categorie_id() ) {
		return '';
	}
	$chiffres = array(
		array( lephare_pred_compter(), 'ressource', 'ressources' ),
		array( count( lephare_pred_valeurs( '_lph_serie' ) ), 'série', 'séries' ),
		array( count( lephare_pred_valeurs( '_lph_orateur' ) ), 'orateur', 'orateurs' ),
	);
	$out = '<ul class="lpp-chiffres" role="list">';
	foreach ( $chiffres as $c ) {
		$out .= '<li><span class="lpp-chiffres__nb">' . (int) $c[0] . '</span><span class="lpp-chiffres__label">' . esc_html( $c[0] > 1 ? $c[2] : $c[1] ) . '</span></li>';
	}
	return $out . '</ul>';
}
add_shortcode( 'lephare_predications_chiffres', 'lephare_pred_chiffres_html' );

/* =========================================================================
 * 6. Page d'une prédication (article de la catégorie Prédications)
 * ====================================================================== */

add_filter(
	'the_content',
	static function ( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || get_the_ID() !== get_queried_object_id() || ! lephare_pred_est_predication( get_the_ID() ) ) {
			return $content;
		}
		$d = lephare_pred_donnees( get_the_ID() );
		return lephare_pred_fiche_publique( $d ) . '<div class="lpp-texte">' . $content . '</div>' . lephare_pred_liees( $d );
	},
	20
);

/**
 * Fiche affichée en haut de la page d'une prédication : infos + vidéo + audio + PDF.
 *
 * @param array $d Données.
 * @return string
 */
function lephare_pred_fiche_publique( $d ) {
	$out = '<section class="lpp-fiche" aria-label="Informations et médias de la prédication">';
	$out .= '<div class="lpp-fiche__infos">';
	if ( $d['type'] ) {
		$out .= '<p class="lpp-type">' . esc_html( $d['type'][1] ) . '</p>';
	}
	$out .= lephare_pred_meta( $d, true ) . '</div>';

	// Vidéo : YouTube (sans cookies, 16/9) ou fichier de la Médiathèque.
	if ( $d['video'] ) {
		$yt = lephare_pred_youtube_id( $d['video'] );
		$out .= '<div class="lpp-bloc"><h2 class="lpp-bloc__titre">' . lephare_pred_icone( 'video' ) . 'Vidéo</h2>';
		if ( $yt ) {
			$out .= '<div class="lpp-video"><iframe src="' . esc_url( 'https://www.youtube-nocookie.com/embed/' . $yt . '?rel=0' ) . '" title="' . esc_attr( 'Vidéo : ' . $d['titre'] ) . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>';
			$out .= '<p class="lpp-bloc__lien"><a href="' . esc_url( $d['video'] ) . '" target="_blank" rel="noopener">Regarder sur YouTube<span class="screen-reader-text"> (nouvel onglet)</span></a></p>';
		} else {
			$type = 'mov' === strtolower( pathinfo( (string) wp_parse_url( $d['video'], PHP_URL_PATH ), PATHINFO_EXTENSION ) ) ? 'video/mp4' : '';
			$out .= '<div class="lpp-video lpp-video--fichier"><video controls preload="metadata" playsinline aria-label="' . esc_attr( 'Vidéo : ' . $d['titre'] ) . '"><source src="' . esc_url( $d['video'] ) . '"' . ( $type ? ' type="' . esc_attr( $type ) . '"' : '' ) . '>Votre navigateur ne peut pas lire cette vidéo. <a href="' . esc_url( $d['video'] ) . '">Télécharger la vidéo</a>.</video></div>';
		}
		$out .= '</div>';
	}

	// Audio : lecteur WordPress (MediaElement, utilisable au clavier).
	if ( $d['audio'] ) {
		$out .= '<div class="lpp-bloc"><h2 class="lpp-bloc__titre">' . lephare_pred_icone( 'audio' ) . 'Audio</h2>';
		$out .= '<div class="lpp-audio">' . wp_audio_shortcode( array( 'src' => esc_url( $d['audio'] ), 'preload' => 'metadata' ) ) . '</div>';
		$out .= '<p class="lpp-bloc__lien"><a href="' . esc_url( $d['audio'] ) . '" download>Télécharger l’audio</a></p></div>';
	}

	// PDF à télécharger.
	if ( $d['pdf_id'] ) {
		$fichier = get_attached_file( $d['pdf_id'] );
		$taille  = $fichier && file_exists( $fichier ) ? size_format( filesize( $fichier ), 0 ) : '';
		$out    .= '<div class="lpp-bloc"><h2 class="lpp-bloc__titre">' . lephare_pred_icone( 'pdf' ) . 'Document</h2>';
		$out    .= '<a class="lpp-bouton lpp-bouton--pdf" href="' . esc_url( wp_get_attachment_url( $d['pdf_id'] ) ) . '" download>' . lephare_pred_icone( 'pdf' ) . 'Télécharger le PDF<span class="lpp-bouton__taille">' . esc_html( $taille ? 'PDF, ' . $taille : 'PDF' ) . '</span></a></div>';
	}

	return $out . '</section>';
}

/**
 * Prédications liées : même série, sinon même orateur (3 au maximum).
 *
 * @param array $d Données.
 * @return string
 */
function lephare_pred_liees( $d ) {
	$critere = $d['serie'] ? array( '_lph_serie', $d['serie'], 'Dans la même série' ) : ( $d['orateur'] ? array( '_lph_orateur', $d['orateur'], 'Du même orateur' ) : null );
	$ids     = array();
	$titre   = 'Prédications liées';
	if ( $critere ) {
		$ids   = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'cat' => lephare_pred_categorie_id(), 'meta_key' => $critere[0], 'meta_value' => $critere[1], 'post__not_in' => array( $d['id'] ), 'posts_per_page' => 3, 'fields' => 'ids', 'no_found_rows' => true ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$titre = $critere[2];
	}
	// Complément : même orateur si la série ne donne rien.
	if ( ! $ids && $d['serie'] && $d['orateur'] ) {
		$ids   = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'cat' => lephare_pred_categorie_id(), 'meta_key' => '_lph_orateur', 'meta_value' => $d['orateur'], 'post__not_in' => array( $d['id'] ), 'posts_per_page' => 3, 'fields' => 'ids', 'no_found_rows' => true ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$titre = 'Du même orateur';
	}
	$page = get_page_by_path( 'predications-ressources' );
	$out  = '<section class="lpp-liees" aria-labelledby="lpp-liees-titre"><div class="lpp-liees__entete"><h2 id="lpp-liees-titre" class="lpp-liees__titre">' . esc_html( $ids ? $titre : 'Prédications & ressources' ) . '</h2>';
	if ( $page ) {
		$out .= '<a class="lpp-lien-retour" href="' . esc_url( get_permalink( $page ) ) . '">Toutes les prédications <span aria-hidden="true">→</span></a>';
	}
	$out .= '</div>';
	if ( $ids ) {
		$out .= '<ul class="lpp-grille lpp-grille--liees" role="list">';
		foreach ( $ids as $rang => $id ) {
			$out .= lephare_pred_carte( lephare_pred_donnees( $id ), $rang, 'h3' );
		}
		$out .= '</ul>';
	}
	return $out . '</section>';
}

/* =========================================================================
 * 7. Widget Elementor « Prédications & ressources » (catégorie LePhare)
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
		require_once __DIR__ . '/includes/class-lephare-predications-widget.php';
		$widgets_manager->register( new LePhare_Predications_Widget() );
		require_once __DIR__ . '/includes/class-lephare-predications-widgets-mediatheque.php';
		$widgets_manager->register( new LePhare_Predication_Vedette_Widget() );
		$widgets_manager->register( new LePhare_Predications_Series_Widget() );
		$widgets_manager->register( new LePhare_Predications_Chiffres_Widget() );
	}
);
