<?php
/**
 * Demo content for the WordPress Playground "Try it live" link.
 *
 * Runs once inside Playground: blueprint.json embeds this file (rebuild it
 * with `php bin/build-blueprint.php`). Playground cannot send email, so a demo-only mailbox captures the alert and shows it on the plugin's settings page.
 *
 * Not loaded by the plugin.
 *
 * @package Admin_Login_Notifier
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

wp_set_current_user( 1 );

// Start from a clean site: remove the default "Hello world!" post and sample page.
foreach ( get_posts( array( 'post_type' => array( 'post', 'page' ), 'numberposts' => -1, 'post_status' => 'any' ) ) as $demo_old ) {
	wp_delete_post( $demo_old->ID, true );
}

update_option( 'blogname', 'Admin Login Notifier' );
update_option( 'blogdescription', 'Live demo' );
// Pretty permalinks. Set them through WP_Rewrite, not update_option(): in
// Playground the routing rules already exist by now, and only this updates
// WordPress's in-memory copy so the flush rebuilds them correctly.
$GLOBALS['wp_rewrite']->set_permalink_structure( '/%postname%/' );
flush_rewrite_rules();

/**
 * Upload one of the Fieldnote theme's images and return its attachment ID.
 *
 * @param string $name Image file name without extension.
 * @return int Attachment ID, or 0 when the theme image is unavailable.
 */
function demo_theme_image( $name ) {
	$source = get_theme_file_path( "assets/images/$name.webp" );
	if ( ! file_exists( $source ) ) {
		return 0;
	}
	$upload = wp_upload_bits( "$name.webp", null, file_get_contents( $source ) );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/webp',
			'post_title'     => $name,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	return $id;
}

/**
 * A long article in block markup: H2 sections with H3 subsections.
 *
 * @param array $sections Section titles.
 * @param int   $paras    Paragraphs per section.
 * @return string Block markup.
 */
function demo_article( $sections, $paras = 2 ) {
	$text = 'A good website is less about the launch and more about the years after it. The people editing it next need clear patterns, sensible defaults and documentation they will actually read. Small decisions made early, like naming, structure and what is configurable, decide how easy every later change will be.';
	$html = '';
	foreach ( $sections as $section ) {
		$html .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $section ) . '</h2><!-- /wp:heading -->';
		for ( $i = 0; $i < $paras; $i++ ) {
			$html .= '<!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->';
		}
		$html .= '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . esc_html( $section ) . ' checklist</h3><!-- /wp:heading -->';
		$html .= '<!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->';
	}
	return $html;
}

/**
 * Insert a published post or page.
 *
 * @param array $args wp_insert_post() arguments.
 * @return int Post ID.
 */
function demo_post( $args ) {
	return wp_insert_post( array_merge( array( 'post_status' => 'publish' ), $args ) );
}

/**
 * Write a small demo-only must-use plugin. These files exist only inside
 * the Playground demo; they are not part of the plugin.
 *
 * @param string $name File name.
 * @param string $code PHP source.
 */
function demo_mu_plugin( $name, $code ) {
	wp_mkdir_p( WPMU_PLUGIN_DIR );
	file_put_contents( trailingslashit( WPMU_PLUGIN_DIR ) . $name, $code );
}

/** Skip the block editor's welcome guide so visitors land straight on the content. */
function demo_skip_welcome_guide( $extra = array() ) {
	update_user_meta(
		1,
		$GLOBALS['wpdb']->get_blog_prefix() . 'persisted_preferences',
		array_merge(
			array(
				'core/edit-post' => array( 'welcomeGuide' => false ),
				'core'           => array( 'welcomeGuide' => false ),
				'_modified'      => gmdate( 'c' ),
			),
			$extra
		)
	);
}

// Demo-only mailbox: capture wp_mail() instead of sending, and show the
// captured alerts on the plugin's settings screen.
demo_mu_plugin(
	'demo-mailbox.php',
	<<<'PHP'
<?php
/**
 * Plugin Name: Demo mailbox (Playground only)
 * Description: Captures outgoing email so the login alert can be shown in the browser.
 */
add_filter(
	'pre_wp_mail',
	function ( $result, $atts ) {
		$log = get_option( 'demo_mailbox', array() );
		array_unshift(
			$log,
			array(
				'to'      => is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : $atts['to'],
				'subject' => $atts['subject'],
				'message' => $atts['message'],
			)
		);
		update_option( 'demo_mailbox', array_slice( $log, 0, 5 ), false );
		return true;
	},
	10,
	2
);
add_action(
	'admin_notices',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || 'settings_page_admin-login-notifier' !== $screen->id ) {
			return;
		}
		$log = get_option( 'demo_mailbox', array() );
		echo '<div class="notice notice-info"><p><strong>Demo mailbox.</strong> Playground cannot send real email, so alerts sent by this plugin are captured and shown here. Log out and back in to trigger another one.</p>';
		foreach ( $log as $mail ) {
			printf(
				'<div style="background:#fff;border:1px solid #dcdcde;padding:12px 16px;margin:0 0 12px"><p style="margin:0 0 4px"><strong>To:</strong> %1$s<br><strong>Subject:</strong> %2$s</p><pre style="white-space:pre-wrap;margin:8px 0 0;font-size:13px">%3$s</pre></div>',
				esc_html( $mail['to'] ),
				esc_html( $mail['subject'] ),
				esc_html( $mail['message'] )
			);
		}
		echo '</div>';
	}
);
PHP
);

// The mu-plugin is not loaded yet in this request, so register the same
// capture filter here before simulating a dashboard login.
require_once WPMU_PLUGIN_DIR . '/demo-mailbox.php';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '203.0.113.42';
do_action( 'wp_login', 'admin', get_userdata( 1 ) );
