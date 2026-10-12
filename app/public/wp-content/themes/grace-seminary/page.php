<?php get_header(); ?><main><section class="page-title"><div class="container"><h1><?php the_title(); ?></h1></div></section>
<section class="content"><div class="container"><article class="entry"><?php while(have_posts()):the_post();if(has_post_thumbnail())the_post_thumbnail('large');the_content();endwhile;?></article></div></section></main><?php get_footer(); ?>
