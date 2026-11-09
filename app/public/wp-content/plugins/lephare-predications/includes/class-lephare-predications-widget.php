<?php
/**
 * Widget Elementor « Prédications & ressources ».
 *
 * Même affichage que le shortcode [lephare_predications], mais réglable dans Elementor :
 *  - onglet Contenu : nombre, Type imposé, à la une, filtres, informations des cartes, textes ;
 *  - onglet Style   : colonnes, espacements, arrondis, format des images, couleurs, ombres, typographies.
 * Par défaut, toutes les couleurs viennent de la palette Kadence (aucun réglage = design de la page Événements).
 *
 * @package LePhare_Predications
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * Widget « Prédications & ressources ».
 */
class LePhare_Predications_Widget extends Widget_Base {

	/** @return string */
	public function get_name() {
		return 'lephare-predications';
	}

	/** @return string */
	public function get_title() {
		return 'Prédications & ressources';
	}

	/** @return string */
	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/** @return string[] */
	public function get_categories() {
		return array( 'lephare' );
	}

	/** @return string[] */
	public function get_keywords() {
		return array( 'prédication', 'predication', 'sermon', 'enseignement', 'étude', 'ressources', 'lephare' );
	}

	/** @return string[] */
	public function get_style_depends(): array {
		return array( 'lephare-predications' );
	}

	/** @return string[] */
	public function get_script_depends(): array {
		return array( 'lephare-predications' );
	}

	/**
	 * Interrupteur Oui / Non.
	 *
	 * @param string $id      Identifiant.
	 * @param string $label   Libellé.
	 * @param bool   $defaut  Valeur par défaut.
	 * @param array  $extra   Réglages en plus.
	 */
	private function interrupteur( $id, $label, $defaut = true, $extra = array() ) {
		$this->add_control(
			$id,
			array_merge(
				array(
					'label'        => $label,
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => 'Oui',
					'label_off'    => 'Non',
					'return_value' => 'yes',
					'default'      => $defaut ? 'yes' : '',
				),
				$extra
			)
		);
	}

	/**
	 * Couleur appliquée à des sélecteurs.
	 *
	 * @param string $id        Identifiant.
	 * @param string $label     Libellé.
	 * @param array  $selecteurs Sélecteur => déclaration CSS ({{VALUE}}).
	 * @param string $aide      Description.
	 */
	private function couleur( $id, $label, $selecteurs, $aide = '' ) {
		$this->add_control(
			$id,
			array(
				'label'       => $label,
				'type'        => Controls_Manager::COLOR,
				'selectors'   => $selecteurs,
				'description' => $aide,
			)
		);
	}

	/**
	 * Réglages du widget.
	 */
	protected function register_controls() {
		$def   = lephare_pred_options_defaut();
		$types = array( '' => 'Tous les types' ) + lephare_pred_types();
		$aide_contraste = 'Gardez un contraste suffisant (au moins 4,5:1 pour le texte) : vérifiez avec un outil de contraste.';

		/* ---------------- Onglet CONTENU ---------------- */

		$this->start_controls_section( 'section_liste', array( 'label' => 'Liste' ) );
		$this->add_control(
			'par_page',
			array(
				'label'   => 'Nombre de cartes par page',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 24,
				'step'    => 1,
				'default' => $def['par_page'],
			)
		);
		$this->add_control(
			'type_fixe',
			array(
				'label'       => 'Afficher seulement',
				'type'        => Controls_Manager::SELECT,
				'options'     => $types,
				'default'     => '',
				'description' => 'Par exemple « Enseignement » pour une page qui ne montre que les enseignements.',
			)
		);
		$this->add_control(
			'style',
			array(
				'label'   => 'Présentation',
				'type'    => Controls_Manager::SELECT,
				'options' => array( 'cartes' => 'Cartes avec image', 'lignes' => 'Liste en lignes (épisodes)' ),
				'default' => 'cartes',
			)
		);
		$this->interrupteur( 'une', 'Prédication à la une' );
		$this->add_control(
			'niveau_titre',
			array(
				'label'       => 'Niveau des titres des cartes',
				'type'        => Controls_Manager::SELECT,
				'options'     => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4' ),
				'default'     => 'h3',
				'description' => 'H3 sous un titre H2 de section (recommandé pour l’accessibilité).',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'section_filtres', array( 'label' => 'Filtres et recherche' ) );
		$this->interrupteur( 'filtres', 'Afficher la barre de filtres' );
		$si_filtres = array( 'condition' => array( 'filtres' => 'yes' ) );
		$this->interrupteur( 'recherche', 'Champ de recherche', true, $si_filtres );
		$this->interrupteur( 'filtre_type', 'Filtre Type', true, array( 'condition' => array( 'filtres' => 'yes', 'type_fixe' => '' ) ) );
		$this->add_control(
			'style_type',
			array(
				'label'     => 'Filtre Type affiché en',
				'type'      => Controls_Manager::SELECT,
				'options'   => array( 'liste' => 'Liste déroulante', 'onglets' => 'Onglets (pastilles)' ),
				'default'   => 'liste',
				'condition' => array( 'filtres' => 'yes', 'filtre_type' => 'yes', 'type_fixe' => '' ),
			)
		);
		$this->interrupteur( 'filtre_orateur', 'Filtre Orateur', true, $si_filtres );
		$this->interrupteur( 'filtre_serie', 'Filtre Série', true, $si_filtres );
		$this->interrupteur( 'compteur', 'Nombre de résultats' );
		$this->interrupteur( 'pagination', 'Pagination' );
		$this->end_controls_section();

		$this->start_controls_section( 'section_cartes', array( 'label' => 'Informations des cartes' ) );
		$this->interrupteur( 'orateur', 'Orateur' );
		$this->interrupteur( 'reference', 'Référence biblique' );
		$this->interrupteur( 'pastilles', 'Pastilles Vidéo / Audio / PDF' );
		$this->interrupteur( 'extrait', 'Extrait (prédication à la une)' );
		$this->end_controls_section();

		$this->start_controls_section( 'section_textes', array( 'label' => 'Textes' ) );
		$textes = array(
			'txt_etiquette'    => 'Étiquette « À la une »',
			'txt_bouton_une'   => 'Bouton de la prédication à la une',
			'txt_bouton_carte' => 'Bouton des cartes',
			'txt_recherche'    => 'Libellé de la recherche',
			'txt_placeholder'  => 'Texte d’exemple dans la recherche',
			'txt_filtrer'      => 'Bouton Filtrer',
			'txt_reset'        => 'Lien Réinitialiser',
			'txt_aucun'        => 'Message si aucun résultat',
			'txt_vide'         => 'Message si aucune prédication publiée',
		);
		foreach ( $textes as $id => $label ) {
			$this->add_control(
				$id,
				array(
					'label'       => $label,
					'type'        => Controls_Manager::TEXT,
					'default'     => $def[ $id ],
					'label_block' => true,
				)
			);
		}
		$this->end_controls_section();

		/* ---------------- Onglet STYLE ---------------- */

		$this->start_controls_section( 'style_mise_en_page', array( 'label' => 'Mise en page', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control(
			'colonnes',
			array(
				'label'     => 'Colonnes',
				'type'      => Controls_Manager::SELECT,
				'options'   => array( '' => 'Automatique', '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .lpp-grille' => '--lpp-colonnes: repeat({{VALUE}}, minmax(0, 1fr));' ),
			)
		);
		$this->add_responsive_control(
			'espace_cartes',
			array(
				'label'      => 'Espace entre les cartes',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array( '{{WRAPPER}} .lpp-grille' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'espace_blocs',
			array(
				'label'       => 'Espace entre les blocs',
				'description' => 'Entre la prédication à la une, les filtres et la grille.',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 96 ) ),
				'selectors'  => array( '{{WRAPPER}} .lpp' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'rayon',
			array(
				'label'      => 'Arrondi des coins',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .lpp' => '--lpp-rayon: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'format_image',
			array(
				'label'     => 'Format des images des cartes',
				'type'      => Controls_Manager::SELECT,
				'options'   => array( '' => 'Par défaut (16/10)', '16 / 9' => '16/9', '4 / 3' => '4/3', '1 / 1' => 'Carré', '3 / 4' => 'Portrait 3/4' ),
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .lpp-carte__media' => 'aspect-ratio: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'style_couleurs', array( 'label' => 'Couleurs', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->couleur( 'c_bouton', 'Boutons et éléments actifs', array( '{{WRAPPER}} .lpp' => '--lpp-vert: {{VALUE}};' ), $aide_contraste );
		$this->couleur( 'c_bouton_survol', 'Boutons au survol', array( '{{WRAPPER}} .lpp' => '--lpp-vert-fonce: {{VALUE}};' ) );
		$this->couleur( 'c_bouton_texte', 'Texte des boutons', array( '{{WRAPPER}} .lpp-bouton, {{WRAPPER}} .lpp-bouton:hover, {{WRAPPER}} .lpp-carte__suite, {{WRAPPER}} .lpp-etiquette, {{WRAPPER}} .lpp-pagination__actuel' => 'color: {{VALUE}};' ) );
		$this->couleur( 'c_titres', 'Titres', array( '{{WRAPPER}} .lpp-une__titre, {{WRAPPER}} .lpp-carte__titre, {{WRAPPER}} .lpp-carte__titre a' => 'color: {{VALUE}};' ), $aide_contraste );
		$this->couleur( 'c_type', 'Type (au-dessus du titre à la une)', array( '{{WRAPPER}} .lpp-type' => 'color: {{VALUE}};' ) );
		$this->couleur( 'c_texte', 'Textes (orateur, date, référence…)', array( '{{WRAPPER}} .lpp-meta, {{WRAPPER}} .lpp-une__extrait, {{WRAPPER}} .lpp-resultats' => 'color: {{VALUE}};' ), $aide_contraste );
		$this->couleur( 'c_icones', 'Icônes', array( '{{WRAPPER}} .lpp-meta .lpp-icone' => 'color: {{VALUE}};' ) );
		$this->couleur( 'c_liens', 'Liens (Réinitialiser…)', array( '{{WRAPPER}} .lpp-lien-reset, {{WRAPPER}} .lpp-vide__lien a' => 'color: {{VALUE}};' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_cartes', array( 'label' => 'Cartes', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->couleur( 'c_carte_fond', 'Fond des cartes', array( '{{WRAPPER}} .lpp-carte__inner, {{WRAPPER}} .lpp-une' => 'background-color: {{VALUE}};' ) );
		$this->couleur( 'c_carte_bord', 'Bordure des cartes', array( '{{WRAPPER}} .lpp-carte__inner' => 'border-color: {{VALUE}};' ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'ombre_carte', 'label' => 'Ombre', 'selector' => '{{WRAPPER}} .lpp-carte__inner' ) );
		$this->add_responsive_control(
			'marge_carte',
			array(
				'label'      => 'Marges intérieures du texte',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array( '{{WRAPPER}} .lpp-carte__contenu' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_control( 'titre_badge', array( 'label' => 'Badge Type (sur l’image)', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->couleur( 'c_badge_fond', 'Fond du badge', array( '{{WRAPPER}} .lpp-carte__type' => 'background-color: {{VALUE}};' ) );
		$this->couleur( 'c_badge_texte', 'Texte du badge', array( '{{WRAPPER}} .lpp-carte__type' => 'color: {{VALUE}};' ), $aide_contraste );
		$this->add_control( 'titre_etiquette', array( 'label' => 'Étiquette « À la une »', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->couleur( 'c_etiquette_fond', 'Fond de l’étiquette', array( '{{WRAPPER}} .lpp-etiquette' => 'background-color: {{VALUE}};' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_filtres', array( 'label' => 'Barre de filtres', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->couleur( 'c_filtres_fond', 'Fond', array( '{{WRAPPER}} .lpp-filtres' => 'background-color: {{VALUE}};' ) );
		$this->couleur( 'c_filtres_libelles', 'Libellés', array( '{{WRAPPER}} .lpp-filtres label' => 'color: {{VALUE}};' ), $aide_contraste );
		$this->couleur( 'c_champs_bord', 'Bordure des champs', array( '{{WRAPPER}} .lpp-filtres input[type="search"], {{WRAPPER}} .lpp-filtres select' => 'border-color: {{VALUE}};' ), 'Contraste d’au moins 3:1 avec le fond.' );
		$this->end_controls_section();

		$this->start_controls_section( 'style_typo', array( 'label' => 'Typographie', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typo_titre_une', 'label' => 'Titre à la une', 'selector' => '{{WRAPPER}} .lpp-une__titre' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typo_titre_carte', 'label' => 'Titres des cartes', 'selector' => '{{WRAPPER}} .lpp-carte__titre' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typo_meta', 'label' => 'Textes des cartes', 'selector' => '{{WRAPPER}} .lpp-meta' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typo_boutons', 'label' => 'Boutons', 'selector' => '{{WRAPPER}} .lpp-bouton, {{WRAPPER}} .lpp-carte__suite' ) );
		$this->end_controls_section();
	}

	/**
	 * Affichage (même code que le shortcode).
	 */
	protected function render() {
		$s = $this->get_settings_for_display();
		$o = array();
		foreach ( array_keys( lephare_pred_options_defaut() ) as $cle ) {
			if ( array_key_exists( $cle, $s ) ) {
				$o[ $cle ] = is_array( $s[ $cle ] ) ? '' : $s[ $cle ];
			}
		}
		echo lephare_pred_rendu( $o ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML échappé dans lephare_pred_rendu().
	}
}
