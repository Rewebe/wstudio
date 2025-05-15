<?php
// === REGISTRER CUSTOM POST TYPE ===
add_action('init', function () {
    register_post_type('kundegalleri', [
        'show_in_rest' => true,
        'capability_type' => 'kundegalleri',
        'map_meta_cap' => true,
        'capabilities' => [
            'edit_post'               => 'edit_kundegalleri',
            'read_post'               => 'read_kundegalleri',
            'delete_post'             => 'delete_kundegalleri',
            'edit_posts'              => 'edit_kundegallerier',
            'edit_others_posts'       => 'edit_others_kundegallerier',
            'publish_posts'           => 'publish_kundegallerier',
            'read_private_posts'      => 'read_private_kundegallerier',
            'delete_posts'            => 'delete_kundegallerier',
            'delete_private_posts'    => 'delete_private_kundegallerier',
            'delete_published_posts'  => 'delete_published_kundegallerier',
            'delete_others_posts'     => 'delete_others_kundegallerier',
            'edit_private_posts'      => 'edit_private_kundegallerier',
            'edit_published_posts'    => 'edit_published_kundegallerier',
        ],
        'rewrite' => ['slug' => 'kunde', 'with_front' => false],
        'labels' => [
            'name' => 'Kundegallerier',
            'singular_name' => 'Kundegalleri',
            'add_new' => 'Tilføj ny kundegalleri',
            'add_new_item' => 'Tilføj nyt kundegalleri',
            'edit_item' => 'Rediger kundegalleri',
            'view_item' => 'Vis kundegalleri',
            'search_items' => 'Søg kundegallerier'
        ],
        'public' => true,
        'has_archive' => false,
        'show_in_menu' => true,
        'show_ui' => true,
        'menu_position' => 20,
        'supports' => ['title', 'thumbnail'],
        'menu_icon' => 'dashicons-format-gallery'
    ]);
});

// === TILFØJ CAPABILITIES TIL ADMIN ===
add_action('admin_init', function () {
    $role = get_role('administrator');
    if ($role) {
        $caps = [
            'edit_kundegalleri',
            'read_kundegalleri',
            'delete_kundegalleri',
            'edit_kundegallerier',
            'edit_others_kundegallerier',
            'publish_kundegallerier',
            'read_private_kundegallerier',
            'delete_kundegallerier',
            'delete_others_kundegallerier',
            'delete_private_kundegallerier',
            'delete_published_kundegallerier',
            'edit_private_kundegallerier',
            'edit_published_kundegallerier',
        ];
        foreach ($caps as $cap) {
            $role->add_cap($cap);
        }
    }
});

// === KOLONNE MED ANTAL BILLEDER I ADMIN ===
add_filter('manage_kundegalleri_posts_columns', function ($columns) {
    $columns['antal_billeder'] = 'Antal billeder';
    return $columns;
});

add_action('manage_kundegalleri_posts_custom_column', function ($column, $post_id) {
    if ($column === 'antal_billeder') {
        $slug = get_post_field('post_name', $post_id);
        $folder = wp_upload_dir()['basedir'] . '/kundegallerier/' . $slug . '/original/';
        if (file_exists($folder)) {
            $files = glob($folder . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            echo $files ? count($files) : 0;
        } else {
            echo '0';
        }
    }
}, 10, 2);

// === RÆKKEHANDLING: SE GALLERI ===
add_filter('post_row_actions', function ($actions, $post) {
    if ($post->post_type === 'kundegalleri') {
        $slug = $post->post_name;
        $url = site_url('/kunde/' . $slug);
        $actions['vis_galleri'] = '<a href="' . esc_url($url) . '" target="_blank">Se galleri</a>';
    }
    return $actions;
}, 10, 2);
