<?php
/**
 * Widgets Elementor du concept « Médiathèque » :
 *  - « Dernière prédication » : carte lecteur de la prédication la plus récente (bannière) ;
 *  - « Séries de prédications » : une carte par série, qui filtre la liste.
 *
 * @package LePhare_Predications
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * Widget « Dernière prédication ».
 */
class LePhare_Predication_Vedette_Widget extends Widget_Base {

	/** @return string */
	public function get_name() {
		return 'lephare-predication-vedette';
	}

	/** @return string */
	public function get_title() {
		return 'Dernière prédication';
	}

	/** @return string */
	public function get_icon() {
		return 'eicon-play';
	}

	/** @return string[] */
	public function get_categories() {
		return array( 'lephare' );
	}

	/** @return string[] */
	public function get_keywords() {
		return array( 'prédication', 'dernière', 'lecteur', 'vidéo', 'lephare' );
	}

	/** @return string[] */
	public function get_style_depends(): array {
		return array( 'lephare-predications' );
	}

	/**
	 * Réglages.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_contenu', array( 'label' => 'Contenu' ) );
		$this->add_control(
			'surtitre',
			array(
				'label'       => 'Étiquette sur l’image',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Dernière prédication',
				'label_block' => true,
			)
		);
		$this->add_control(
			'niveau_titre',
			array(
				'label'   => 'Niveau du titre',
				'type'    => Controls_Manager::SELECT,
				'options' => array( 'h2' => 'H2', 'h3' => 'H3' ),
				'default' => 'h2',
			)
		);
		$this->add_control(
			'info',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Affiche automatiquement la prédication la plus récente (date de la prédication).',
				'content_classes' => 'elementor-descriptor',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'style_carte', array( 'label' => 'Carte', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'c_fond', array( 'label' => 'Fond', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .lpp-vedette' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'c_titre', array( 'label' => 'Titre', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .lpp-vedette__titre, {{WRAPPER}} .lpp-vedette__titre a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'c_lecture', array( 'label' => 'Bouton lecture', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .lpp-vedette' => '--lpp-vert: {{VALUE}};' ) ) );
		$this->add_control(
			'rayon',
			array(
				'label'      => 'Arrondi des coins',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .lpp-vedette' => '--lpp-rayon: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typo_titre', 'label' => 'Typographie du titre', 'selector' => '{{WRAPPER}} .lpp-vedette__titre' ) );
		$this->end_controls_section();
	}

	/**
	 * Affichage.
	 */
	protected function render() {
		$s = $this->get_settings_for_display();
		echo lephare_pred_vedette_html( array( 'surtitre' => $s['surtitre'] ?? '', 'niveau_titre' => $s['niveau_titre'] ?? 'h2' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans la fonction.
	}
}

/**
 * Widget « Chiffres des prédications » : ressources, séries, orateurs (calculés automatiquement).
 */
class LePhare_Predications_Chiffres_Widget extends Widget_Base {

	/** @return string */
	public function get_name() {
		return 'lephare-predications-chiffres';
	}

	/** @return string */
	public function get_title() {
		return 'Chiffres des prédications';
	}

	/** @return string */
	public function get_icon() {
		return 'eicon-counter';
	}

	/** @return string[] */
	public function get_categories() {
		return array( 'lephare' );
	}

	/** @return string[] */
	public function get_style_depends(): array {
		return array( 'lephare-predications' );
	}

	/**
	 * Réglages (style uniquement : les nombres sont calculés).
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_info', array( 'label' => 'Contenu' ) );
		$this->add_control(
			'info',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Nombre de ressources publiées, de séries et d’orateurs : calculé automatiquement.',
				'content_classes' => 'elementor-descriptor',
			)
		);
		$this->end_controls_section();
		$this->start_controls_section( 'style_chiffres', array( 'label' => 'Chiffres', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'c_nb', array( 'label' => 'Couleur des nombres', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .lpp-chiffres__nb' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'c_label', array( 'label' => 'Couleur des libellés', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .lpp-chiffres__label' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typo_nb', 'label' => 'Typographie des nombres', 'selector' => '{{WRAPPER}} .lpp-chiffres__nb' ) );
		$this->end_controls_section();
	}

	/**
	 * Affichage.
	 */
	protected function render() {
		echo lephare_pred_chiffres_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans la fonction.
	}
}

/**
 * Widget « Séries de prédications ».
 */
class LePhare_Predications_Series_Widget extends Widget_Base {

	/** @return string */
	public function get_name() {
		return 'lephare-predications-series';
	}

	/** @return string */
	public function get_title() {
		return 'Séries de prédications';
	}

	/** @return string */
	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	/** @return string[] */
	public function get_categories() {
		return array( 'lephare' );
	}

	/** @return string[] */
	public function get_keywords() {
		return array( 'série', 'serie', 'prédication', 'lephare' );
	}

	/** @return string[] */
	public function get_style_depends(): array {
		return array( 'lephare-predications' );
	}

	/**
	 * Réglages.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_contenu', array( 'label' => 'Contenu' ) );
		$this->add_control(
			'page',
			array(
				'label'       => 'Page de la liste',
				'type'        => Controls_Manager::URL,
				'description' => 'Laisser vide si la liste des prédications est sur cette même page.',
				'options'     => false,
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'style_series', array( 'label' => 'Cartes des séries', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control(
			'colonnes',
			array(
				'label'     => 'Colonnes',
				'type'      => Controls_Manager::SELECT,
				'options'   => array( '' => 'Automatique', '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
				'default'   => '',
				'selectors' => array( '{{WRAPPER}} .lpp-series' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
			)
		);
		$this->add_control( 'c_voile', array( 'label' => 'Couleur du voile sur l’image', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .lpp-serie' => '--lpp-voile: {{VALUE}};' ), 'description' => 'Foncé de préférence : le texte est blanc.' ) );
		$this->add_control(
			'hauteur',
			array(
				'label'      => 'Hauteur des cartes',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 140, 'max' => 400 ) ),
				'selectors'  => array( '{{WRAPPER}} .lpp-serie__lien' => 'min-height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typo_nom', 'label' => 'Typographie du nom', 'selector' => '{{WRAPPER}} .lpp-serie__nom' ) );
		$this->end_controls_section();
	}

	/**
	 * Affichage.
	 */
	protected function render() {
		$s = $this->get_settings_for_display();
		echo lephare_pred_series_html( array( 'page' => $s['page']['url'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans la fonction.
	}
}
