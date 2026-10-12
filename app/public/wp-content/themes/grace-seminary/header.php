<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<div class="topbar"><div class="container"><span>Formation théologique & leadership</span><span><?php bloginfo('description'); ?></span></div></div>
<header class="site-header"><div class="container nav">
<a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><span class="mark">✦</span><span><?php bloginfo('name'); ?><small>Formation, caractère, service</small></span></a>
<button class="toggle" aria-label="Menu">☰</button><nav>
<?php if(has_nav_menu('primary')) wp_nav_menu(array('theme_location'=>'primary','container'=>false,'menu_class'=>'menu')); else { grace_seminary_fallback(); } ?>
</nav></div></header>
