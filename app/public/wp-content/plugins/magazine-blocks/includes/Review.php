<?php
/**
 * Class Review.
 *
 * @since TBD
 * @package Magazine Blocks
 */

namespace MagazineBlocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Traits\Singleton;

/**
 * Class Review.
 */
class Review {


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
	 * @since 1.0.3
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'admin_head', array( $this, 'review_notice_scripts' ) );
		add_action( 'admin_notices', array( $this, 'review_notice' ) );
		add_action( 'wp_ajax_magazine_blocks_review_notice_dismiss', array( $this, 'review_notice_dismiss' ) );
	}

	/**
	 * Review notice markup.
	 *
	 * @since 1.0.3
	 * @return void
	 */
	public function review_notice() {
		if ( ! $this->maybe_show_review_notice() ) {
			return;
		}
		?>
		<div class="notice mzb-notice mzb-review-notice">
			<div class="mzb-notice-logo">
				<svg width="120" height="120" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
					class="mzb-settings-icon" aria-hidden="true" focusable="false">
					<rect width="24" height="24" fill="white"></rect>
					<path d="M12 18.7957H4.60217V5.20432L12 9.82797V18.7957Z" fill="#690AA0"></path>
					<path d="M19.4194 18.7957H12V9.82797L19.4194 5.20432V18.7957Z" fill="#8D42CE"></path>
					<path d="M24 24H0V0H24V24ZM1.07527 22.9247H22.9247V1.07527H1.07527V22.9247Z" fill="#690AA0"></path>
				</svg>
			</div>
			<div class="mzb-notice-content">
				<h3 class="mzb-notice-title">
					<?php esc_html_e( 'Howdy, Admin 👋', 'magazine-blocks' ); ?>
				</h3>
				<p class="mzb-notice-description">
					<?php
					printf(
						/* Translators: 1: Plugin name, 2: Benefit, 3: Break tag, 4: Smile icon */
						esc_html__(
							'Hope your experience with the %1$s plugin is going well. Please share your valuable review with us. %2$s %3$s Your review will encourage us to release updates regularly with new features and bug fixes so that you can continue using the plugin without issues. %4$s',
							'magazine-blocks'
						),
						'<strong>Magazine Blocks</strong>',
						'',
						'<h2>What benefit would you have?</h2>',
						''
					)
					?>
				</p>
				<p class="mzb-notice-actions">
					<a href="https://wordpress.org/support/plugin/magazine-blocks/reviews?rate=5#new-post" target="_blank"
						rel="noopener noreferrer" class="button button-primary mzb-leave-review">
						<span class="dashicons dashicons-external"></span>
						<?php esc_html_e( 'Sure, I\'d love to!', 'magazine-blocks' ); ?>
					</a>
					<a href="#" class="button button-secondary mzb-remind-me-later"><span
							class="dashicons dashicons-smiley"></span>
						<?php esc_html_e( 'Remind me later', 'magazine-blocks' ); ?>
					</a>
					<a href="#" class="button button-secondary mzb-reviewed-already"><span
							class="dashicons dashicons-dismiss"></span>
						<?php esc_html_e( 'I already did', 'magazine-blocks' ); ?>
					</a>
					<a href="https://wpblockart.com/contact/" class="button button-secondary mzb-have-query" target="_blank"
						rel="noopener noreferrer"><span class="dashicons dashicons-testimonial"></span>
						<?php esc_html_e( 'I have a query', 'magazine-blocks' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Maybe show review notice.
	 *
	 * @since 1.0.3
	 * @return bool True or false.
	 */
	private function maybe_show_review_notice() {
		$user_id         = get_current_user_id();
		$activation_time = get_option( '_magazine_blocks_activation_time' );

		if ( ! $activation_time ) {
			update_option( '_magazine_blocks_activation_time', time() );
			return false;
		}

		$review = get_user_meta( $user_id, '_magazine_blocks_review', true );

		if (
			$activation_time > strtotime( '-7 day' ) ||
			( isset( $review['partial_dismiss'] ) && ( $review['partial_dismiss'] > strtotime( '-7 day' ) ) ) ||
			( isset( $review['dismiss'] ) && $review['dismiss'] )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Review notice scripts.
	 *
	 * @return void
	 */
	public function review_notice_scripts() {
		if ( ! $this->maybe_show_review_notice() ) {
			return;
		}
		?>
		<style type="text/css">
			.mzb-notice {
				display: flex;
				align-items: flex-start;
				border-left-color: #690aa0 !important;
				padding: 24px;
			}

			.mzb-notice .mzb-notice-logo {
				margin-right: 20px;
				display: flex;
				align-items: center;
				justify-content: center;
				height: 95px;
				width: 95px;;
			}

			.mzb-notice .smile-icon {
				background: #e7e94b;
				padding: 2px;
				font-size: 18px;
				border-radius: 50%
			}

			.mzb-notice .mzb-notice-content h3 {
				margin: 0;
				color: #121212;
				font-size: 20px;
				line-height: 1.5
			}

			.mzb-notice .mzb-notice-content p {
				margin-top: 4px;
				margin-bottom: 8px;
				padding: 0;
				font-size: 14px;
			}

			.mzb-notice .mzb-notice-content h2 {
				margin-bottom: 4px;
				margin-top: 8px;
				color: #121212;
				font-size: 16px;
				font-weight: 600;
				line-height: 1.8;
			}

			.mzb-notice .mzb-notice-content .mzb-notice-actions {
				margin-top: 18px;
				margin-bottom: 0;
			}

			.mzb-notice .mzb-notice-content .button {
				margin-right: 5px;
			}

			.mzb-notice .mzb-notice-content .button .dashicons {
				margin: 3px 4px 0 0;
			}

			.mzb-notice .mzb-notice-content .button-secondary {
				color: #8D42CE;
				border-radius: 2px;
				border: 1px solid #8D42CE;
				background: #FFF;
			}

			.mzb-notice .mzb-notice-content a {
				font-size: 14px;
			}

			.mzb-notice .mzb-notice-content .button-primary {
				background-color: #8D42CE;
				border: 1px solid #8D42CE;
			}

			.mzb-notice-description strong {
				color: #121212;
			}

			.mzb-notice-content {
				width: 1000px;
				line-height: 1.8;
			}
		</style>

		<script type="text/javascript">
			jQuery(document).ready(function (t) { t(document).on("click", ".mzb-notice .button:not(.mzb-have-query)", function (e) { t(this).hasClass("mzb-leave-review") || e.preventDefault(); var a = { action: "magazine_blocks_review_notice_dismiss", security: "<?php echo esc_js( wp_create_nonce( 'magazine_blocks_review_notice_dismiss_nonce' ) ); ?>", type: "dismiss" }; t(this).hasClass("mzb-remind-me-later") && (a.type = "partial_dismiss"), t.post(ajaxurl, a), t(".mzb-notice").remove() }) });
		</script>
		<?php
	}

	/**
	 * Dismiss review notice.
	 *
	 * @since 1.0.3
	 * @return void
	 */
	public function review_notice_dismiss() {
		check_ajax_referer( 'magazine_blocks_review_notice_dismiss_nonce', 'security' );

		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$data = array();

		if ( 'dismiss' === $type ) {
			$data['dismiss'] = true;
		}

		if ( 'partial_dismiss' === $type ) {
			$data['partial_dismiss'] = time();
		}

		update_user_meta( get_current_user_id(), '_magazine_blocks_review', $data );
	}
}
