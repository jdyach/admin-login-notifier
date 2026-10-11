<?php
/**
 * Settings page under Settings > Admin Login Notifier.
 *
 * @package Admin_Login_Notifier
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ALN_Settings {

    const PAGE_SLUG    = 'admin-login-notifier';
    const OPTION_GROUP = 'aln_settings_group';

    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'register_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function register_page(): void {
        add_options_page(
            __( 'Admin Login Notifier', 'admin-login-notifier' ),
            __( 'Login Notifier', 'admin-login-notifier' ),
            'manage_options',
            self::PAGE_SLUG,
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function register_settings(): void {
        register_setting( self::OPTION_GROUP, ALN_OPTIONS_KEY, [ 'sanitize_callback' => [ __CLASS__, 'sanitize' ] ] );
    }

    public static function sanitize( mixed $input ): array {
        $all_roles = wp_roles()->get_names();
        return [
            'notify_email'    => sanitize_email( $input['notify_email'] ?? get_option( 'admin_email' ) ),
            'notify_all'      => ! empty( $input['notify_all'] ) ? '1' : '',
            'roles_to_notify' => array_filter(
                array_map( 'sanitize_key', (array) ( $input['roles_to_notify'] ?? [] ) ),
                fn( $r ) => array_key_exists( $r, $all_roles )
            ),
        ];
    }

    public static function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'admin-login-notifier' ) ); }
        $options   = wp_parse_args( get_option( ALN_OPTIONS_KEY, [] ), [ 'notify_email' => get_option( 'admin_email' ), 'roles_to_notify' => ['administrator'], 'notify_all' => '' ] );
        $all_roles = wp_roles()->get_names();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Admin Login Notifier', 'admin-login-notifier' ); ?></h1>
            <p><?php esc_html_e( 'Sends an email alert when a user matching the selected roles logs in to the WP admin.', 'admin-login-notifier' ); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields( self::OPTION_GROUP ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Notify email', 'admin-login-notifier' ); ?></th>
                        <td><input type="email" name="<?php echo esc_attr( ALN_OPTIONS_KEY ); ?>[notify_email]" value="<?php echo esc_attr( $options['notify_email'] ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Notify on all logins', 'admin-login-notifier' ); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr( ALN_OPTIONS_KEY ); ?>[notify_all]" value="1" <?php checked( $options['notify_all'] ); ?> />
                        <?php esc_html_e( 'Send alert for every login, regardless of role', 'admin-login-notifier' ); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Roles to monitor', 'admin-login-notifier' ); ?></th>
                        <td>
                            <?php foreach ( $all_roles as $slug => $name ) : ?>
                            <label style="display:block;margin-bottom:4px">
                                <input type="checkbox" name="<?php echo esc_attr( ALN_OPTIONS_KEY ); ?>[roles_to_notify][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, (array) $options['roles_to_notify'], true ) ); ?> />
                                <?php echo esc_html( translate_user_role( $name ) ); ?>
                            </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Ignored when "Notify on all logins" is checked.', 'admin-login-notifier' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Settings', 'admin-login-notifier' ) ); ?>
            </form>
        </div>
        <?php
    }
}
