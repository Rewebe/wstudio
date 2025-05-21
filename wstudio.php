<?php
/*
 Plugin Name: Wstudio
 Description: Core plugin til galleri-system. Indeholder posttype og fælles funktioner.
 Version: 1.0
 Author: Weigang
*/

require_once plugin_dir_path(__FILE__) . 'includes/posttype.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-metaboxes.php';
require_once plugin_dir_path(__FILE__) . 'includes/module-loader.php';
error_log('🧩 module-loader blev inkluderet fra main plugin file');

add_action('admin_menu', function () {
    add_menu_page(
        'WStudio',
        'WStudio',
        'manage_options',
        'edit.php?post_type=wstudio_gallery',
        '',
        'dashicons-format-gallery',
        6
    );
});