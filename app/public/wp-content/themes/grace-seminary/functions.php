<?php
if (!defined('ABSPATH')) exit;
function grace_seminary_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    register_nav_menus(array('primary'=>'Primary Menu','footer'=>'Footer Menu'));
}
add_action('after_setup_theme','grace_seminary_setup');
function grace_seminary_assets() {
    wp_enqueue_style('grace-seminary', get_stylesheet_uri(), array(), '1.0.0');
    wp_enqueue_script('grace-seminary-js', get_template_directory_uri().'/assets/js/theme.js', array(), '1.0.0', true);
}
add_action('wp_enqueue_scripts','grace_seminary_assets');
function grace_seminary_fallback() {
    echo '<ul class="menu">';
    foreach(array('Accueil'=>'/','À propos'=>'/about/','Programmes'=>'/programmes/','Admissions'=>'/admission/','Contact'=>'/contact/') as $label=>$path)
        echo '<li><a href="'.esc_url(home_url($path)).'">'.esc_html($label).'</a></li>';
    echo '</ul>';
}
