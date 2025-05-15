<?php
// admin-today-bookings.php

if (!current_user_can('manage_options')) {
    wp_die('Du har ikke adgang til denne side.');
}

global $wpdb;
$customers_table = $wpdb->prefix . 'latepoint_customers';
$bookings_table  = $wpdb->prefix . 'latepoint_bookings';

$today = date('Y-m-d');

$bookings = $wpdb->get_results(
    $wpdb->prepare("
        SELECT 
            b.booking_code,
            b.start_time,
            c.first_name,
            c.last_name
        FROM {$bookings_table} b
        LEFT JOIN {$customers_table} c ON b.customer_id = c.id
        WHERE b.start_date = %s 
          AND b.status = 'approved'
        ORDER BY b.start_time ASC
    ", $today)
);

// Funktion til format HH:MM
function format_time($minutes) {
    $hours = floor($minutes / 60);
    $mins  = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $mins);
}

echo '<div class="wrap">';
echo '<h1>Dagens bookinger (' . esc_html($today) . ')</h1>';

if ($bookings) {
    echo '<ul style="margin-top:1rem;">';
    foreach ($bookings as $b) {
        $navn = trim($b->first_name . ' ' . $b->last_name);
        $tid  = format_time($b->start_time);

        // Brug kun booking_code
        $url = admin_url('admin-post.php?action=opret_galleri&booking_code=' . urlencode($b->booking_code));

        echo '<li style="margin-bottom:0.5rem;">';
        echo '<strong>🕒 ' . esc_html($tid) . '</strong> – ' . esc_html($navn) . ' ';
        echo '<a href="' . esc_url($url) . '" class="button button-secondary">Opret galleri</a>';
        echo '</li>';
    }
    echo '</ul>';
} else {
    echo '<p>Ingen bookinger fundet for i dag.</p>';
}

echo '</div>';
?>