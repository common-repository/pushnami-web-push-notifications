<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly
/*
Plugin Name: Pushnami - Web Push Notifications
Plugin URI: https://wordpress.org/plugins/pushnami-web-push-notifications
Description: Capture & Monetize Your Audience. The leader for web push, mobile push notifications, and email. Trusted by more than 20K brands to send 10 billion messages per month.
Version: 1.1.4
Author: Pushnami
Author URI: https://www.pushnami.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('PUSHNAMI_PLUGIN_DIR')) {
    define('PUSHNAMI_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('PUSHNAMI_PLUGIN_URL')) {
    define('PUSHNAMI_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('PUSHNAMI_DEFAULT_GCM')) {
    define('PUSHNAMI_DEFAULT_GCM', '733213273952');
}

if (!defined('PUSHNAMI_DEFAULT_API_URL')) {
    define('PUSHNAMI_DEFAULT_API_URL', 'https://api.pushnami.com');
}

require_once PUSHNAMI_PLUGIN_DIR . 'includes/class-pushnami.php';

register_activation_hook(__FILE__, array('WPPushnami', 'installFunctions'));

add_action('admin_init', array('WPPushnami', 'installFunctions'));

//start the plugin
WPPushnami::init();
