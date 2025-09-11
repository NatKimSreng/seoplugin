<?php
/**
 * SEOPlugin Core Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SEOPlugin {
    public function init() {
        // Initialize core functionality
    }

    // Example core method
    public function get_default_meta_description() {
        return get_option( 'seoplugin_default_meta_description', 'Welcome to our site!' );
    }
}