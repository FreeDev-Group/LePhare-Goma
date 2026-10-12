<?php

/**
 * Magazine Blocks plugin main class.
 *
 * @since 1.0.0
 * @package Magazine Blocks
 */

namespace MagazineBlocks;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Analytics\BlockUsageTracker;
use MagazineBlocks\Analytics\TrackingNotice;
use MagazineBlocks\Analytics\UsageTracking;
use MagazineBlocks\RestApi\RestApi;
use MagazineBlocks\Traits\Singleton;

/**
 * Magazine Blocks setup.
 *
 * Include and initialize necessary files and classes for the plugin.
 *
 * @since   1.0.0
 */
final class MagazineBlocks {



	use Singleton;

	/**
	 * @var Utils
	 */
	public $utils;

	/**
	 * Plugin Constructor.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function __construct() {
		$this->init_props();
		Activation::init();
		Deactivation::init();
		Update::init();
		RestApi::init();
		Admin::init();
		Blocks::init();
		Ajax::init();
		ScriptStyle::init();
		Review::init();
		MaintenanceMode::init();
		UsageTracking::init();
		TrackingNotice::init();
		BlockUsageTracker::init();
		$this->init_hooks();
	}

	/**
	 * Init properties.
	 *
	 * @return void
	 */
	private function init_props() {
		$this->utils = Utils::init();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'after_wp_init' ), 0 );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'check_filetype_and_ext' ), 10, 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_global_styles' ) );
		add_filter( 'template_include', array( $this, 'mzb_global_template_override' ), 50 );
	}

	public function enqueue_global_styles() {
		$global_styles = magazine_blocks_generate_global_styles();
		$global_styles->enqueue_fonts();
		$global_styles->enqueue();
	}

	/**
	 * Override the template for specific conditions.
	 *
	 * This function checks if the current request is for a specific type of page
	 * (like front page, single post, archive, 404, or search) and returns a custom
	 * template if a matching builder template exists.
	 *
	 * @param string $template The current template being used.
	 * @return string The modified template path if a match is found, otherwise the original template.
	 */
	public function mzb_global_template_override( $template ) {
		$map = array(
			'is_front_page' => 'front',
			'is_single'     => 'single',
			'is_archive'    => 'archive',
			'is_404'        => '404',
			'is_search'     => 'search',
		);

		$header_template = get_posts(
			array(
				'post_type'   => 'mzb-builder-template',
				'meta_key'    => '_mzb_template',
				'meta_value'  => 'header',
				'post_status' => 'publish',
				'numberposts' => 1,
			)
		);

		$footer_template = get_posts(
			array(
				'post_type'   => 'mzb-builder-template',
				'meta_key'    => '_mzb_template',
				'meta_value'  => 'footer',
				'post_status' => 'publish',
				'numberposts' => 1,
			)
		);

		// Check if we have header or footer templates
		$has_header_or_footer = ! empty( $header_template ) || ! empty( $footer_template );

		// Initialize variables
		$matched_template = null;
		$template_type    = null;

		// Check for page template matches
		foreach ( $map as $check => $type ) {
			if ( function_exists( $check ) && $check() ) {
				$page_template = get_posts(
					array(
						'post_type'   => 'mzb-builder-template',
						'meta_key'    => '_mzb_template',
						'meta_value'  => $type,
						'post_status' => 'publish',
						'numberposts' => 1,
					)
				);

				if ( ! empty( $page_template ) ) {
					$matched_template = $page_template;
					$template_type    = $type;
					break;
				}
			}
		}

		// If we have a page template OR header/footer templates, use our custom template
		if ( $matched_template || $has_header_or_footer ) {

			// Set query vars for page template if found
			if ( $matched_template ) {
				set_query_var( 'mzb_current_template_id', $matched_template[0]->ID );
				set_query_var( 'mzb_template_type', $template_type );

				$id      = $matched_template[0]->ID;
				$content = get_post_field( 'post_content', $id );

				add_filter(
					'magazine_blocks_block_styles_id',
					function () use ( $id ) {
						return $id;
					}
				);

				add_filter(
					'magazine_blocks_content_for_css_generation',
					function () use ( $content ) {
						return $content;
					}
				);
			}

			// Always enqueue header/footer styles if they exist
			add_action(
				'wp_enqueue_scripts',
				function () use ( $header_template, $footer_template ) {
					// wp_head() runs before block markup exists here, so core's render-time style auto-enqueue never fires.
					wp_enqueue_style( 'magazine-blocks-blocks' );

					if ( ! class_exists( '\MagazineBlocks\BlockStyles' ) ) {
						return;
					}
					if ( count( $header_template ) > 0 ) {
						$header_blocks           = parse_blocks( get_post_field( 'post_content', $header_template[0]->ID ) );
						$header_processed_blocks = magazine_blocks_process_blocks( $header_blocks );
						$header_styles           = new \MagazineBlocks\BlockStyles( $header_processed_blocks, $header_template[0]->ID, true );

						$header_styles->enqueue_fonts();
						$header_styles->enqueue();
					}
					if ( count( $footer_template ) > 0 ) {
						$footer_blocks           = parse_blocks( get_post_field( 'post_content', $footer_template[0]->ID ) );
						$footer_processed_blocks = magazine_blocks_process_blocks( $footer_blocks );
						$footer_styles           = new \MagazineBlocks\BlockStyles( $footer_processed_blocks, $footer_template[0]->ID, true );

						$footer_styles->enqueue_fonts();
						$footer_styles->enqueue();
					}
				}
			);

			return MAGAZINE_BLOCKS_PLUGIN_DIR . '/includes/Templates/template-render.php';
		}

		return $template;
	}

	/**
	 * Enqueue styles for the template type.
	 *
	 * This function checks if a template of the specified type exists and enqueues
	 * its styles if it does.
	 *
	 * @param string $type The type of template to enqueue styles for (e.g., 'header', 'footer').
	 */
	private function enqueue_template_styles( $type ) {
		$template = get_posts(
			array(
				'post_type'   => 'mzb-builder-template',
				'meta_key'    => '_mzb_template',
				'meta_value'  => $type,
				'post_status' => 'publish',
				'numberposts' => 1,
			)
		);

		if ( ! empty( $template ) && class_exists( '\MagazineBlocks\BlockStyles' ) ) {
			$blocks           = parse_blocks( get_post_field( 'post_content', $template[0]->ID ) );
			$processed_blocks = magazine_blocks_process_blocks( $blocks );
			$styles           = new \MagazineBlocks\BlockStyles( $processed_blocks, $template[0]->ID, true );

			$styles->enqueue_fonts();
			$styles->enqueue();
		}
	}

	/**
	 * Returns the rendered builder template content as a string (escaped).
	 */
	public function render_template_to_string( $post_id ) {
		if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) {
			return '';
		}

		$post = get_post( $post_id );

		$content = get_post_field( 'post_content', $post_id );

		try {
			if ( class_exists( '\MagazineBlocks\BlockStyles' ) ) {
				$blocks = parse_blocks( $post->post_content );
				$blocks = magazine_blocks_process_blocks( $blocks );
				$css    = ( new \MagazineBlocks\BlockStyles( $blocks, $post->ID, true ) )->get_styles() ?? '';
				printf( '<style>%s</style>', $css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( \Exception $e ) {
			// Do nothing.
		}

		if ( function_exists( 'has_blocks' ) && has_blocks( $content ) && function_exists( 'parse_blocks' ) && function_exists( 'render_block' ) ) {
			return do_blocks( $content );
		}

		return '';
	}

	/**
	 * Echoes the rendered template content directly.
	 */
	public function render_template( $post_id ) {
		echo $this->render_template_to_string( $post_id );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function register_post_types() {
		$post_types = array(
			\MagazineBlocks\PostTypes\SiteBuilder::class,
		);
		foreach ( $post_types as $post_type ) {
			( new $post_type() )->register();
		}
	}

	/**
	 * Initialize Magazine Blocks when WordPress initializes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function after_wp_init() {
		/**
		 * Magazine Blocks before init.
		 *
		 * @since 1.0.0
		 */
		do_action( 'magazine_blocks_before_init' );
		$this->update_plugin_version();
		$this->load_text_domain();
		$this->register_post_types();
		/**
		 * Magazine Blocks init.
		 *
		 * Fires after Magazine Blocks has loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'magazine_blocks_init' );
	}

	/**
	 * Update the plugin version.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function update_plugin_version() {
		update_option( '_magazine_blocks_version', MAGAZINE_BLOCKS_VERSION );
	}

	/**
	 * Load plugin text domain.
	 */
	private static function load_text_domain() {
		load_plugin_textdomain( 'magazine-blocks', false, plugin_basename( dirname( MAGAZINE_BLOCKS_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Return valid filetype array for lottie json uploads.
	 *
	 * @param array  $value Filetype array.
	 * @param string $file Original file.
	 * @param string $filename Filename.
	 * @param array  $mimes Mimes array.
	 * @param string $real_mime Real mime type.
	 * @return array
	 */
	public function check_filetype_and_ext( $value, $file, $filename, $mimes, $real_mime ) {

		$wp_filetype = wp_check_filetype( $filename, $mimes );
		$ext         = $wp_filetype['ext'];
		$type        = $wp_filetype['type'];

		if ( 'json' !== $ext || 'application/json' !== $type || 'text/plain' !== $real_mime ) {
			return $value;
		}

		$value['ext']             = $wp_filetype['ext'];
		$value['type']            = $wp_filetype['type'];
		$value['proper_filename'] = $filename;

		return $value;
	}
}
