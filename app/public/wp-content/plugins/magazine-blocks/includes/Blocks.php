<?php
/**
 * Magazine Blocks Manager.
 *
 * @since 1.0.0
 * @package Magazine Blocks
 */

namespace MagazineBlocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use MagazineBlocks\BlockTypes\Button;
use MagazineBlocks\BlockTypes\Advertisement;
use MagazineBlocks\BlockTypes\Archive;
use MagazineBlocks\BlockTypes\DateWeather;
use MagazineBlocks\BlockTypes\Modal;
use MagazineBlocks\BlockTypes\PostContent;
use MagazineBlocks\BlockTypes\PostMeta;
use MagazineBlocks\Traits\Singleton;
use MagazineBlocks\BlockTypes\Heading;
use MagazineBlocks\BlockTypes\BannerPosts;
use MagazineBlocks\BlockTypes\GridModule;
use MagazineBlocks\BlockTypes\FeaturedPosts;
use MagazineBlocks\BlockTypes\FeaturedCategories;
use MagazineBlocks\BlockTypes\PostList;
use MagazineBlocks\BlockTypes\CategoryList;
use MagazineBlocks\BlockTypes\TabPost;
use MagazineBlocks\BlockTypes\NewsTicker;
use MagazineBlocks\BlockTypes\Column;
use MagazineBlocks\BlockTypes\PostVideo;
use MagazineBlocks\BlockTypes\Section;
use MagazineBlocks\BlockTypes\Slider;
use MagazineBlocks\BlockTypes\AbstractBlock;
use MagazineBlocks\BlockTypes\Breadcrumbs;
use MagazineBlocks\BlockTypes\Category;
use MagazineBlocks\BlockTypes\PostImage;
use MagazineBlocks\BlockTypes\LatestPosts;
use MagazineBlocks\BlockTypes\SocialIcons;
use MagazineBlocks\BlockTypes\SocialIcon;
use MagazineBlocks\BlockTypes\Icon;
use MagazineBlocks\BlockTypes\Image;
use MagazineBlocks\BlockTypes\Paragraph;
use MagazineBlocks\BlockTypes\PostTitle;
use MagazineBlocks\Services\BlockRegistrar;
use MagazineBlocks\Services\MetadataValidator;
use MagazineBlocks\Services\PostQueryBuilder;
use MagazineBlocks\Services\TemplateContextHandler;
use WP_Query;

/**
 * Magazine_Blocks Blocks Manager
 *
 * Registers all the blocks & block categories and manages them.
 *
 * @since 1.0.0
 */
final class Blocks {

	use Singleton;

	/**
	 * Block styles.
	 *
	 * @var BlockStyles|null $block_styles
	 */
	private $block_styles;

	/**
	 * Blocks that need to be prepared for CSS generation.
	 *
	 * @var array $prepared_blocks
	 */
	private $prepared_blocks = array();

	/**
	 * Prepared widget blocks.
	 *
	 * @var array
	 */
	private $prepared_widget_blocks = array();

	/**
	 * Register block service instance.
	 *
	 * @var [type]
	 */
	private $registrar;
	/**
	 * Builder query service instance.
	 *
	 * @var [type]
	 */
	private $query_builder;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$validator           = new MetadataValidator();
		$this->registrar     = new BlockRegistrar( $validator );
		$context_handler     = new TemplateContextHandler();
		$this->query_builder = new PostQueryBuilder( $context_handler );

		$this->init_hooks();
	}

	/**
	 * Magazine_Blocks/BlocksManager Constructor.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		global $pagenow;

		$block_categories_hook   = version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ?
			'block_categories_all' :
			'block_categories';
		$preload_api_hook_handle = class_exists( 'WP_Block_Editor_Context' ) ? 'block_editor_rest_api_preload_paths' : 'block_editor_preload_paths';

		add_action( 'init', array( $this, 'register_block_types' ) );

		add_filter( $block_categories_hook, array( $this, 'block_categories' ), PHP_INT_MAX, 2 );
		add_filter( $preload_api_hook_handle, array( $this, 'preload_rest_api_path' ), 10, 2 );

		add_filter( 'pre_render_block', array( $this, 'maybe_prepare_blocks' ), 10, 3 );
		add_filter( 'wp_head', array( $this, 'maybe_prepare_blocks' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_blocks_styles' ), 11 );

		add_action( 'customize_save_after', array( $this, 'maybe_clear_widget_block_styles' ) );
		add_action( 'rest_after_save_widget', array( $this, 'maybe_clear_widget_block_styles' ) );
		add_action( 'after_switch_theme', array( $this, 'maybe_clear_block_styles_on_theme_switch' ), 10, 2 );
		add_action( 'save_post', array( $this, 'maybe_clear_block_styles' ), 10, 3 );
		add_action( 'delete_post', array( $this, 'maybe_clear_block_styles' ), 10, 2 );
		add_action( 'magazine_blocks_responsive_breakpoints_changed', array( $this, 'regenerate_block_styles' ) );
		add_action(
			'wp_head',
			function () {
				if ( ! is_single() ) {
					return;
				}
				$id   = get_the_ID();
				$view = get_post_meta( $id, '_mzb_post_view_count', true );
				$view = ! $view ? 0 : $view;
				$view++;
				update_post_meta( $id, '_mzb_post_view_count', $view );
			}
		);

		add_filter( 'posts_join', 'magazine_blocks_join_popular_order_meta', 10, 2 );
		add_filter( 'posts_orderby', 'magazine_blocks_orderby_popular_order_meta', 10, 2 );

		add_action(
			'enqueue_block_editor_assets',
			array( $this, 'mzb_post_type' )
		);

		if ( 'customize.php' !== $pagenow ) {
			add_action(
				'enqueue_block_editor_assets',
				function () {
					$palette = get_theme_support( 'editor-color-palette' );

					if ( empty( $palette ) ) {
						return;
					}

					$styles = sprintf(
						':root{%s}',
						array_reduce(
							current( $palette ),
							function ( $acc, $curr ) {
								$acc .= "--{$curr['slug']}: {$curr['color']};\n";
								return $acc;
							},
							''
						)
					);

					wp_add_inline_style( 'wp-block-library', $styles );
				}
			);
		}
	}

	/**
	 * Localize the current post type for use in block editor scripts.
	 *
	 * Makes the post type available to JavaScript via the magazinePostTypeSettings object.
	 */
	public function mzb_post_type() {
		$current_post = get_post();
		$post_type    = $current_post ? $current_post->post_type : 'post';

		wp_localize_script(
			'magazine-blocks-blocks',
			'magazinePostTypeSettings',
			array(
				'postType' => $post_type,
			)
		);
	}

	/**
	 * Preload REST api path.
	 *
	 * @param array                                   $paths Rest API paths.
	 * @param \WP_Block_Editor_Context|\WP_Post|mixed $context Current editor context.
	 *
	 * @return array
	 */
	public function preload_rest_api_path( array $paths, $context ): array {
		if (
			$context instanceof \WP_Post ||
			( isset( $context->name ) && in_array( $context->name, array( 'core/edit-site', 'core/edit-post' ), true ) )
		) {
			$paths[] = '/magazine-blocks/v1/library-data';
		}
		return $paths;
	}

	/**
	 * Prepare blocks for FSE themes.
	 */
	public function maybe_prepare_blocks() {
		if ( magazine_blocks_is_block_theme() && doing_filter( 'pre_render_block' ) ) {
			$args                    = func_get_args();
			$block                   = $args[1];
			$this->prepared_blocks[] = $block;
			return $args[0];
		}
		if ( ! magazine_blocks_is_block_theme() && doing_action( 'wp_head' ) ) {
			$content                      = apply_filters(
				'magazine_blocks_content_for_css_generation',
				get_the_content()
			);
			$this->prepared_blocks        = parse_blocks( $content );
			$this->prepared_widget_blocks = $this->get_widget_blocks();
		}
	}

	/**
	 * Enqueue blocks styles.
	 *
	 * @return void
	 */
	public function enqueue_blocks_styles() {
		if ( empty( $this->prepared_blocks ) ) {
			return;
		}

		$fonts                 = array();
		$this->prepared_blocks = magazine_blocks_process_blocks( $this->prepared_blocks );
		$styles                = magazine_blocks_generate_blocks_styles( $this->prepared_blocks );
		if ( ! magazine_blocks_is_block_theme() ) {
			$this->prepared_widget_blocks = magazine_blocks_process_blocks( $this->prepared_widget_blocks );
			$widget_styles                = magazine_blocks_generate_blocks_styles( $this->prepared_widget_blocks, 'widgets' );
			$fonts                        = $widget_styles->get_fonts();
			$widget_styles->enqueue();
		}

		$styles->enqueue_fonts( $fonts );
		$styles->enqueue();
	}

	/**
	 * Register block types.
	 */
	public function register_block_types() {
		$block_types   = $this->get_block_types();
		$is_old_struct = defined( 'MAGAZINE_BLOCKS_PRO_VERSION' ) && version_compare( MAGAZINE_BLOCKS_PRO_VERSION, '1.2.2', '<=' );

		foreach ( $block_types as $block_type ) {
			if ( $is_old_struct && false !== stripos( $block_type, 'MagazineBlocksPro' ) ) {
				new $block_type();
			} else {
				new $block_type( $this->registrar, $this->query_builder );
			}
		}
	}

	/**
	 * Get block types.
	 *
	 * @return AbstractBlock[]
	 */
	private function get_block_types() {
		return apply_filters(
			'magazine_blocks_block_types',
			array(
				Advertisement::class,
				Heading::class,
				Archive::class,
				PostContent::class,
				PostMeta::class,
				BannerPosts::class,
				Breadcrumbs::class,
				GridModule::class,
				FeaturedPosts::class,
				FeaturedCategories::class,
				TabPost::class,
				PostList::class,
				CategoryList::class,
				NewsTicker::class,
				Column::class,
				PostVideo::class,
				Section::class,
				DateWeather::class,
				Slider::class,
				SocialIcons::class,
				SocialIcon::class,
				Modal::class,
				LatestPosts::class,
				Image::class,
				Icon::class,
				Category::class,
				Paragraph::class,
				PostImage::class,
				PostTitle::class,
				Button::class,
			)
		);
	}

	/**
	 * Add "Magazine Blocks" category to the blocks listing in post edit screen.
	 *
	 * @param array $block_categories All registered block categories.
	 * @return array
	 * @since 1.0.0
	 */
	public function block_categories( array $block_categories ): array {
		return array_merge(
			array(
				array(
					'slug'  => 'magazine-blocks',
					'title' => esc_html__( 'Magazine Blocks - General', 'magazine-blocks' ),
				),
				array(
					'slug'  => 'magazine-blocks-post-category',
					'title' => esc_html__( 'Magazine Blocks - Post And Category', 'magazine-blocks' ),
				),
				array(
					'slug'  => 'magazine-blocks-utility-blocks',
					'title' => esc_html__( 'Magazine Blocks - Dynamic Blocks', 'magazine-blocks' ),
				),
			),
			$block_categories
		);
	}

	/**
	 * Clear cached widget styles when widget is updated.
	 *
	 * @return void
	 */
	public function maybe_clear_widget_block_styles() {
		$cached = get_option( '_magazine_blocks_blocks_css', array() );
		magazine_blocks_array_forget( $cached, 'widgets' );
		update_option( '_magazine_blocks_blocks_css', $cached );
	}

	/**
	 * Clear cached styles when theme is switched.
	 *
	 * If is block theme then clear all cached styles stored in options table.
	 * As block theme fully depends on blocks.
	 *
	 * @param string    $name string Theme name.
	 * @param \WP_Theme $theme Theme object.
	 * @return void
	 */
	public function maybe_clear_block_styles_on_theme_switch( string $name, \WP_Theme $theme ) {
		if ( $theme->is_block_theme() ) {
			delete_option( '_magazine_blocks_blocks_css' );
		}
	}

	/**
	 * Clear or update cached styles.
	 *
	 * @param int      $id Post ID.
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function maybe_clear_block_styles( $id, \WP_Post $post ) {
		if ( doing_action( 'save_post' ) ) {
			// Don't make style for reusable blocks.
			if ( 'wp_block' === $post->post_type ) {
				return;
			}

			// Clear cached styles when template part or template is updated.
			if ( 'wp_template_part' === $post->post_type || 'wp_template' === $post->post_type ) {
				delete_option( '_magazine_blocks_blocks_css' );
				return;
			}

			delete_post_meta( $id, '_magazine_blocks_blocks_css' );
		}

		if ( doing_action( 'delete_post' ) ) {
			$filesystem = magazine_blocks_get_filesystem();
			if ( $filesystem ) {
				$css_files = $filesystem->dirlist( MAGAZINE_BLOCKS_UPLOAD_DIR );
				if ( ! empty( $css_files ) ) {
					foreach ( $css_files as $css_file ) {
						if ( false !== strpos( $css_file['name'], "ba-style-$id-" ) ) {
							$filesystem->delete( MAGAZINE_BLOCKS_UPLOAD_DIR . $css_file['name'] );
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Get widget blocks.
	 *
	 * @return array
	 */
	private function get_widget_blocks() {
		return parse_blocks(
			array_reduce(
				get_option( 'widget_block', array() ),
				function ( $acc, $curr ) {
					if ( ! empty( $curr['content'] ) ) {
						$acc .= $curr['content'];
					}
					return $acc;
				},
				''
			)
		);
	}

	/**
	 * Regenerate block styles.
	 *
	 * @return void
	 */
	public function regenerate_block_styles() {
		delete_option( '_magazine_blocks_blocks_css' );
		delete_post_meta_by_key( '_magazine_blocks_blocks_css' );

		$filesystem = magazine_blocks_get_filesystem();

		if ( $filesystem ) {
			$filesystem->delete( MAGAZINE_BLOCKS_UPLOAD_DIR, true );
		}
	}
}
