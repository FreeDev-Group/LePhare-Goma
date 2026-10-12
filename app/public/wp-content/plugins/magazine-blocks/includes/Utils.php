<?php
/**
 * Import helper.
 *
 * @package Magazine Blocks
 * @since 1.0.0
 */

namespace MagazineBlocks;

defined( 'ABSPATH' ) || exit;

/**
 * Import helper.
 *
 * Process content.
 *
 * @since 1.0.0
 */
class Utils {

	/**
	 * Holds single instance of this class.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Init.
	 *
	 * @return Utils|null
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Get media items.
	 *
	 * @return array[]
	 */
	public function get_media_items() {
		$query = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		return array_map(
			function ( $item ) {
				$item                  = (array) $item;
				$item['media_details'] = wp_get_attachment_metadata( $item['ID'] );
				$item['alt_text']      = get_post_meta( $item['ID'], '_wp_attachment_image_alt', true );
				$item['mime_type']     = $item['post_mime_type'];
				$item['source_url']    = wp_get_attachment_url( $item['ID'] );

				return $item;
			},
			get_posts( $query )
		);
	}

	/**
	 * Create files.
	 *
	 * @param string $filename Filename.
	 * @param string $content Content.
	 * @return bool
	 * @since 1.0.0
	 */
	public function create_files( $filename, $content ) {
		global $wp_filesystem;
		$upload_dir_url = wp_upload_dir();
		$upload_dir     = trailingslashit( $upload_dir_url['basedir'] ) . 'magazine_blocks/';

		! $wp_filesystem && require_once ABSPATH . 'wp-admin/includes/file.php';

		WP_Filesystem( false, $upload_dir_url['basedir'], true );

		! $wp_filesystem->is_dir( $upload_dir ) && $wp_filesystem->mkdir( $upload_dir );

		return $wp_filesystem->put_contents( "$upload_dir$filename", $content );
	}

	/**
	 * Process content while importing section or template.
	 *
	 * @since 1.0.0
	 * @param string $content Post content.
	 * @return array|string|string[]
	 */
	public static function process_content( $content = '' ) {

		preg_match_all( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $content, $match );

		$urls = array_unique( $match[0] );

		if ( empty( $urls ) ) {
			return $content;
		}

		$map_urls   = array();
		$image_urls = array();

		foreach ( $urls as $key => $url ) {
			if ( self::has_image( $url ) ) {
				if (
					false === strpos( $url, '-150x' ) &&
					false === strpos( $url, '-300x' ) &&
					false === strpos( $url, '-1024x' )
				) {
					$image_urls[] = $url;
				}
			}
		}

		if ( ! empty( $image_urls ) ) {
			foreach ( $image_urls as $key => $image_url ) {
				$image                  = array(
					'url' => $image_url,
					'id'  => 0,
				);
				$downloaded_image       = self::import_image( $image );
				$map_urls[ $image_url ] = $downloaded_image['url'];
			}
		}

		foreach ( $map_urls as $old_url => $new_url ) {
			$content = str_replace( $old_url, $new_url, $content );
			$old_url = str_replace( '/', '/\\', $old_url );
			$new_url = str_replace( '/', '/\\', $new_url );
			$content = str_replace( $old_url, $new_url, $content );
		}

		return $content;
	}

	/**
	 * Check if the URL points to an image.
	 *
	 * @since 1.0.0
	 * @param string $url Valid URL.
	 * @return false|int
	 */
	private static function has_image( $url = '' ) {
		return preg_match( '/^((https?:\/\/)|(www\.))([a-z0-9-].?)+(:[0-9]+)?\/[\w\-]+\.(jpg|png|gif|jpeg)\/?$/i', $url );
	}

	/**
	 * Handles image import.
	 *
	 * @since 1.0.0
	 * @param array $data Image URL data.
	 * @return array|mixed
	 */
	private static function import_image( $data ) {

		$local_image = self::is_local_image( $data );

		if ( $local_image['status'] ) {
			return $local_image['image'];
		}

		$file_content = wp_remote_retrieve_body(
			wp_safe_remote_get(
				$data['url'],
				array(
					'timeout'   => '60',
					'sslverify' => false,
				)
			)
		);

		if ( empty( $file_content ) ) {
			return $data;
		}

		$filename = basename( $data['url'] );

		$upload = wp_upload_bits( $filename, null, $file_content );
		$post   = array(
			'post_title' => $filename,
			'guid'       => $upload['url'],
		);
		$info   = wp_check_filetype( $upload['file'] );

		if ( $info ) {
			$post['post_mime_type'] = $info['type'];
		} else {
			return $data;
		}

		$post_id = wp_insert_attachment( $post, $upload['file'] );

		require_once ABSPATH . 'wp-admin/includes/image.php';

		wp_update_attachment_metadata(
			$post_id,
			wp_generate_attachment_metadata( $post_id, $upload['file'] )
		);
		update_post_meta( $post_id, '_magazine_blocks_image_hash', sha1( $data['url'] ) );

		return array(
			'id'  => $post_id,
			'url' => $upload['url'],
		);
	}

	/**
	 * Check if the image exists in the system.
	 *
	 * @since 1.0.0
	 * @param array $data Image URL data.
	 * @return array
	 */
	private static function is_local_image( $data ) {

		global $wpdb;

		$image_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT `post_id` FROM `' . $wpdb->postmeta . '`
					WHERE `meta_key` = \'_magazine_blocks_image_hash\'
						AND `meta_value` = %s
				;',
				sha1( $data['url'] )
			)
		);

		if ( $image_id ) {
			$local_image = array(
				'id'  => $image_id,
				'url' => wp_get_attachment_url( $image_id ),
			);
			return array(
				'status' => true,
				'image'  => $local_image,
			);
		}
		return array(
			'status' => false,
			'image'  => $data,
		);
	}

	/**
	 * Get magazine blocks widget blocks.
	 *
	 * @return string
	 */
	public function get_widget_blocks() {
		$blocks = get_option( 'widget_block' );
		return array_reduce(
			(array) $blocks,
			function ( $acc, $cur ) {
				if (
					isset( $cur['content'] ) &&
					false !== strpos( $cur['content'], 'wp:magazine-blocks/' )
				) {
					$acc .= $cur['content'];
				}
				return $acc;
			},
			''
		);
	}
}
