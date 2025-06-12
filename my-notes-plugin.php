<?php
/*
Plugin Name: JOLT Dashboard Notes
Plugin URI: https://github.com/johnoltmans/JOLT-Dashboard-Notes
Description: A simple plugin for personal notes in the admin dashboard.
Version: 1.5
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

// Voeg aangepaste admin-pagina toe voor alleen-lezen weergave
function add_note_view_page() {
    add_submenu_page(
        null,
        'View Note',
        'View Note',
        'edit_posts',
        'view-note',
        'render_note_view_page'
    );
}
add_action('admin_menu', 'add_note_view_page');

// Toon de inhoud van één note
function render_note_view_page() {
    if (!isset($_GET['note_id']) || !current_user_can('edit_posts')) {
        wp_die('Not allowed');
    }

    $note_id = intval($_GET['note_id']);
    $note = get_post($note_id);

    if (!$note || $note->post_type !== 'note') {
        wp_die('Note not found');
    }

    $edit_link = get_edit_post_link($note_id);
    $back_link = admin_url('edit.php?post_type=note');

    echo '<div class="wrap">';
    echo '<h1>' . esc_html($note->post_title) . '</h1>';

    // Waarschuwing met rood uitroepteken
    echo '<div style="background: #fff8c4; border-left: 4px solid #dc3232; padding: 12px; margin-bottom: 20px;">';
    echo '<strong style="color: #dc3232;">❗ Admin Only:</strong> This note is only visible inside the WordPress admin and is not accessible to the public.';
    echo '</div>';

    echo '<p><a href="' . esc_url($edit_link) . '" class="button button-primary">✏️ Edit this note</a></p>';
    echo wpautop(esc_html($note->post_content));
    echo '<p><a href="' . esc_url($back_link) . '">← Back to Notes</a></p>';
    echo '</div>';
}


// Dashboard widget met samenvatting + links
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
            $note_id    = get_the_ID();
            $title      = esc_html(get_the_title());
            $excerpt    = wp_trim_words(strip_tags(get_the_content()), 20);
            $edit_link  = get_edit_post_link($note_id);
            $read_link  = admin_url('admin.php?page=view-note&note_id=' . $note_id);

            echo '<li style="margin-bottom: 10px;">';
            echo '<strong>' . $title . '</strong>';
            echo '<br><small>' . esc_html($excerpt) . '</small>';
            echo '<div style="margin-top: 5px;">';
            echo '<a href="' . esc_url($edit_link) . '">Edit</a> | ';
            echo '<a href="' . esc_url($read_link) . '">Read</a>';
            echo '</div>';
            echo '</li><hr>';
        }
        echo '</ul>';
    } else {
        echo '<p>No notes yet.</p>';
    }

    echo '<p><a href="' . admin_url('edit.php?post_type=note') . '">View all notes</a></p>';

    wp_reset_postdata();
}


// Voeg widget toe aan dashboard
function add_notes_dashboard_widget() {
    wp_add_dashboard_widget(
        'notes_dashboard_widget',
        'Your Notes',
        'notes_dashboard_widget'
    );
}
add_action('wp_dashboard_setup', 'add_notes_dashboard_widget');

// Voeg "Read" toe aan de rij-acties op edit.php?post_type=note
function add_read_link_to_note_row_actions($actions, $post) {
    if ($post->post_type === 'note') {
        $read_url = admin_url('admin.php?page=view-note&note_id=' . $post->ID);
        $read_link = '<a href="' . esc_url($read_url) . '">Read</a>';

        // Voeg toe vóór 'trash'
        $new_actions = [];

        foreach ($actions as $key => $action) {
            if ($key === 'trash') {
                $new_actions['read'] = $read_link;
            }
            $new_actions[$key] = $action;
        }

        return $new_actions;
    }

    return $actions;
}
add_filter('post_row_actions', 'add_read_link_to_note_row_actions', 10, 2);
