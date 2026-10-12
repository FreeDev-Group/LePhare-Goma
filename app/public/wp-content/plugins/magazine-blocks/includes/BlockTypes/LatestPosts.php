<?php
/**
 * LatestPosts block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\PostRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * LatestPosts block class.
 */
class LatestPosts extends Block {

	use PostRenderer;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'latest-posts';

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		$attrs = $this->extract_attributes( $attributes );

		$categories = 'all' === $attrs['category'] ? get_categories() : array( get_category( $attrs['category'] ) );
		$posts      = $this->get_latest_posts_by_category(
			$categories,
			$attrs['excluded_category'],
			$attrs['excluded_tag'],
			$attrs['offset'] + ( $attrs['paged'] - 1 ),
			$attrs['post_type'],
			$attrs['author'],
			$attrs['tag']
		);

		$attrs['max_num_pages'] = $attrs['enable_pagination'] ? $this->get_max_num_pages(
			$categories,
			$attrs['excluded_category'],
			$attrs['excluded_tag'],
			$attrs['post_type'],
			$attrs['author'],
			$attrs['tag']
		) : 1;

		return $this->render_block( $posts, $attrs );
	}

	/**
	 * Extract attributes.
	 *
	 * @param array $attributes Original block attributes.
	 */
	protected function extract_attributes( $attributes ) {
		$client_id      = magazine_blocks_array_get( $attributes, 'clientId', '' );
		$layout         = magazine_blocks_array_get( $attributes, 'layout', 'layout-1' );
		$heading_layout = magazine_blocks_array_get( $attributes, 'headingLayout', '' );

		// Get the specific advanced styles based on layout and heading layout.
		$advanced_style = magazine_blocks_array_get( $attributes, magazine_get_style_key( $layout ), '' );
		$heading_style  = magazine_blocks_array_get( $attributes, magazine_get_heading_style_key( $heading_layout ), '' );
		return array(
			'client_id'         => $client_id,
			'class_name'        => magazine_blocks_array_get( $attributes, 'className', '' ),
			'label'             => magazine_blocks_array_get( $attributes, 'label', '' ),
			'layout'            => $layout,
			'advanced_style'    => $advanced_style,
			'heading_layout'    => $heading_layout,
			'heading_style'     => $heading_style,
			'column'            => magazine_blocks_array_get( $attributes, 'column', 2 ),
			// Query parameters.
			'category'          => magazine_blocks_array_get( $attributes, 'category', '' ),
			'tag'               => magazine_blocks_array_get( $attributes, 'tag', '' ),
			'excluded_category' => magazine_blocks_array_get( $attributes, 'excludedCategory', '' ),
			'excluded_tag'      => magazine_blocks_array_get( $attributes, 'excludedTag', '' ),
			'order_by'          => magazine_blocks_array_get( $attributes, 'orderBy', '' ),
			'order_type'        => magazine_blocks_array_get( $attributes, 'orderType', '' ),
			'author'            => magazine_blocks_array_get( $attributes, 'authorName', '' ),
			'post_type'         => magazine_blocks_array_get( $attributes, 'postType', 'post' ),
			'offset'            => magazine_blocks_array_get( $attributes, 'offset', 0 ),
			// Display toggles.
			'enable_heading'    => magazine_blocks_array_get( $attributes, 'enableHeading', true ),
			'enable_post_title' => magazine_blocks_array_get( $attributes, 'enablePostTitle', true ),
			'post_title_markup' => magazine_blocks_array_get( $attributes, 'postTitleMarkup', 'h6' ),
			'enable_excerpt'    => magazine_blocks_array_get( $attributes, 'enableExcerpt', false ),
			'enable_read_more'  => magazine_blocks_array_get( $attributes, 'enableReadMore', false ),
			'read_more_text'    => magazine_blocks_array_get( $attributes, 'readMoreText', '' ),
			'excerpt_limit'     => magazine_blocks_array_get( $attributes, 'excerptLimit', '' ),
			'enable_author'     => magazine_blocks_array_get( $attributes, 'enableAuthor', false ),
			'enable_date'       => magazine_blocks_array_get( $attributes, 'enableDate', false ),
			'enable_icon'       => magazine_blocks_array_get( $attributes, 'enableIcon', false ),
			'meta_position'     => magazine_blocks_array_get( $attributes, 'metaPosition', 'bottom' ),
			'hover_animation'   => magazine_blocks_array_get( $attributes, 'hoverAnimation', '' ),
			'hide_on_desktop'   => magazine_blocks_array_get( $attributes, 'hideOnDesktop', false ),
			// Style options.
			'layout1_style'     => magazine_blocks_array_get( $attributes, 'layout1AdvancedStyle', '' ),
			'layout2_style'     => magazine_blocks_array_get( $attributes, 'layout2AdvancedStyle', '' ),
			'enable_pagination' => magazine_blocks_array_get( $attributes, 'enablePagination', false ),
			// pagination.
			'paged'             => isset( $_GET[ 'block_id_' . $client_id ] ) ? max( 1, intval( $_GET[ 'block_id_' . $client_id ] ) ) : 1, //phpcs:ignore.

		);
	}

	/**
	 * Get latest posts grouped by category.
	 *
	 * @param mixed $categories The categories list.
	 * @param mixed $excluded_category The excluded category list.
	 * @param mixed $excluded_tag The excluded tag list.
	 * @param mixed $offset The offset.
	 * @param mixed $post_type  Th post type.
	 * @param mixed $author     The author.
	 * @param mixed $tag        The tag to include.
	 */
	protected function get_latest_posts_by_category( $categories, $excluded_category, $excluded_tag, $offset, $post_type, $author, $tag = '' ) {
		if ( ! is_array( $excluded_category ) ) {
			$excluded_category = empty( $excluded_category ) ? array() : array( $excluded_category );
		}

		$latest_posts    = array();
		$displayed_posts = array();

		foreach ( $categories as $category ) {
			if ( ! in_array( $category->term_id, $excluded_category, true ) ) {
				$post = $this->get_latest_post_in_category( $category->term_id, $excluded_category, $excluded_tag, $offset, $post_type, $author, $tag );

				if ( $post && ! in_array( $post->ID, $displayed_posts, true ) ) {
					$displayed_posts[] = $post->ID;
					$latest_posts[]    = $post;
				}
			}
		}

		return $latest_posts;
	}

	/**
	 * Get single latest post in a category.
	 *
	 * @param mixed $category_id The categories ID.
	 * @param mixed $excluded_category The excluded category list.
	 * @param mixed $excluded_tag The excluded tag list.
	 * @param mixed $offset The offset.
	 * @param mixed $post_type  Th post type.
	 * @param mixed $author     The author.
	 * @param mixed $tag        The tag to include.
	 */
	protected function get_latest_post_in_category( $category_id, $excluded_category, $excluded_tag, $offset, $post_type, $author, $tag = '' ) {
		$latest_posts = get_posts(
			array(
				'post_type'        => $post_type,
				'category'         => $category_id,
				'numberposts'      => 1,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'category__not_in' => $excluded_category,
				'tag__not_in'      => $excluded_tag,
				'tag_id'           => 'all' === $tag ? '' : $tag,
				'offset'           => $offset,
				'author'           => 'all' === $author ? '' : $author,
			)
		);

		return ! empty( $latest_posts ) ? $latest_posts[0] : null;
	}

	/**
	 * Get the maximum number of pages available across the selected categories.
	 *
	 * Each page advances one post deeper into every category's post history,
	 * so the total pages available is bounded by whichever category has the most matching posts.
	 *
	 * @param mixed $categories The categories list.
	 * @param mixed $excluded_category The excluded category list.
	 * @param mixed $excluded_tag The excluded tag list.
	 * @param mixed $post_type  The post type.
	 * @param mixed $author     The author.
	 * @param mixed $tag        The tag to include.
	 * @return int
	 */
	protected function get_max_num_pages( $categories, $excluded_category, $excluded_tag, $post_type, $author, $tag = '' ) {
		if ( ! is_array( $excluded_category ) ) {
			$excluded_category = empty( $excluded_category ) ? array() : array( $excluded_category );
		}

		$max_num_pages = 1;

		foreach ( $categories as $category ) {
			if ( in_array( $category->term_id, $excluded_category, true ) ) {
				continue;
			}

			$post_count = count(
				get_posts(
					array(
						'post_type'        => $post_type,
						'category'         => $category->term_id,
						'numberposts'      => -1,
						'fields'           => 'ids',
						'category__not_in' => $excluded_category,
						'tag__not_in'      => $excluded_tag,
						'tag_id'           => 'all' === $tag ? '' : $tag,
						'author'           => 'all' === $author ? '' : $author,
					)
				)
			);

			$max_num_pages = max( $max_num_pages, $post_count );
		}

		return $max_num_pages;
	}

	/**
	 * Render block wrapper.
	 *
	 * @param mixed $posts The posts.
	 * @param mixed $attrs The attributes.
	 */
	protected function render_block( $posts, $attrs ) {
		$block_class = "mzb-latest-posts mzb-latest-posts-{$attrs['client_id']} {$attrs['class_name']}";
		if ( $attrs['hide_on_desktop'] ) {
			$block_class .= ' magazine-blocks-hide-on-desktop';
		}

		$posts_class = "mzb-posts mzb-{$attrs['layout']} mzb-post-col--" . ( $attrs['column'] ? $attrs['column'] : 4 );
		if ( 'layout-1' === $attrs['layout'] ) {
			$posts_class .= " mzb-{$attrs['layout1_style']}";
		} elseif ( 'layout-2' === $attrs['layout'] ) {
			$posts_class .= " mzb-{$attrs['layout2_style']}";
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $block_class ); ?>">
			<?php if ( $attrs['enable_heading'] ) : ?>
				<div class="mzb-post-heading mzb-<?php echo esc_attr( $attrs['heading_layout'] ); ?> mzb-<?php echo esc_attr( $attrs['heading_style'] ); ?>">
					<h2 class="mzb-heading-text"><?php echo esc_html( $attrs['label'] ); ?></h2>
				</div>
			<?php endif; ?>

			<div class="<?php echo esc_attr( $posts_class ); ?>">
				<?php foreach ( $posts as $post ) : ?>
					<div class="mzb-post">
						<?php echo $this->render_featured_image( $post->ID, $attrs['hover_animation'] ); ?>

						<div class="mzb-post-content">
							<?php if ( 'top' === $attrs['meta_position'] ) : ?>
								<?php echo $this->render_meta_section( $post->ID, $attrs ); ?>
							<?php endif; ?>

							<?php if ( $attrs['enable_post_title'] ) : ?>
								<?php echo $this->render_post_title( $post->ID, $attrs['post_title_markup'] ); ?>
							<?php endif; ?>

							<?php if ( 'bottom' === $attrs['meta_position'] ) : ?>
								<?php echo $this->render_meta_section( $post->ID, $attrs ); ?>
							<?php endif; ?>

							<?php if ( $attrs['enable_excerpt'] || $attrs['enable_read_more'] ) : ?>
								<?php
								echo $this->render_excerpt_and_read_more(
									$post->ID,
									array(
										'enable_excerpt'  => $attrs['enable_excerpt'],
										'excerpt_limit'   => $attrs['excerpt_limit'],
										'enable_readmore' => $attrs['enable_read_more'],
										'read_more_text'  => $attrs['read_more_text'],
									)
								);
								?>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $attrs['enable_pagination'] ) : ?>
				<?php echo mzb_numbered_pagination( $attrs['max_num_pages'], $attrs['paged'], $attrs['client_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin-generated markup; URLs already escaped inside mzb_numbered_pagination(). ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render meta section (author/date/icons).
	 *
	 * @param mixed $post_id The post id.
	 * @param mixed $attrs The attributes.
	 */
	protected function render_meta_section( $post_id, $attrs ) {
		$meta_items = array();

		if ( $attrs['enable_author'] ) {
			$meta_items[] = $this->render_author( $post_id, $attrs['enable_icon'] );
		}

		if ( $attrs['enable_date'] ) {
			$meta_items[] = $this->render_date( $post_id, $attrs['enable_icon'] );
		}

		if ( empty( $meta_items ) ) {
			return '';
		}

		return sprintf( '<div class="mzb-post-entry-meta">%s</div>', implode( '', $meta_items ) );
	}
}
