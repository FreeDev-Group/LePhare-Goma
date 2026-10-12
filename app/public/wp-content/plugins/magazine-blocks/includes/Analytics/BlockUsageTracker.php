<?php
/**
 * Incremental block usage tracker.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\Analytics;

defined( 'ABSPATH' ) || exit;

use MagazineBlocks\Traits\Singleton;

/**
 * Maintains a live, site-wide count of how many times each Magazine Blocks block is
 * used, updated incrementally on post save/trash/restore/delete rather than by
 * rescanning all content on every telemetry send.
 *
 * Modelled on the approach shipped by Elementor's usage module and Spectra/Ultimate
 * Addons for Gutenberg's incremental block tracker: diff a post's block counts
 * against its last-known snapshot (stored in postmeta) and apply only the delta to a
 * single aggregate option, so cost stays O(1) per edit regardless of site size.
 *
 * @since 1.8.6
 */
class BlockUsageTracker {

	use Singleton;

	/**
	 * Option holding the aggregate block-usage counts, keyed by full block name.
	 *
	 * @since 1.8.6
	 */
	const AGGREGATE_OPTION = '_magazine_blocks_usage_stats';

	/**
	 * Postmeta key holding a post's own last-known block-count snapshot.
	 *
	 * @since 1.8.6
	 */
	const SNAPSHOT_META_KEY = '_mzb_block_usage_counts';

	/**
	 * Option flagging whether the one-time historical backfill has already run.
	 *
	 * @since 1.8.6
	 */
	const BACKFILL_DONE_OPTION = '_magazine_blocks_usage_backfilled';

	/**
	 * Cron hook used to run the one-time backfill off the request thread.
	 *
	 * @since 1.8.6
	 */
	const BACKFILL_CRON_HOOK = 'magazine_blocks_backfill_usage_stats';

	/**
	 * Posts processed per `run_backfill()` invocation, keeping each cron-triggered
	 * batch well within a typical host's PHP execution-time limit regardless of how
	 * many posts a site has in total.
	 *
	 * @since 1.8.6
	 */
	const BACKFILL_BATCH_SIZE = 200;

	/**
	 * Registers lifecycle hooks.
	 *
	 * Handlers no-op internally when tracking isn't opted in, so hooks stay attached
	 * unconditionally rather than depending on option state at bootstrap time.
	 *
	 * @since 1.8.6
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'maybe_schedule_backfill' ) );
		add_action( self::BACKFILL_CRON_HOOK, array( $this, 'run_backfill' ) );

		add_action( 'save_post', array( $this, 'handle_save_post' ), 10, 2 );
		add_action( 'before_delete_post', array( $this, 'handle_post_removed' ) );
		add_action( 'wp_trash_post', array( $this, 'handle_post_removed' ) );
		add_action( 'untrash_post', array( $this, 'handle_post_restored' ) );
	}

	/**
	 * Diffs a saved post's block counts against its previous snapshot and applies
	 * only the delta to the aggregate. Skips autosaves, revisions, non-public post
	 * types, and re-diffing a save that didn't actually change post content.
	 *
	 * @since 1.8.6
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function handle_save_post( $post_id, $post ) {
		if ( ! UsageTracking::is_opted_in() ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, self::get_tracked_post_types(), true ) ) {
			return;
		}

		// Only publish/private counts as "used"; drafts and auto-drafts stay out of the aggregate.
		if ( ! in_array( $post->post_status, array( 'publish', 'private' ), true ) ) {
			$this->handle_post_removed( $post_id );
			return;
		}

		// Short-circuits repeat save_post calls for content that hasn't changed.
		static $processed_hashes = array();
		$content_hash            = md5( $post->post_content );
		if ( isset( $processed_hashes[ $post_id ] ) && $processed_hashes[ $post_id ] === $content_hash ) {
			return;
		}
		$processed_hashes[ $post_id ] = $content_hash;

		$previous_counts = self::get_snapshot( $post_id );
		$current_counts  = self::count_blocks( $post->post_content );

		self::apply_delta( $previous_counts, $current_counts );
		update_post_meta( $post_id, self::SNAPSHOT_META_KEY, $current_counts );
	}

	/**
	 * Removes a post's last-known contribution from the aggregate when it's trashed,
	 * unpublished, or permanently deleted. Clears the snapshot after subtracting so a
	 * later `before_delete_post` firing on an already-trashed post can't subtract the
	 * same counts a second time.
	 *
	 * @since 1.8.6
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function handle_post_removed( $post_id ) {
		if ( ! UsageTracking::is_opted_in() ) {
			return;
		}

		$previous_counts = self::get_snapshot( $post_id );
		if ( empty( $previous_counts ) ) {
			return;
		}

		self::apply_delta( $previous_counts, array() );
		delete_post_meta( $post_id, self::SNAPSHOT_META_KEY );
	}

	/**
	 * Re-adds a post's block counts to the aggregate when it's restored from trash.
	 *
	 * @since 1.8.6
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function handle_post_restored( $post_id ) {
		if ( ! UsageTracking::is_opted_in() ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$current_counts = self::count_blocks( $post->post_content );
		self::apply_delta( array(), $current_counts );
		update_post_meta( $post_id, self::SNAPSHOT_META_KEY, $current_counts );
	}

	/**
	 * Returns a post's last-known block-count snapshot.
	 *
	 * @since 1.8.6
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,int>
	 */
	private static function get_snapshot( $post_id ) {
		$snapshot = get_post_meta( $post_id, self::SNAPSHOT_META_KEY, true );
		return is_array( $snapshot ) ? $snapshot : array();
	}

	/**
	 * Counts every tracked block, including nested inner blocks, in post content.
	 *
	 * @since 1.8.6
	 *
	 * @param string $content Post content.
	 * @return array<string,int> Zero-filled counts for every currently registered
	 *                           Magazine Blocks block, plus whatever was actually found.
	 */
	private static function count_blocks( $content ) {
		$counts = array_fill_keys( self::get_tracked_block_names(), 0 );

		if ( empty( $content ) || ! has_blocks( $content ) ) {
			return $counts;
		}

		self::count_blocks_recursive( parse_blocks( $content ), $counts );

		return $counts;
	}

	/**
	 * Recursively tallies block occurrences, including inner blocks.
	 *
	 * @since 1.8.6
	 *
	 * @param array $blocks Parsed blocks.
	 * @param array $counts Reference to the running counts array.
	 * @return void
	 */
	private static function count_blocks_recursive( $blocks, &$counts ) {
		foreach ( $blocks as $block ) {
			$block_name = $block['blockName'] ?? '';

			if ( $block_name && array_key_exists( $block_name, $counts ) ) {
				++$counts[ $block_name ];
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::count_blocks_recursive( $block['innerBlocks'], $counts );
			}
		}
	}

	/**
	 * Returns the post types counted toward block-usage stats: every public post type
	 * plus the Site Builder's template post type, which is registered non-public
	 * (`'public' => false`, since it's never queried on the front end directly) but
	 * still holds real block content for headers, footers, and archive/single templates.
	 *
	 * @since 1.8.6
	 *
	 * @return string[]
	 */
	private static function get_tracked_post_types() {
		$post_types   = get_post_types( array( 'public' => true ), 'names' );
		$post_types[] = 'mzb-builder-template';
		return array_values( array_unique( $post_types ) );
	}

	/**
	 * Returns every currently registered Magazine Blocks block name, read from
	 * WordPress' own block type registry rather than a hand-maintained list, so it
	 * can never drift out of sync with the blocks the plugin actually ships.
	 *
	 * @since 1.8.6
	 *
	 * @return string[]
	 */
	private static function get_tracked_block_names() {
		$registered = \WP_Block_Type_Registry::get_instance()->get_all_registered();

		return array_values(
			array_filter(
				array_keys( $registered ),
				static function ( $block_name ) {
					return 0 === strpos( $block_name, 'magazine-blocks/' );
				}
			)
		);
	}

	/**
	 * Applies a per-post count delta to the site-wide aggregate: subtracts what the
	 * post previously contributed, adds what it contributes now, never letting an
	 * individual block's total drop below zero.
	 *
	 * @since 1.8.6
	 *
	 * @param array<string,int> $previous_counts Counts the post had before.
	 * @param array<string,int> $current_counts  Counts the post has now.
	 * @return void
	 */
	private static function apply_delta( $previous_counts, $current_counts ) {
		$aggregate   = self::get_aggregate();
		$block_names = array_unique( array_merge( array_keys( $previous_counts ), array_keys( $current_counts ) ) );

		foreach ( $block_names as $block_name ) {
			$old_count = isset( $previous_counts[ $block_name ] ) ? (int) $previous_counts[ $block_name ] : 0;
			$new_count = isset( $current_counts[ $block_name ] ) ? (int) $current_counts[ $block_name ] : 0;

			if ( $old_count === $new_count ) {
				continue;
			}

			if ( ! isset( $aggregate[ $block_name ] ) ) {
				$aggregate[ $block_name ] = 0;
			}

			$aggregate[ $block_name ] += ( $new_count - $old_count );

			if ( $aggregate[ $block_name ] < 0 ) {
				$aggregate[ $block_name ] = 0;
			}
		}

		update_option( self::AGGREGATE_OPTION, $aggregate, false );
	}

	/**
	 * Returns the current site-wide block-usage aggregate.
	 *
	 * @since 1.8.6
	 *
	 * @return array<string,int>
	 */
	public static function get_aggregate() {
		$aggregate = get_option( self::AGGREGATE_OPTION, array() );
		return is_array( $aggregate ) ? $aggregate : array();
	}

	/**
	 * Schedules the one-time historical backfill shortly after tracking is opted
	 * into, off the request thread. No-ops once the backfill has already run.
	 *
	 * @since 1.8.6
	 *
	 * @return void
	 */
	public function maybe_schedule_backfill() {
		if ( ! UsageTracking::is_opted_in() || get_option( self::BACKFILL_DONE_OPTION ) ) {
			return;
		}

		if ( ! wp_next_scheduled( self::BACKFILL_CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 5, self::BACKFILL_CRON_HOOK );
		}
	}

	/**
	 * One-time scan seeding the aggregate and per-post snapshots from existing
	 * content, so opting in mid-way through a site's life still counts everything
	 * already published rather than only future edits. Processes one bounded batch
	 * of not-yet-seeded posts and reschedules itself while any remain, rather than
	 * looping over the whole site in a single request.
	 *
	 * @since 1.8.6
	 *
	 * @return void
	 */
	public function run_backfill() {
		if ( ! UsageTracking::is_opted_in() ) {
			return;
		}

		$post_ids = get_posts(
			array(
				'post_type'              => self::get_tracked_post_types(),
				'post_status'            => array( 'publish', 'private' ),
				'posts_per_page'         => self::BACKFILL_BATCH_SIZE,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => self::SNAPSHOT_META_KEY,
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		if ( empty( $post_ids ) ) {
			update_option( self::BACKFILL_DONE_OPTION, true, false );
			return;
		}

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}

			$current_counts = self::count_blocks( $post->post_content );
			self::apply_delta( array(), $current_counts );
			update_post_meta( $post_id, self::SNAPSHOT_META_KEY, $current_counts );
		}

		if ( count( $post_ids ) === self::BACKFILL_BATCH_SIZE ) {
			wp_schedule_single_event( time() + 2, self::BACKFILL_CRON_HOOK );
		} else {
			update_option( self::BACKFILL_DONE_OPTION, true, false );
		}
	}
}
