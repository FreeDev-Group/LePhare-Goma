<?php
/**
 * Widget Elementor « Galerie » : toute la page /galerie/, réglable dans Elementor.
 *
 * Onglet Contenu : généré à partir de lephare_gal_schema_page() (bandeau, albums, messages).
 * Onglet Style : couleurs, typographies, colonnes et arrondis.
 *
 * @package LePhare_Galerie
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * Widget « Galerie ».
 */
class LePhare_Galerie_Widget extends Widget_Base {

	/** @return string */
	public function get_name() {
		return 'lephare-galerie';
	}

	/** @return string */
	public function get_title() {
		return 'Galerie';
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
		return array( 'galerie', 'album', 'photos', 'images', 'lephare' );
	}

	/** @return string[] */
	public function get_style_depends(): array {
		return array( 'lephare-galerie' );
	}

	/** @return string[] */
	public function get_script_depends(): array {
		return array( 'lephare-galerie' );
	}

	/**
	 * Réglages.
	 */
	protected function register_controls() {
		$categories = array();
		foreach ( lephare_gal_categories( true ) as $slug => $t ) {
			$categories[ $slug ] = $t->name;
		}

		foreach ( lephare_gal_schema_page() as $id => $section ) {
			$this->start_controls_section( 'section_' . $id, array( 'label' => $section['label'] ) );
			foreach ( $section['champs'] as $cle => $c ) {
				list( $type, $label, $defaut ) = $c;
				$commun = array( 'label' => $label, 'label_block' => true );
				if ( ! empty( $c[4] ) ) {
					$commun['condition'] = $c[4];
				}
				switch ( $type ) {
					case 'oui':
						$ctrl = array( 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Oui', 'label_off' => 'Non', 'return_value' => 'yes', 'default' => $defaut ? 'yes' : '', 'label_block' => false );
						break;
					case 'nombre':
						$ctrl = array( 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 48, 'default' => $defaut, 'label_block' => false );
						break;
					case 'media':
						$ctrl = array( 'type' => Controls_Manager::MEDIA );
						break;
					case 'categories':
						$ctrl = array( 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'options' => $categories );
						break;
					case 'zone':
						$ctrl = array( 'type' => Controls_Manager::TEXTAREA, 'default' => $defaut, 'rows' => 3 );
						break;
					default:
						$ctrl = array( 'type' => Controls_Manager::TEXT, 'default' => $defaut );
				}
				$this->add_control( $cle, array_merge( $commun, $ctrl ) );
			}
			$this->end_controls_section();
		}

		$racine = '{{WRAPPER}} .lpg';
		$this->start_controls_section( 'style_couleurs', array( 'label' => 'Couleurs', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control(
			'aide_couleurs',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Gardez un contraste suffisant : au moins 4,5:1 entre un texte et son fond.',
				'content_classes' => 'elementor-descriptor',
			)
		);
		foreach ( array(
			'c_encre'   => array( 'Bleu marine (titres, onglet actif)', '--lpg-encre' ),
			'c_texte'   => array( 'Gris des textes', '--lpg-texte' ),
			'c_vert'    => array( 'Vert (catégories)', '--lpg-vert' ),
			'c_sauge'   => array( 'Vert doux (accents)', '--lpg-sauge' ),
			'c_section' => array( 'Fond de la section des albums', '--lpg-fond-section' ),
		) as $id => $c ) {
			$this->add_control( $id, array( 'label' => $c[0], 'type' => Controls_Manager::COLOR, 'selectors' => array( $racine => $c[1] . ': {{VALUE}};' ) ) );
		}
		$this->add_control( 'c_bandeau', array( 'label' => 'Fond du bandeau', 'type' => Controls_Manager::COLOR, 'selectors' => array( $racine . ' .lpg-hero' => 'background-color: {{VALUE}};' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'style_typo', array( 'label' => 'Typographie', 'tab' => Controls_Manager::TAB_STYLE ) );
		foreach ( array(
			'typo_h1'     => array( 'Titre de la page (H1)', $racine . ' .lpg-hero h1.lpg-hero__titre' ),
			'typo_intro'  => array( 'Introduction', $racine . ' .lpg-hero p.lpg-hero__intro' ),
			'typo_titre'  => array( 'Titre de la liste', $racine . ' h2.lpg-tete__titre' ),
			'typo_cartes' => array( 'Titres des albums', $racine . ' .lpg-carte__titre' ),
		) as $id => $t ) {
			$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $id, 'label' => $t[0], 'selector' => $t[1] ) );
		}
		$this->end_controls_section();

		$this->start_controls_section( 'style_mise_en_page', array( 'label' => 'Mise en page', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control(
			'colonnes',
			array(
				'label'     => 'Colonnes de la grille d’albums',
				'type'      => Controls_Manager::SELECT,
				'options'   => array( '' => 'Automatique', '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
				'default'   => '',
				'selectors' => array( $racine . ' .lpg-albums .lpg-grille' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
			)
		);
		$this->add_control(
			'rayon',
			array(
				'label'      => 'Arrondi des cartes',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 32, 'step' => 2 ) ),
				'selectors'  => array( $racine => '--lpg-rayon: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'pad_bandeau',
			array(
				'label'              => 'Marges intérieures du bandeau',
				'type'               => Controls_Manager::DIMENSIONS,
				'size_units'         => array( 'px' ),
				'allowed_dimensions' => 'vertical',
				'selectors'          => array( $racine . ' .lpg-hero' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Affichage (même code que le shortcode).
	 */
	protected function render() {
		$s = $this->get_settings_for_display();
		$o = array();
		foreach ( array_keys( lephare_gal_defauts( lephare_gal_schema_page() ) ) as $cle ) {
			if ( array_key_exists( $cle, $s ) ) {
				$o[ $cle ] = $s[ $cle ];
			}
		}
		echo lephare_gal_rendu( $o ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML échappé dans lephare_gal_rendu().
	}
}
