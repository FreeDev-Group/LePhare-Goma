<?php
/**
 * AdminMenu class.
 *
 * @package Magazine Blocks
 * @since 1.0.0
 */

namespace MagazineBlocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Traits\Singleton;

/**
 * Admin class.
 */
class Admin {

	use Singleton;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'init_menus' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
		add_filter( 'update_footer', array( $this, 'admin_footer_version' ), 11 );
		add_action( 'in_admin_header', array( $this, 'hide_admin_notices' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
	}

	/**
	 * Init menus.
	 *
	 * @since 1.0.0
	 */
	public function init_menus() {
		$magazine_blocks_page = add_menu_page(
			esc_html__( 'Magazine Blocks', 'magazine-blocks' ),
			esc_html__( 'Magazine Blocks', 'magazine-blocks' ),
			'manage_options',
			'magazine-blocks',
			array( $this, 'markup' ),
			'data:image/svg+xml;base64,' . base64_encode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Used for SVG icon encoding
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#F3F1F1"><path d="M10.155 15.105H4.803V5.273l5.352 3.343v6.49Zm5.341 0h-5.341v-6.49l5.341-3.342v9.832Z"/><path d="M18 18.057H2V2h16v16.057Zm-15.276-.723h14.564V2.724H2.724v14.61Z"/></svg>' ) // phpcs:ignore
		);

		$submenus = $this->get_submenus();

		uasort(
			$submenus,
			function ( $a, $b ) {
				if ( $a['position'] === $b['position'] ) {
					return 0;
				}
				return ( $a['position'] < $b['position'] ) ? -1 : 1;
			}
		);

		foreach ( $submenus as $slug => $submenu ) {
			add_submenu_page(
				$submenu['parent_slug'],
				$submenu['page_title'],
				$submenu['menu_title'],
				$submenu['capability'],
				'magazine-blocks#/' . $slug,
				$submenu['callback'],
				$submenu['position']
			);
		}

		add_action( "admin_print_scripts-$magazine_blocks_page", array( $this, 'enqueue' ) );
		remove_submenu_page( 'magazine-blocks', 'magazine-blocks' );
	}

	/**
	 * Get submenus.
	 *
	 * @return array
	 */
	private function get_submenus() {
		$submenus = array(
			'dashboard'    => array(
				'page_title' => __( 'Dashboard', 'magazine-blocks' ),
				'menu_title' => __( 'Dashboard', 'magazine-blocks' ),
				'position'   => 10,
			),
			'blocks'       => array(
				'page_title' => __( 'Blocks', 'magazine-blocks' ),
				'menu_title' => __( 'Blocks', 'magazine-blocks' ),
				'position'   => 20,
			),
			'site-builder' => array(
				'page_title' => __( 'Template Builder', 'magazine-blocks' ),
				'menu_title' => __( 'Template Builder', 'magazine-blocks' ),
				'position'   => 21,
			),
			// 'products'     => [
			// 'page_title' => __( 'Products', 'magazine-blocks' ),
			// 'menu_title' => __( 'Products', 'magazine-blocks' ),
			// 'position'   => 30,
			// ],
			'settings'     => array(
				'page_title' => __( 'Settings', 'magazine-blocks' ),
				'menu_title' => __( 'Settings', 'magazine-blocks' ),
				'position'   => 40,
			),
			'free-vs-pro'  => array(
				'page_title' => __( 'Free vs Pro', 'magazine-blocks' ),
				'menu_title' => __( 'Free vs Pro', 'magazine-blocks' ),
				'position'   => 45,
			),
			'help'         => array(
				'page_title' => __( 'Help', 'magazine-blocks' ),
				'menu_title' => __( 'Help', 'magazine-blocks' ),
				'position'   => 50,
			),
		);

		$submenus = apply_filters( 'magazine_blocks_admin_submenus', $submenus );
		$submenus = array_map(
			function ( $submenu ) {
				return wp_parse_args(
					$submenu,
					array(
						'page_title'  => '',
						'menu_title'  => '',
						'parent_slug' => 'magazine-blocks',
						'capability'  => 'manage_options',
						'position'    => 1000,
						'callback'    => array( $this, 'markup' ),
					)
				);
			},
			$submenus
		);

		return $submenus;
	}

	/**
	 * Markup.
	 *
	 * @since 1.0.0
	 */
	public function markup() {
		echo '<div id="mzb"></div>';
	}

	/**
	 * Enqueue.
	 *
	 * @since 1.0.0
	 */
	public function enqueue() {
		wp_enqueue_script( 'magazine-blocks-admin' );
	}

	/**
	 * Change admin footer text on Magazine Blocks page.
	 *
	 * @param string $text Admin footer text.
	 * @return string Admin footer text.
	 */
	public function admin_footer_text( string $text ): string {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_magazine_blocks' !== $screen->id ) {
			return $text;
		}

		return __( 'Thank you for creating with Magazine Blocks.', 'magazine-blocks' );
	}

	/**
	 * Override WordPress version with plugin version.
	 *
	 * @param string $version Version text.
	 *
	 * @return string Version text.
	 */
	public function admin_footer_version( string $version ): string {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_magazine_blocks' !== $screen->id ) {
			return $version;
		}

		/* translators: %s: Plugin version number */
		return sprintf( __( 'Version %s', 'magazine-blocks' ), MAGAZINE_BLOCKS_VERSION );
	}

	/**
	 * Redirecting user to dashboard page.
	 */
	public function admin_redirects() {
		if ( get_option( '_magazine_blocks_activation_redirect' ) && apply_filters( 'magazine_blocks_activation_redirect', true ) ) {
			update_option( '_magazine_blocks_activation_redirect', false );
			wp_safe_redirect( admin_url( 'index.php?page=magazine-blocks#/getting-started' ) );
			exit;
		}
	}

	/**
	 * Hide admin notices from Magazine Blocks admin pages.
	 *
	 * @since 1.0.0
	 */
	public function hide_admin_notices() {

		// Bail if we're not on a Magazine Blocks screen or page.
		if ( empty( $_REQUEST['page'] ) || false === strpos( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), 'magazine-blocks' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		global $wp_filter;
		$ignore_notices = apply_filters( 'magazine_blocks_ignore_hide_admin_notices', array() );

		foreach ( array( 'user_admin_notices', 'admin_notices', 'all_admin_notices' ) as $wp_notice ) {
			if ( empty( $wp_filter[ $wp_notice ] ) ) {
				continue;
			}

			$hook_callbacks = $wp_filter[ $wp_notice ]->callbacks;

			if ( empty( $hook_callbacks ) || ! is_array( $hook_callbacks ) ) {
				continue;
			}

			foreach ( $hook_callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $callback ) {
					if ( ! empty( $name ) && in_array( $name, $ignore_notices, true ) ) {
						continue;
					}
					if (
						! empty( $callback['function'] ) &&
						! is_a( $callback['function'], '\Closure' ) &&
						isset( $callback['function'][0], $callback['function'][1] ) &&
						is_object( $callback['function'][0] ) &&
						in_array( $callback['function'][1], $ignore_notices, true )
					) {
						continue;
					}
					unset( $wp_filter[ $wp_notice ]->callbacks[ $priority ][ $name ] );
				}
			}
		}
	}
}
