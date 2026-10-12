<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<?php do_action( 'wp_body_open' ); ?>

	<?php
	// Get custom header
	$header = get_posts(
		array(
			'post_type'   => 'mzb-builder-template',
			'meta_key'    => '_mzb_template',
			'meta_value'  => 'header',
			'post_status' => 'publish',
			'numberposts' => 1,
		)
	);

	// Get custom footer
	$footer = get_posts(
		array(
			'post_type'   => 'mzb-builder-template',
			'meta_key'    => '_mzb_template',
			'meta_value'  => 'footer',
			'post_status' => 'publish',
			'numberposts' => 1,
		)
	);

	$use_custom_header = ! empty( $header ) && function_exists( 'magazine_blocks' );
	$use_custom_footer = ! empty( $footer ) && function_exists( 'magazine_blocks' );

	// Render header
	if ( $use_custom_header ) {
		echo magazine_blocks()->render_template_to_string( $header[0]->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block markup includes <style>/<script>; sanitized at save time via WP's own content filters.
	} else {
		get_header();
	}

	// Render main content
	$template_id = get_query_var( 'mzb_current_template_id' );

	if ( $template_id && function_exists( 'magazine_blocks' ) ) {
		// Custom page template exists, render it
		echo magazine_blocks()->render_template_to_string( $template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block markup includes <style>/<script>; sanitized at save time via WP's own content filters.
	} else {
		// Determine the template WordPress would normally use
		if ( is_front_page() ) {
			$original_template = get_front_page_template();
		} elseif ( is_home() ) {
			$original_template = get_home_template();
		} elseif ( is_single() ) {
			$original_template = get_single_template();
		} elseif ( is_page() ) {
			$original_template = get_page_template();
		} elseif ( is_archive() ) {
			$original_template = get_archive_template();
		} elseif ( is_search() ) {
			$original_template = get_search_template();
		} elseif ( is_404() ) {
			$original_template = get_404_template();
		} else {
			$original_template = get_index_template();
		}

		if ( $original_template && file_exists( $original_template ) ) {
			ob_start();
			include $original_template;
			$template_content = ob_get_clean();

			if ( $use_custom_header ) {
				$template_content = preg_replace( '/\s*<\?php\s+get_header\s*\(.*?\);\s*\?>/is', '', $template_content );
				$template_content = preg_replace( '/<header[^>]*>.*?<\/header>/is', '', $template_content );
			}
			if ( $use_custom_footer ) {
				$template_content = preg_replace( '/\s*<\?php\s+get_footer\s*\(.*?\);\s*\?>/is', '', $template_content );
				$template_content = preg_replace( '/<footer[^>]*>.*?<\/footer>/is', '', $template_content );
			}

			// Strip wrapping HTML if included
			$template_content = preg_replace( '/<!DOCTYPE[^>]*>/i', '', $template_content );
			$template_content = preg_replace( '/<html[^>]*>/i', '', $template_content );
			$template_content = preg_replace( '/<\/html>/i', '', $template_content );
			$template_content = preg_replace( '/<head[^>]*>.*?<\/head>/is', '', $template_content );
			$template_content = preg_replace( '/<body[^>]*>/i', '', $template_content );
			$template_content = preg_replace( '/<\/body>/i', '', $template_content );

			echo $template_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Re-echoing the original theme template's own trusted output; wp_kses_post() would strip its <script>/<style> tags.
		} else {
			// Fallback if template not found.
			echo '<div class="site-content">';
			echo '<div class="container">';
			echo '<div class="content-area">';
			echo '<main class="site-main">';

			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();

					if ( is_front_page() || is_home() ) {
						if ( is_page() ) {
							the_content();
						} else {
							get_template_part( 'template-parts/content', get_post_type() );
						}
					} elseif ( is_single() ) {
						get_template_part( 'template-parts/content', 'single' );
					} elseif ( is_page() ) {
						get_template_part( 'template-parts/content', 'page' );
					} elseif ( is_archive() ) {
						get_template_part( 'template-parts/content', 'archive' );
					} else {
						the_content();
					}
				}

				if ( is_home() || is_archive() ) {
					the_posts_pagination();
				}
			} else {
				get_template_part( 'template-parts/content', 'none' );
			}

			echo '</main>';

			if ( is_active_sidebar( 'sidebar-1' ) ) {
				echo '<aside class="sidebar">';
				dynamic_sidebar( 'sidebar-1' );
				echo '</aside>';
			}

			echo '</div>'; // .content-area
			echo '</div>'; // .container
			echo '</div>'; // .site-content
		}
	}

	// Render footer
	if ( $use_custom_footer ) {
		echo magazine_blocks()->render_template_to_string( $footer[0]->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block markup includes <style>/<script>; sanitized at save time via WP's own content filters.
	} else {
		get_footer();
	}
	?>

	<?php wp_footer(); ?>
</body>

</html>
