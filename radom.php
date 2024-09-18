<?php
/**
 * Plugin Name:       Radom
 * Plugin URI:        https://radom.network/
 * Description:       Developer friendly crypto payment plugin.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Radom Pay Limited
 * Author URI:        https://radom.network/
 * Text Domain:       radom-pay-plugin
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 */

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'radom_woocommerce_not_active_notice');
    return;
}

function radom_woocommerce_not_active_notice()
{
    echo '
<div class="error"><p>';
    echo __('Radom Pay plugin requires WooCommerce to be activated.', 'radom-pay-plugin');
    echo '</p></div>';
}

function radom_init()
{
    include plugin_dir_path(__FILE__) . 'admin-page.php';
    include plugin_dir_path(__FILE__) . 'radom-gateway.php';
    include plugin_dir_path(__FILE__) . 'webhook.php';
}

add_action('plugins_loaded', 'radom_init');

function radom_pay_activate()
{
    add_option('radom_pay_do_activation_redirect', true);
}

register_activation_hook(__FILE__, 'radom_pay_activate');

function radom_pay_redirect()
{
    if (get_option('radom_pay_do_activation_redirect', false)) {
        delete_option('radom_pay_do_activation_redirect');
        wp_redirect(admin_url('options-general.php?page=radom-pay-plugin'));
        exit;
    }
}

add_action('admin_init', 'radom_pay_redirect');

function radom_pay_admin_notice()
{
    $options = get_option('radom_pay_plugin');
    $api_key = isset($options['radom_pay_api_key']) ? $options['radom_pay_api_key'] : '';

    if (!$api_key) {
        echo '
<div class="updated notice is-dismissible"><p>';
        echo __(
            'The Radom plugin is active but you haven\'t set up your API key yet. Please visit the <a
                href="' . admin_url('options-general.php?page=radom-pay-plugin') . '">settings page</a> to enter your
        API key.'
        );
        echo '</p></div>';
    }
}

add_action('admin_notices', 'radom_pay_admin_notice');

function radom_pay_plugin_add_settings_link($links)
{
    $settings_link = '<a href="' . admin_url('admin.php?page=radom-pay-plugin') . '">' . __('Configure', 'radom-pay-plugin')
    . '</a>';
    array_push($links, $settings_link);
    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'radom_pay_plugin_add_settings_link');

function radom_enqueue_admin_scripts($hook)
{
    if ('toplevel_page_radom-pay-plugin' != $hook) {
        return;
    }
    // Enqueue Select2 CSS
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    // Enqueue Select2 JS
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), null, true);
    // Enqueue custom admin script
    wp_enqueue_script('radom-admin-js', plugins_url('admin.js', __FILE__), array('jquery', 'select2-js'), null, true);
    // Enqueue custom admin CSS
    wp_enqueue_style('radom-admin-css', plugins_url('admin.css', __FILE__));
}

add_action('admin_enqueue_scripts', 'radom_enqueue_admin_scripts');

function radom_enqueue_checkout_styles()
{
    if (is_checkout()) {
        wp_enqueue_style('radom-checkout-css', plugins_url('radom-checkout.css', __FILE__));
    }
}

add_action('wp_enqueue_scripts', 'radom_enqueue_checkout_styles');

function radom_credit_link()
{
    $options = get_option('radom_pay_plugin');
    if (isset($options['show_credit_link']) && $options['show_credit_link'] === 'yes') {
        echo '<style>
                .radom-credit-link {
                    font-size: 0.5em; /* Small, but not too small */
                    line-height: 1;
                    margin: 0;
                    opacity: 0.5; /* Reduce opacity to make it less obtrusive */
                    background-color: inherit; /* Inherit the footer background color */
                    color: inherit; /* Inherit the footer text color */
                    text-align: center; /* Center the text */
                }
                .radom-credit-link a {
                    color: inherit; /* Inherit the footer link color */
                    text-decoration: none; /* Remove underline for a cleaner look */
                }
              </style>';
        echo '<p class="radom-credit-link">Crypto payments powered by <a href="https://radom.com" rel="dofollow">Radom</a></p>';
    }
}
add_action('wp_footer', 'radom_credit_link');
