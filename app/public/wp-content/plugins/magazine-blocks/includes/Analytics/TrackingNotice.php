<?php
/**
 * Class TrackingNotice.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\Analytics;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Traits\Singleton;

/**
 * Prompts the site owner, 24 hours after activation, to allow or decline anonymous
 * usage tracking. Built independently of the bundled ThemeGrill SDK's own consent
 * notice, which never fires because `UsageTracking::force_opt_out_default()` gives
 * its backing option a non-empty value on every bootstrap.
 *
 * @since 1.8.8
 */
class TrackingNotice {

	use Singleton;

	/**
	 * User meta key storing this notice's per-user state (`decided`, `dismiss_count`).
	 *
	 * @since 1.8.8
	 */
	const META_KEY = '_magazine_blocks_tracking_notice';

	/**
	 * Number of times the notice can be closed with the "x" before it hides for good.
	 *
	 * @since 1.8.8
	 */
	const MAX_DISMISSALS = 3;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 *
	 * @since 1.8.8
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'admin_head', array( $this, 'notice_scripts' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
		add_action( 'wp_ajax_magazine_blocks_tracking_notice_dismiss', array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Tracking-consent notice markup.
	 *
	 * @since 1.8.8
	 * @return void
	 */
	public function notice() {
		if ( ! $this->should_show_notice() ) {
			return;
		}
		?>
		<div class="notice mzb-tracking-notice">
			<div class="mzb-tracking-notice-logo">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
					aria-hidden="true" focusable="false">
					<rect width="24" height="24" fill="white"></rect>
					<path d="M12 18.7957H4.60217V5.20432L12 9.82797V18.7957Z" fill="#690AA0"></path>
					<path d="M19.4194 18.7957H12V9.82797L19.4194 5.20432V18.7957Z" fill="#8D42CE"></path>
					<path d="M24 24H0V0H24V24ZM1.07527 22.9247H22.9247V1.07527H1.07527V22.9247Z" fill="#690AA0"></path>
				</svg>
			</div>
			<div class="mzb-tracking-notice-content">
				<h3 class="mzb-tracking-notice-title">
					<?php esc_html_e( 'Help us improve Magazine Blocks', 'magazine-blocks' ); ?>
				</h3>
				<p class="mzb-tracking-notice-description">
					<?php esc_html_e( 'Would you like to share anonymous usage data (such as which blocks you use, and your server environment) to help us prioritize fixes and features? No personal data, page content, or site URLs are ever collected.', 'magazine-blocks' ); ?>
				</p>
				<p class="mzb-tracking-notice-actions">
					<a href="#" class="button button-primary mzb-tracking-allow">
						<?php esc_html_e( 'Sure, allow it', 'magazine-blocks' ); ?>
					</a>
					<a href="#" class="button button-secondary mzb-tracking-decline">
						<?php esc_html_e( 'No thanks', 'magazine-blocks' ); ?>
					</a>
				</p>
			</div>
			<button type="button" class="notice-dismiss mzb-tracking-close">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'magazine-blocks' ); ?></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Whether the tracking-consent notice should be shown to the current user.
	 *
	 * @since 1.8.8
	 * @return bool
	 */
	private function should_show_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$activation_time = get_option( '_magazine_blocks_activation_time' );

		if ( ! $activation_time ) {
			add_option( '_magazine_blocks_activation_time', time() );
			return false;
		}

		if ( $activation_time > strtotime( '-1 day' ) ) {
			return false;
		}

		$state = get_user_meta( get_current_user_id(), self::META_KEY, true );

		if ( ! empty( $state['decided'] ) ) {
			return false;
		}

		if ( ! empty( $state['dismiss_count'] ) && $state['dismiss_count'] >= self::MAX_DISMISSALS ) {
			return false;
		}

		return true;
	}

	/**
	 * Tracking-consent notice styles and click handler.
	 *
	 * @since 1.8.8
	 * @return void
	 */
	public function notice_scripts() {
		if ( ! $this->should_show_notice() ) {
			return;
		}
		?>
		<style type="text/css">
			.mzb-tracking-notice {
				position: relative;
				display: flex;
				align-items: flex-start;
				gap: 16px;
				padding: 16px 40px 16px 12px;
				border-left-color: #690aa0 !important;
			}

			.mzb-tracking-notice-logo {
				flex-shrink: 0;
				display: flex;
				align-items: center;
				justify-content: center;
				width: 48px;
				height: 48px;
			}

			.mzb-tracking-notice-content {
				flex-grow: 1;
			}

			.mzb-tracking-notice-title {
				margin: 0 0 4px;
				color: #121212;
				font-size: 15px;
				line-height: 1.4;
			}

			.mzb-tracking-notice-description {
				margin: 0 0 12px;
				font-size: 13px;
				line-height: 1.6;
			}

			.mzb-tracking-notice-actions {
				margin: 0;
			}

			.mzb-tracking-notice-actions .button {
				margin-right: 8px;
			}

			.mzb-tracking-notice .mzb-tracking-close {
				position: absolute;
				top: 0;
				right: 1px;
				padding: 9px;
				background: none;
				border: none;
				cursor: pointer;
			}
		</style>

		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				$( document ).on(
					'click',
					'.mzb-tracking-notice .mzb-tracking-allow, .mzb-tracking-notice .mzb-tracking-decline, .mzb-tracking-notice .mzb-tracking-close',
					function ( e ) {
						e.preventDefault();

						var type = 'close';
						if ( $( this ).hasClass( 'mzb-tracking-allow' ) ) {
							type = 'allow';
						} else if ( $( this ).hasClass( 'mzb-tracking-decline' ) ) {
							type = 'decline';
						}

						$.post( ajaxurl, {
							action: 'magazine_blocks_tracking_notice_dismiss',
							security: '<?php echo esc_js( wp_create_nonce( 'magazine_blocks_tracking_notice_dismiss_nonce' ) ); ?>',
							type: type,
						} );

						$( '.mzb-tracking-notice' ).remove();
					}
				);
			} );
		</script>
		<?php
	}

	/**
	 * Handles Allow / Decline / Close actions sent from the notice.
	 *
	 * @since 1.8.8
	 * @return void
	 */
	public function handle_dismiss() {
		check_ajax_referer( 'magazine_blocks_tracking_notice_dismiss_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}

		$type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$user_id = get_current_user_id();
		$state   = get_user_meta( $user_id, self::META_KEY, true );
		$state   = is_array( $state ) ? $state : array();

		if ( 'allow' === $type || 'decline' === $type ) {
			update_option( UsageTracking::OPT_IN_OPTION, 'allow' === $type ? 'yes' : 'no' );
			$state['decided'] = true;
		} elseif ( 'close' === $type ) {
			$state['dismiss_count'] = ( isset( $state['dismiss_count'] ) ? (int) $state['dismiss_count'] : 0 ) + 1;
		}

		update_user_meta( $user_id, self::META_KEY, $state );

		wp_send_json_success();
	}
}
