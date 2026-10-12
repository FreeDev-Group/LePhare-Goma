<?php
if (!defined('ABSPATH')) exit;
function theology_academy_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    register_nav_menus(array('primary'=>'Primary Menu','footer'=>'Footer Menu'));
}
add_action('after_setup_theme','theology_academy_setup');
function theology_academy_assets() {
    wp_enqueue_style('theology-academy', get_stylesheet_uri(), array(), '1.0.0');
    wp_enqueue_script('theology-academy-js', get_template_directory_uri().'/assets/js/theme.js', array(), '1.0.0', true);
}
add_action('wp_enqueue_scripts','theology_academy_assets');
function theology_academy_fallback() {
    echo '<ul class="menu">';
    foreach(array('Accueil'=>'/','À propos'=>'/about/','Programmes'=>'/programmes/','Admissions'=>'/admission/','Contact'=>'/contact/') as $label=>$path)
        echo '<li><a href="'.esc_url(home_url($path)).'">'.esc_html($label).'</a></li>';
    echo '</ul>';
}
