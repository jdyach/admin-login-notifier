<?php
/**
 * Hooks into wp_login and sends the notification email.
 *
 * @package Admin_Login_Notifier
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ALN_Notifier {

    public static function init(): void {
        add_action( 'wp_login', [ __CLASS__, 'on_login' ], 10, 2 );
    }

    /**
     * Fires on every successful login. Sends email if the user's role matches settings.
     *
     * @param string  $user_login Username.
     * @param WP_User $user       The logged-in user object.
     */
    public static function on_login( string $user_login, WP_User $user ): void {
        $options      = self::get_options();
        $notify_all   = ! empty( $options['notify_all'] );
        $roles_to_notify = (array) ( $options['roles_to_notify'] ?? ['administrator'] );

        // Check if this user's role should trigger a notification.
        if ( ! $notify_all ) {
            $user_roles = (array) $user->roles;
            if ( empty( array_intersect( $user_roles, $roles_to_notify ) ) ) {
                return;
            }
        }

        $to      = sanitize_email( $options['notify_email'] ?? get_option( 'admin_email' ) );
        $site    = get_bloginfo( 'name' );
        $subject = sprintf(
            /* translators: 1: site name, 2: username */
            __( '[%1$s] Admin login: %2$s', 'admin-login-notifier' ),
            $site,
            $user_login
        );

        $ip      = self::get_ip();
        $time    = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
        $roles   = implode( ', ', $user->roles );
        $admin   = admin_url();

        $message = sprintf(
            /* translators: multiple placeholders — see inline comments */
            __(
                "A user has logged in to the WordPress admin.\n\n" .
                "Site:      %1\$s\n" .
                "Username:  %2\$s\n" .
                "Role(s):   %3\$s\n" .
                "IP:        %4\$s\n" .
                "Time:      %5\$s\n\n" .
                "Admin URL: %6\$s\n\n" .
                "If this wasn't you, secure your site immediately.",
                'admin-login-notifier'
            ),
            $site,        // %1$s
            $user_login,  // %2$s
            $roles,       // %3$s
            $ip,          // %4$s
            $time,        // %5$s
            $admin        // %6$s
        );

        wp_mail( $to, $subject, $message );
    }

    /**
     * Returns the visitor's IP address, handling common proxy headers.
     */
    private static function get_ip(): string {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                // X-Forwarded-For can be a comma-separated list; take the first.
                $ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) )[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return __( 'Unknown', 'admin-login-notifier' );
    }

    private static function get_options(): array {
        return wp_parse_args( get_option( ALN_OPTIONS_KEY, [] ), [
            'notify_email'    => get_option( 'admin_email' ),
            'roles_to_notify' => ['administrator'],
            'notify_all'      => '',
        ] );
    }
}
