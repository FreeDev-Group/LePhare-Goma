<?php
/**
 *
 */
namespace MagazineBlocks;

use MagazineBlocks\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

class MaintenanceMode {

	use Singleton;

	const MAINTENANCE_MODE = 'maintenance';
	const COMING_SOON      = 'coming-soon';

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'magazine_blocks_init', array( $this, 'init_hooks' ) );
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		if ( ! $this->is_maintenance_mode_enabled() ) {
			return;
		}
		add_action( 'template_redirect', array( $this, 'template_redirect' ), 11 );
		add_filter( 'template_include', array( $this, 'template_include' ) );
	}

	/**
	 * Get mode.
	 *
	 * @return string
	 */
	protected function get_mode() {
		return magazine_blocks_get_setting( 'maintenance-mode.mode', 'none' );
	}

	/**
	 * Is maintenance mode.
	 *
	 * @return boolean
	 */
	protected function is_maintenance_mode() {
		return $this->get_mode() === self::MAINTENANCE_MODE;
	}

	/**
	 * Is coming soon mode.
	 *
	 * @return boolean
	 */
	protected function is_coming_soon_mode() {
		return $this->get_mode() === self::COMING_SOON;
	}

	/**
	 * Get page id of maintenance page.
	 *
	 * @return int
	 */
	protected function get_maintenance_page_id() {
		return magazine_blocks_get_setting( 'maintenance-mode.maintenance-page', array() )['value'] ?? 0;
	}

	/**
	 * Is maintenance mode enabled
	 *
	 * @return boolean
	 */
	protected function is_maintenance_mode_enabled() {
		return ( $this->is_maintenance_mode() || $this->is_coming_soon_mode() ) && $this->get_maintenance_page_id() && ! is_user_logged_in();
	}

	/**
	 * Template redirect.
	 *
	 * @return void
	 */
	public function template_redirect() {
		if ( $this->is_maintenance_mode() ) {
			$this->set_maintenance_headers();
		}
		global $post, $wp_query;

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		$post     = get_post( $this->get_maintenance_page_id() );
		$wp_query = new \WP_Query();
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		$wp_query->query(
			array(
				'p'         => $this->get_maintenance_page_id(),
				'post_type' => 'page',
			)
		);
	}

	/**
	 * set maintenance header.
	 *
	 * @return void
	 */
	protected function set_maintenance_headers() {
		$protocol = wp_get_server_protocol();
		header( "$protocol 503 Service Unavailable", true, 503 );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Retry-After: 600' );
	}

	/**
	 * Include template.
	 *
	 * @return void
	 */
	public function template_include() {
		$this->template();
	}

	/**
	 * Maintenance template.
	 *
	 * @return void
	 */
	protected function template() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta charset="UTF-8" />
				<meta name="viewport" content="width=device-width, initial-scale=1.0" />
				<title><?php the_title(); ?></title>
				<?php wp_head(); ?>
			</head>
			<body <?php body_class(); ?>>
				<?php the_content(); ?>
				<?php wp_footer(); ?>
			</body>
		</html>
		<?php
	}
}
