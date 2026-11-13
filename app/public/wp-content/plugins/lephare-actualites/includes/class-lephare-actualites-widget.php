<?php
/**
 * Widget Elementor « Actualités » : toute la page Actualités, réglable partie par partie dans Elementor.
 *
 * Onglet Contenu : généré à partir de lephare_actu_schema_page() (textes, sections à afficher ou masquer,
 * photo de fond, catégories, nombre d'articles, messages, animations).
 * Onglet Style : couleurs, typographies, espacements et arrondis (variables CSS de la page).
 *
 * @package LePhare_Actualites
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * Widget « Actualités ».
 */
class LePhare_Actualites_Widget extends Widget_Base {

	/** @return string */
	public function get_name() {
		return 'lephare-actualites';
	}

	/** @return string */
	public function get_title() {
		return 'Actualités';
	}

	/** @return string */
	public function get_icon() {
		return 'eicon-post-list';
	}

	/** @return string[] */
	public function get_categories() {
		return array( 'lephare' );
	}

	/** @return string[] */
	public function get_keywords() {
		return array( 'actualités', 'actualites', 'articles', 'blog', 'lephare' );
	}

	/** @return string[] */
	public function get_style_depends(): array {
		return array( 'lephare-actualites' );
	}

	/** @return string[] */
	public function get_script_depends(): array {
		return array( 'lephare-actualites' );
	}

	/**
	 * Réglages : contenu (depuis le schéma) puis style.
	 */
	protected function register_controls() {
		$categories = array();
		foreach ( lephare_actu_categories( array(), true ) as $slug => $t ) {
			$categories[ $slug ] = $t->name . ' (' . $t->count . ')';
		}

		/* -------- Contenu : une section par partie de la page -------- */
		foreach ( lephare_actu_schema_page() as $id => $section ) {
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
						$ctrl = array( 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 24, 'default' => $defaut, 'label_block' => false );
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

		/* -------- Style : couleurs -------- */
		$racine = '{{WRAPPER}} .lpa';
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
			'c_encre'   => array( 'Bleu marine (titres, textes forts, onglet actif)', '--lpa-encre' ),
			'c_texte'   => array( 'Gris des textes', '--lpa-texte' ),
			'c_vert'    => array( 'Vert (catégories)', '--lpa-vert' ),
			'c_sauge'   => array( 'Vert doux (accents, bouton du bandeau)', '--lpa-sauge' ),
			'c_bouton'  => array( 'Vert du bouton « Lire l’article »', '--lpa-vert-bouton' ),
			'c_section' => array( 'Fond de la section des articles', '--lpa-fond-section' ),
			'c_filet'   => array( 'Bordures et filets', '--lpa-filet' ),
		) as $id => $c ) {
			$this->add_control( $id, array( 'label' => $c[0], 'type' => Controls_Manager::COLOR, 'selectors' => array( $racine => $c[1] . ': {{VALUE}};' ) ) );
		}
		$this->add_control( 'c_bandeau', array( 'label' => 'Fond du bandeau', 'type' => Controls_Manager::COLOR, 'selectors' => array( $racine . ' .lpa-hero' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control(
			'voile',
			array(
				'label'      => 'Opacité du voile sur la photo du bandeau',
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0.4, 'max' => 1, 'step' => 0.05 ) ),
				'size_units' => array( 'px' ),
				'selectors'  => array( $racine . ' .lpa-hero__photo::after' => 'opacity: {{SIZE}};' ),
				'condition'  => array( 'hero_photo' => 'yes' ),
				'description' => 'Plus bas = photo plus visible, mais le texte blanc doit rester lisible.',
			)
		);
		$this->end_controls_section();

		/* -------- Style : typographies -------- */
		$this->start_controls_section( 'style_typo', array( 'label' => 'Typographie', 'tab' => Controls_Manager::TAB_STYLE ) );
		foreach ( array(
			'typo_h1'     => array( 'Titre de la page (H1)', $racine . ' .lpa-hero h1.lpa-entete__titre' ),
			'typo_intro'  => array( 'Introduction du bandeau', $racine . ' .lpa-hero p.lpa-entete__intro' ),
			'typo_titres' => array( 'Titres de section', $racine . ' .lpa-section-tete h2.lpa-section-tete__titre, ' . $racine . ' .lpa-liste h2.lpa-liste__titre' ),
			'typo_une'    => array( 'Titre « À la une »', $racine . ' .lpa-une .lpa-une__texte h3.lpa-une__titre' ),
			'typo_cartes' => array( 'Titres des cartes', $racine . ' .lpa-grille .lpa-carte__titre' ),
			'typo_textes' => array( 'Extraits', $racine . ' p.lpa-une__extrait, ' . $racine . ' .lpa-grille p.lpa-carte__extrait' ),
			'typo_btn'    => array( 'Boutons', $racine . ' a.lpa-bouton, ' . $racine . ' a.lpa-bouton-doux' ),
		) as $id => $t ) {
			$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $id, 'label' => $t[0], 'selector' => $t[1] ) );
		}
		$this->end_controls_section();

		/* -------- Style : espacements et arrondis -------- */
		$this->start_controls_section( 'style_espaces', array( 'label' => 'Espacements et arrondis', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control(
			'pad_bandeau',
			array(
				'label'      => 'Marges intérieures du bandeau',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'allowed_dimensions' => 'vertical',
				'selectors'  => array( $racine . ' .lpa-hero, ' . $racine . ' .lpa-hero:has(+ .lpa-rubriques)' => 'padding-top: {{TOP}}{{UNIT}}; padding-bottom: {{BOTTOM}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'espace_sections',
			array(
				'label'      => 'Espace entre les sections',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 32, 'max' => 160, 'step' => 8 ) ),
				'selectors'  => array( $racine => '--lpa-section: {{SIZE}}{{UNIT}};', $racine . ' .lpa-une' => 'padding-bottom: {{SIZE}}{{UNIT}};', $racine . ' .lpa-liste' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'rayon',
			array(
				'label'      => 'Arrondi des cartes',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 32, 'step' => 2 ) ),
				'selectors'  => array( $racine => '--lpa-rayon: {{SIZE}}{{UNIT}};', $racine . ' .lpa-une__article' => 'border-radius: calc({{SIZE}}{{UNIT}} + 8px);' ),
			)
		);
		$this->add_responsive_control(
			'colonnes',
			array(
				'label'     => 'Colonnes de la grille',
				'type'      => Controls_Manager::SELECT,
				'options'   => array( '' => 'Automatique', '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
				'default'   => '',
				'selectors' => array( $racine . ' .lpa-liste .lpa-grille:not(.lpa-grille--une-carte):not(.lpa-grille--deux)' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
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
		foreach ( array_keys( lephare_actu_options_defaut() ) as $cle ) {
			if ( array_key_exists( $cle, $s ) ) {
				$o[ $cle ] = $s[ $cle ];
			}
		}
		echo lephare_actu_rendu( $o ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML échappé dans lephare_actu_rendu().
	}
}
