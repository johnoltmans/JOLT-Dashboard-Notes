<?php
/*
Plugin Name: Dashboard Notes
Plugin URI: https://github.com/Smoshed/WordPress-Plugins
Description: A simple plugin for personal notes in the admin dashboard.
Version: 1.1
Author: John Oltmans
Author URI: https://www.johnoltmans.nl/
*/

if (!defined('ABSPATH')) {
    exit;
}

// Register the "note" custom post type
function register_notes_cpt() {
    $labels = [
        'name' => 'Notes',
        'singular_name' => 'Note',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Note',
        'edit_item' => 'Edit Note',
        'new_item' => 'New Note',
        'view_item' => 'View Note',
        'search_items' => 'Search Notes',
        'not_found' => 'No notes found',
        'not_found_in_trash' => 'No notes found in Trash',
        'menu_name' => 'Notes'
    ];

    $args = [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 3,
        'menu_icon' => 'dashicons-welcome-write-blog',
        'supports' => ['title', 'editor'],
        'capability_type' => 'post'
    ];

    register_post_type('note', $args);
}
add_action('init', 'register_notes_cpt');

// Verberg frontend-toegang
add_action('template_redirect', function () {
    if (is_singular('note') || is_post_type_archive('note')) {
        wp_redirect(home_url());
        exit;
    }
});

// Dashboard widget voor alle Notes met edit-links
function notes_dashboard_widget() {
    $notes = new WP_Query([
        'post_type'      => 'note',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'modified',
        'order'          => 'DESC',
    ]);

    if ($notes->have_posts()) {
        echo '<ul>';
        while ($notes->have_posts()) {
            $notes->the_post();
            $edit_link = get_edit_post_link(get_the_ID());
            echo '<li style="margin-bottom: 10px;">';
            echo '<strong>' . esc_html(get_the_title()) . '</strong> ';
            echo '<a href="' . esc_url($edit_link) . '" style="margin-left: 10px;">✏️ Edit</a>';
            echo '<br><small>' . wp_trim_words(strip_tags(get_the_content()), 20) . '</small>';
            echo '</li><hr>';
        }
        echo '</ul>';
    } else {
        echo '<p>No notes yet.</p>';
    }

    echo '<p><a href="' . admin_url('edit.php?post_type=note') . '">View all notes</a></p>';

    wp_reset_postdata();
}

function add_notes_dashboard_widget() {
    wp_add_dashboard_widget(
        'notes_dashboard_widget',
        'Your Notes',
        'notes_dashboard_widget'
    );
}
add_action('wp_dashboard_setup', 'add_notes_dashboard_widget');
