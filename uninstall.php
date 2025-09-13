<?php
/**
 * Uninstall SEOPlugin
 *
 * Deletes all plugin data when uninstalled via WordPress admin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'seoplugin_default_meta_description' );
delete_post_meta_by_key( '_seoplugin_meta_title' );
delete_post_meta_by_key( '_seoplugin_meta_description' );

// Delete term meta
$terms = get_terms( [
    'taxonomy' => get_taxonomies( [ 'public' => true ] ),
    'hide_empty' => false,
    'fields' => 'ids'
] );

foreach ( $terms as $term_id ) {
    delete_term_meta( $term_id, '_seoplugin_meta_title' );
    delete_term_meta( $term_id, '_seoplugin_meta_description' );
    delete_term_meta( $term_id, '_seoplugin_og_image_id' );
    delete_term_meta( $term_id, '_seoplugin_focus_keyword' );
    delete_term_meta( $term_id, '_seoplugin_robots_meta' );
    delete_term_meta( $term_id, '_seoplugin_canonical_url' );
}