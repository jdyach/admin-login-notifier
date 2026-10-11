<?php
/**
 * Plugin Name:       Admin Login Notifier
 * Plugin URI:        https://github.com/jdyach/admin-login-notifier
 * Description:       Sends an email alert to the site admin whenever a user logs into the WordPress dashboard, including timestamp, username, and IP address.
 * Version:           1.0.0
 * Author:            Jacqui Dyachenko
 * Author URI:        https://jacquid.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       admin-login-notifier
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'ALN_VERSION',    '1.0.0' );
define( 'ALN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ALN_OPTIONS_KEY', 'aln_settings' );

require_once ALN_PLUGIN_DIR . 'includes/class-aln-notifier.php';
require_once ALN_PLUGIN_DIR . 'includes/class-aln-settings.php';

function aln_init(): void {
    ALN_Notifier::init();
    ALN_Settings::init();
}
add_action( 'plugins_loaded', 'aln_init' );

register_activation_hook( __FILE__, function (): void {
    if ( false === get_option( ALN_OPTIONS_KEY ) ) {
        add_option( ALN_OPTIONS_KEY, [
            'notify_email'   => get_option( 'admin_email' ),
            'roles_to_notify'=> ['administrator'],
            'notify_all'     => '',
        ] );
    }
} );
