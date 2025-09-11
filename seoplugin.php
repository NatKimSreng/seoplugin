<?php
/**
 * Plugin Name:       SEOPlugin
 * Plugin URI:        https://natkimsreng.shop
 * Description:       A WordPress SEO plugin for managing meta tags, generating sitemaps, and optimizing content.
 * Version:           1.0.0
 * Author:            NatKimsreng
 * Author URI:        https://natkimsreng.shop
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       seoplugin
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'SEOPLUGIN_VERSION', '1.0.0' );
define( 'SEOPLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEOPLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoloader for classes in include/
spl_autoload_register( function ( $class_name ) {
    if ( false !== strpos( $class_name, 'SEOPlugin' ) ) {
        $classes_dir = SEOPLUGIN_DIR . 'include/';
        $class_file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
        $path = $classes_dir . $class_file;
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
});

// Initialize the plugin
function seoplugin_init() {
    // Load text domain for translations
    load_plugin_textdomain( 'seoplugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Include admin and public functionality
    if ( is_admin() ) {
        require_once SEOPLUGIN_DIR . 'admin/seoplugin-admin.php';
    }
    require_once SEOPLUGIN_DIR . 'Public/seoplugin-public.php';

    // Instantiate core class
    $seoplugin = new SEOPlugin();
    $seoplugin->init();
}
add_action( 'plugins_loaded', 'seoplugin_init' );

// Activation hook
function seoplugin_activate() {
    // Add default options
    add_option( 'seoplugin_default_meta_description', 'Welcome to our site!' );
}
register_activation_hook( __FILE__, 'seoplugin_activate' );

// Deactivation hook
function seoplugin_deactivate() {
    // Clean up if needed
}
register_deactivation_hook( __FILE__, 'seoplugin_deactivate' );