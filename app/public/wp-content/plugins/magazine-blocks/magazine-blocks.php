<?php
/**
 * Plugin Name: Magazine Blocks
 * Description: Craft your beautifully unique and dynamic Magazine, Newspaper website with various beautiful and advanced posts related blocks like Featured Posts, Banner Posts, Grid Module, Tab Posts, and more.
 * Author: WPBlockArt
 * Author URI: https://wpblockart.com/
 * Version: 1.8.9
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Text Domain: magazine-blocks
 * Domain Path: /languages
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WordPress Available: yes
 * Requires License: no
 *
 * @package Magazine Blocks
 */

use MagazineBlocks\Blocks;
use MagazineBlocks\Helpers\PostHelper;
use MagazineBlocks\MagazineBlocks;

defined( 'ABSPATH' ) || exit;

! defined( 'MAGAZINE_BLOCKS_VERSION' ) && define( 'MAGAZINE_BLOCKS_VERSION', '1.8.9' );
! defined( 'MAGAZINE_BLOCKS_PLUGIN_FILE' ) && define( 'MAGAZINE_BLOCKS_PLUGIN_FILE', __FILE__ );
! defined( 'MAGAZINE_BLOCKS_PLUGIN_DIR' ) && define( 'MAGAZINE_BLOCKS_PLUGIN_DIR', __DIR__ );
! defined( 'MAGAZINE_BLOCKS_PLUGIN_DIR_URL' ) && define( 'MAGAZINE_BLOCKS_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
! defined( 'MAGAZINE_BLOCKS_ASSETS' ) && define( 'MAGAZINE_BLOCKS_ASSETS', __DIR__ . '/assets' );
! defined( 'MAGAZINE_BLOCKS_ASSETS_DIR_URL' ) && define( 'MAGAZINE_BLOCKS_ASSETS_DIR_URL', MAGAZINE_BLOCKS_PLUGIN_DIR_URL . 'assets' );
! defined( 'MAGAZINE_BLOCKS_DIST_DIR_URL' ) && define( 'MAGAZINE_BLOCKS_DIST_DIR_URL', MAGAZINE_BLOCKS_PLUGIN_DIR_URL . 'dist' );
! defined( 'MAGAZINE_BLOCKS_LANGUAGES' ) && define( 'MAGAZINE_BLOCKS_LANGUAGES', __DIR__ . '/languages' );
! defined( 'MAGAZINE_BLOCKS_UPLOAD_DIR' ) && define( 'MAGAZINE_BLOCKS_UPLOAD_DIR', wp_upload_dir()['basedir'] . '/magazine-blocks' );
! defined( 'MAGAZINE_BLOCKS_UPLOAD_DIR_URL' ) && define( 'MAGAZINE_BLOCKS_UPLOAD_DIR_URL', wp_upload_dir()['baseurl'] . '/magazine-blocks' );

// Load the autoloader.
require_once __DIR__ . '/vendor/autoload.php';

if ( ! function_exists( 'magazine_blocks' ) ) {
	/**
	 * Returns the main instance of Magazine Blocks to prevent the need to use globals.
	 *
	 * @return MagazineBlocks
	 */
	function magazine_blocks() {
		return MagazineBlocks::init();
	}
}

magazine_blocks();

/**
 * Create API fields for additional info
 *
 * @since 1.0.9
 */
function magazine_blocks_register_rest_fields() {
	$post_type = PostHelper::get_post_types();

	foreach ( $post_type as $key => $value ) {
		// Featured image.
		register_rest_field(
			$value['value'],
			'magazine_blocks_featured_image_url',
			array(
				'get_callback'    => 'magazine_blocks_get_featured_image_url',
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Author info.
		register_rest_field(
			$value['value'],
			'magazine_blocks_author',
			array(
				'get_callback'    => 'magazine_blocks_get_author_info',
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Add comment info.
		register_rest_field(
			$value['value'],
			'magazine_blocks_comment',
			array(
				'get_callback'    => 'magazine_blocks_get_comment_info',
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Add comment info.
		register_rest_field(
			$value['value'],
			'magazine_blocks_author_image',
			array(
				'get_callback'    => 'magazine_blocks_get_author_image',
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Category links.
		register_rest_field(
			$value['value'],
			'magazine_blocks_category',
			array(
				'get_callback'    => 'magazine_blocks_get_category_list',
				'update_callback' => null,
				'schema'          => array(
					'description' => esc_html__( 'Category list links', 'magazine-blocks' ),
					'type'        => 'string',
				),
			)
		);

		// Video post format URL.
		register_rest_field(
			$value['value'],
			'videoUrl',
			array(
				'get_callback'    => 'magazine_blocks_get_video_url',
				'update_callback' => null,
				'schema'          => array(
					'description' => esc_html__( 'Video URL from the video post format meta', 'magazine-blocks' ),
					'type'        => 'string',
				),
			)
		);
	}
}

// Feature image.
function magazine_blocks_get_featured_image_url( $object ) {

	$featured_images = array();
	if ( ! isset( $object['featured_media'] ) ) {
		return $featured_images;
	} else {

		$full                         = wp_get_attachment_image_src( $object['featured_media'], 'full', false );
		$medium                       = wp_get_attachment_image_src( $object['featured_media'], 'medium', false );
		$thumbnail                    = wp_get_attachment_image_src( $object['featured_media'], 'thumbnail', false );
		$featured_images['full']      = $full;
		$featured_images['medium']    = $medium;
		$featured_images['thumbnail'] = $thumbnail;
		return $featured_images;
	}
}

// Author.
function magazine_blocks_get_author_info( $object ) {
	$author = ( isset( $object['author'] ) ) ? $object['author'] : '';

	$author_data['display_name'] = get_the_author_meta( 'display_name', $author );
	$author_data['author_link']  = get_author_posts_url( $author );

	return $author_data;
}

// Comment.
function magazine_blocks_get_comment_info( $object ) {
	$comments_count = wp_count_comments( $object['id'] );
	return $comments_count->total_comments;
}

// Author Image.
function magazine_blocks_get_author_image( $object ) {
	$author = ( isset( $object['author'] ) ) ? $object['author'] : '';

	$author_image = get_avatar_url( $author );

	return $author_image;
}

// Video post format URL.
function magazine_blocks_get_video_url( $object ) {
	return get_post_meta( $object['id'], 'video_url', true );
}

// Category list.
if ( ! function_exists( 'magazine_blocks_render_category_link' ) ) {
	/**
	 * Render a single category/term as a link, matching the frontend's own
	 * category markup so the block editor preview doesn't diverge from it:
	 * only add the ColorMag override class + inline color when the theme's
	 * "Override Category Color" setting is on and a color actually resolves.
	 *
	 * @param \WP_Term $term          Category or term.
	 * @param bool     $use_override  Whether ColorMag's category color override is active.
	 * @return string Rendered anchor markup.
	 */
	function magazine_blocks_render_category_link( $term, $use_override ) {
		$link = get_term_link( $term );
		$url  = is_wp_error( $link ) ? '#' : esc_url( $link );

		if ( $use_override ) {
			$color = colormag_category_color( $term->term_id );
			if ( $color ) {
				return sprintf(
					'<a href="%s" class="category-link category-link-%d" style="color: %s;">%s</a>',
					$url,
					(int) $term->term_id,
					esc_attr( $color ),
					esc_html( $term->name )
				);
			}
		}

		return sprintf( '<a href="%s">%s</a>', $url, esc_html( $term->name ) );
	}
}

if ( ! function_exists( 'magazine_blocks_get_category_list' ) ) {
	/**
	 * REST field callback returning a post's category/term links as HTML,
	 * used by the block editor's live post-listing previews.
	 *
	 * @param array $object REST response object array.
	 * @return string Category links HTML.
	 */
	function magazine_blocks_get_category_list( $object ) {
		$taxonomies   = get_post_taxonomies( $object['id'] );
		$post_id      = $object['id'];
		$output       = '';
		$use_override = get_theme_mod( 'colormag_enable_override_category_color', false ) && function_exists( 'colormag_category_color' );

		if ( 'post' === get_post_type( $post_id ) ) {
			$categories = get_the_category( $post_id );
			if ( ! empty( $categories ) ) {
				foreach ( $categories as $category ) {
					$output .= magazine_blocks_render_category_link( $category, $use_override ) . ' ';
				}
			}
		} elseif ( ! empty( $taxonomies ) ) {
			$terms = get_the_terms( $post_id, $taxonomies[0] );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$output .= magazine_blocks_render_category_link( $term, $use_override ) . ' ';
				}
			}
		}
		return trim( $output );
	}
}
add_action( 'rest_api_init', 'magazine_blocks_register_rest_fields' );

add_action(
	'enqueue_block_assets',
	function () {
		// Frontend output is handled separately via the wp_head callback below.
		if ( ! is_admin() ) {
			return;
		}

		$categories                     = get_categories();
		$enable_override_category_color = get_theme_mod( 'colormag_enable_override_category_color', false );
		$css                            = '';

		if ( function_exists( 'colormag_category_color' ) && $enable_override_category_color ) {
			foreach ( $categories as $category ) {
				$color = colormag_category_color( $category->term_id );
				if ( $color ) {
					$css .= '.mzb-post-meta .mzb-post-categories .category-link-' . esc_attr( $category->term_id ) . '{background-color: ' . esc_attr( $color ) . ';}';
				}
			}
		} else {
			foreach ( $categories as $category ) {
				$css .= '.mzb-post-meta .mzb-post-categories .category-link-' . esc_attr( $category->term_id ) . '{background-color: var(--mzb-categories-colors-' . esc_attr( $category->term_id ) . ');}';
			}
		}

		if ( $css && wp_style_is( 'magazine-blocks-blocks-editor', 'registered' ) ) {
			wp_add_inline_style( 'magazine-blocks-blocks-editor', $css );
		}
	}
);

add_action(
	'wp_head',
	function () {
		$categories                     = get_categories();
		$enable_override_category_color = get_theme_mod( 'colormag_enable_override_category_color', false );
		$css                            = '';

		if ( function_exists( 'colormag_category_color' ) && $enable_override_category_color ) {
			foreach ( $categories as $category ) {
				$color = colormag_category_color( $category->term_id );
				if ( $color ) {
					$css .= '.mzb-post-meta .mzb-post-categories .category-link-' . esc_attr( $category->term_id ) . '{background-color: ' . esc_attr( $color ) . ';}';
				}
			}
		} else {
			foreach ( $categories as $category ) {
				$settings = magazine_blocks_get_setting( 'global-styles' );
				$settings = json_decode( $settings, true );
				$color    = $settings['categories_color'] ?? array();
				if ( ! empty( $color ) ) {
					foreach ( $color as $setting => $value ) {
						if ( isset( $value['id'], $value['value'] ) && $value['id'] == $category->term_id ) {
							$css .= '.mzb-post-meta .mzb-post-categories .category-link-' . esc_attr( $category->term_id ) . '{background-color: ' . esc_attr( $value['value'] ) . ';}';
						}
					}
				}
			}
		}

		if ( $css ) {
			echo '<style id="magazine-blocks-category-colors">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
);

add_action( 'wp_ajax_magazine_blocks_pagination_load', 'magazine_blocks_pagination_load' );
add_action( 'wp_ajax_nopriv_magazine_blocks_pagination_load', 'magazine_blocks_pagination_load' );

function magazine_blocks_pagination_load() {
	if ( ! check_ajax_referer( '_magazine_blocks_nonce', 'security', false ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Invalid request.', 'magazine-blocks' ) ),
			403
		);
	}

	$page = isset( $_GET['page'] ) ? absint( wp_unslash( $_GET['page'] ) ) : 0;

	$att = isset( $_POST['att'] ) && is_array( $_POST['att'] ) ? map_deep( wp_unslash( $_POST['att'] ), 'sanitize_text_field' ) : array(); // Pass the attributes needed for rendering

	// Modify the attributes with the new page number
	$att['paged'] = $page;

	// Render the posts using your existing function
	$html = Blocks::render_block_magazine_blocks_featured_posts( $att );

	// Return the HTML as the response
	wp_send_json_success( array( 'html' => $html ) );

	wp_die();
}
