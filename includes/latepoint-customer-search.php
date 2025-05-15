<?php
/*
Plugin Name: LatePoint Customer Search
Description: Søg i LatePoint-kunder via navn eller e-mail (demo/test).
Version: 0.2
Author: Weigang
*/

add_action('admin_menu', function() {
    add_menu_page('LatePoint Kunder', 'LatePoint Kunder', 'manage_options', 'latepoint-customer-search', 'lcs_admin_page');
});

function lcs_admin_page() {
    ?>
    <div class="wrap">
        <h1>Søg i LatePoint-kunder</h1>
        <form method="get">
            <input type="hidden" name="page" value="latepoint-customer-search">
            <input type="text" name="search" placeholder="Skriv navn eller e-mail" value="<?php echo esc_attr($_GET['search'] ?? ''); ?>">
            <button class="button button-primary">Søg</button>
        </form>

        <?php
        if (!empty($_GET['search'])) {
            $results = lcs_find_customer($_GET['search']);
            if ($results) {
                echo '<h2>Resultater:</h2><ul>';
                foreach ($results as $c) {
                    $navn = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
                    echo '<li><strong>' . esc_html($navn) . '</strong> — ' . esc_html($c->email) . ' — ' . esc_html($c->phone) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>Ingen kunder fundet.</p>';
            }
        }
        ?>
    </div>
    <?php
}

function lcs_find_customer($search) {
    global $wpdb;
    $table = $wpdb->prefix . 'latepoint_customers';

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table
             WHERE email LIKE %s
                OR first_name LIKE %s
                OR last_name LIKE %s",
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%'
        )
    );
}
