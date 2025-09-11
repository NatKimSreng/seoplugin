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