<?php
// Register Custom Post Type: wstudio
function wstudio_register_post_type() {
    $labels = [
        'name'               => 'Gallerier',
        'singular_name'      => 'Galleri',
        'menu_name'          => 'WStudio',
        'name_admin_bar'     => 'Galleri',
        'add_new'            => 'Tilføj ny',
        'add_new_item'       => 'Tilføj nyt galleri',
        'new_item'           => 'Nyt galleri',
        'edit_item'          => 'Rediger galleri',
        'view_item'          => 'Vis galleri',
        'all_items'          => 'Alle gallerier',
        'search_items'       => 'Søg i gallerier',
        'not_found'          => 'Ingen gallerier fundet',
        'not_found_in_trash' => 'Ingen gallerier i papirkurven',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=wstudio_gallery',
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-format-gallery',
        'supports'           => ['title'],
    ];

    register_post_type('wstudio_gallery', $args);
}

add_action('init', 'wstudio_register_post_type');