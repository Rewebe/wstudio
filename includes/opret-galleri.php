<?php
// includes/opret-galleri.php

if (!defined('ABSPATH')) exit;

add_action('admin_post_opret_galleri', 'weigang_opret_galleri');

function weigang_opret_galleri() {
    if (!current_user_can('edit_posts')) {
        wp_die('Ingen adgang.');
    }

    global $wpdb;
    $booking_code = sanitize_text_field($_GET['booking_code'] ?? '');

    if (empty($booking_code)) {
        wp_die('Mangler bookingkode.');
    }

    // Slå alt bookingdata op baseret på booking_code
    $booking = $wpdb->get_row($wpdb->prepare("
        SELECT 
            b.booking_code, b.status, b.start_date, b.start_time, b.service_id,
            c.first_name, c.last_name, c.email,
            s.name AS service_name
        FROM {$wpdb->prefix}latepoint_bookings b
        LEFT JOIN {$wpdb->prefix}latepoint_customers c ON b.customer_id = c.id
        LEFT JOIN {$wpdb->prefix}latepoint_services s ON b.service_id = s.id
        WHERE b.booking_code = %s
        LIMIT 1
    ", $booking_code));

    if (!$booking) {
        wp_die('Kunne ikke finde booking baseret på bookingkode.');
    }

    // Format start dato og tid
    $booking_start_datetime = '';
    if (!empty($booking->start_date) && is_numeric($booking->start_time)) {
        $hours = floor($booking->start_time / 60);
        $mins  = $booking->start_time % 60;
        $booking_start_datetime = $booking->start_date . ' ' . sprintf('%02d:%02d:00', $hours, $mins);
    }

    $generated_password = wp_generate_password(10, false);

    $post_id = wp_insert_post([
        'post_type'   => 'kundegalleri',
        'post_title'  => 'Galleri #' . time(),
        'post_status' => 'draft',
        'meta_input'  => [
            'kunde_first_name'       => $booking->first_name,
            'kunde_last_name'        => $booking->last_name,
            'kunde_email'            => $booking->email,
            'kunde_password'         => $generated_password,
            'booking_code'           => $booking->booking_code,
            'booking_status'         => $booking->status,
            'booking_start_datetime' => $booking_start_datetime,
            'service_id'             => $booking->service_id,
            'service_name'           => $booking->service_name,
        ]
    ]);

    if (is_wp_error($post_id)) {
        wp_die('Fejl ved oprettelse: ' . $post_id->get_error_message());
    }

    wp_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
    exit;
}
?>