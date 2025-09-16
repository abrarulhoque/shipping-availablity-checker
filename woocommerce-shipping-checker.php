<?php
/**
 * Plugin Name: WooCommerce ZIP Code Shipping Checker
 * Plugin URI:
 * Description: Provides a custom page template and functionality to check shipping availability by ZIP code.
 * Version: 1.0.0
 * Author: Abrar
 * Author URI: https://abrarulhoque.com
 * Text Domain: wc-shipping-checker
 * Requires WooCommerce: 3.0.0
 */

// Don't allow direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_SHIPPING_CHECKER_PATH', plugin_dir_path(__FILE__));
define('WC_SHIPPING_CHECKER_URL', plugin_dir_url(__FILE__));
define('WC_SHIPPING_CHECKER_VERSION', '1.0.0');

/**
 * Check if WooCommerce is active
 */
function wc_shipping_checker_is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', array());
    
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }
    
    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

/**
 * Initialize the plugin
 */
function wc_shipping_checker_init() {
    // Only load if WooCommerce is active
    if (!wc_shipping_checker_is_woocommerce_active()) {
        add_action('admin_notices', 'wc_shipping_checker_woocommerce_required_notice');
        return;
    }
    
    // Include required files
    require_once WC_SHIPPING_CHECKER_PATH . 'functions.php';
    
    // Register the page template
    add_filter('theme_page_templates', 'wc_shipping_checker_add_page_template');
    add_filter('template_include', 'wc_shipping_checker_load_page_template');
}
add_action('plugins_loaded', 'wc_shipping_checker_init');

/**
 * Admin notice if WooCommerce is not active
 */
function wc_shipping_checker_woocommerce_required_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('WooCommerce ZIP Code Shipping Checker requires WooCommerce to be installed and active.', 'wc-shipping-checker'); ?></p>
    </div>
    <?php
}

/**
 * Add the shipping checker page template to the template dropdown
 */
function wc_shipping_checker_add_page_template($templates) {
    $templates['page-shipping-checker.php'] = __('Shipping Availability Checker', 'wc-shipping-checker');
    return $templates;
}

/**
 * Load the shipping checker page template when needed
 */
function wc_shipping_checker_load_page_template($template) {
    global $post;
    
    if (!$post) {
        return $template;
    }
    
    $page_template_slug = get_page_template_slug($post->ID);
    
    if ('page-shipping-checker.php' === $page_template_slug) {
        $template = WC_SHIPPING_CHECKER_PATH . 'page-shipping-checker.php';
    }
    
    return $template;
}

/**
 * Activation hook - copies files to the plugin directory
 */
function wc_shipping_checker_activate() {
    // Nothing to do on activation yet
}
register_activation_hook(__FILE__, 'wc_shipping_checker_activate'); 
