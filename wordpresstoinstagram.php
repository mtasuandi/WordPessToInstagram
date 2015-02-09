<?php
/*
Plugin Name: WordPress To Instagram
Plugin URI: http://kreaxy.com
Description: Automatic posting of featured images into Instagram account.
Version: 0.1
Author: Kreaxy Digital Media
Author URI: http://kreaxy.com
License: GNU GPL v2
 */

if ( ! defined( 'ABSPATH' ) ) die( 'Cheating, uh?' );

define( 'WORDPRESSTOINSTAGRAM_VERSION', '0.1' );
define( 'WORDPRESSTOINSTAGRAM_SLUG', 'wordpresstoinstagram' );
define( 'WORDPRESSTOINSTAGRAM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WORDPRESSTOINSTAGRAM_LICENSE_URL', 'http://opensource.kreaxy.com' );
define( 'WORDPRESSTOINSTAGRAM_PLUGIN_UPDATER_URL', 'http://kreaxy.com/updater/plugins/wordpresstoinstagram/metadata.json' );
define( 'WORDPRESSTOINSTAGRAM_LICENSE_STATUS', get_option( '__wordpresstoinstagram_license_status' ) );

class WordPressToInstagram {
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'wordpresstoinstagram_activation' ) );
		add_action( 'admin_menu', array( $this, 'wordpresstoinstagram_admin_menu' ) );
		add_action( 'init', array( $this, 'wordpresstoinstagram_init' ) );
	}

	public function wordpresstoinstagram_activation() {
		if ( !function_exists( 'dbDelta' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}

		global $wpdb;
		$tableInstagramAccounts = $wpdb->prefix . 'wpinstagram_accounts';

		$sqlInstagrams = <<<SQL
CREATE TABLE {$tableInstagramAccounts} (
id INT(11) unsigned NOT NULL AUTO_INCREMENT,
username VARCHAR(50) NOT NULL,
password VARCHAR(50) NOT NULL,
is_active INT(1) NOT NULL DEFAULT 1,
created_at DATETIME NOT NULL,
updated_at TIMESTAMP NOT NULL,
deleted_at DATETIME NULL,
PRIMARY KEY id (id)
) DEFAULT CHARACTER SET utf8, DEFAULT COLLATE utf8_general_ci;
SQL;
		dbDelta( $sqlInstagrams );
	}
	
	public function wordpresstoinstagram_admin_menu() {
		$licenseStatus = WORDPRESSTOINSTAGRAM_LICENSE_STATUS;
		
		if ( empty( $licenseStatus ) ) {
			add_menu_page( __( 'WordPress To Instagram Activation', WORDPRESSTOINSTAGRAM_SLUG . '-activation' ),
				__( 'WP To Instagram', WORDPRESSTOINSTAGRAM_SLUG . '-activation' ),
				'manage_options',
				WORDPRESSTOINSTAGRAM_SLUG . '-activation',
				array( $this, 'wordpresstoinstagram_display_page_license' ), 'dashicons-lock'
			);
		} else {
			add_menu_page( __( 'WordPress To Instagram', WORDPRESSTOINSTAGRAM_SLUG ),
				__( 'WP To Instagram', WORDPRESSTOINSTAGRAM_SLUG ),
				'manage_options',
				WORDPRESSTOINSTAGRAM_SLUG,
				array( $this, 'wordpresstoinstagram_display_page' ), 'dashicons-lock'
			);
		}
	}

	public function wordpresstoinstagram_display_page_license() {
		echo <<<LICENSEPAGE
<div class="wrap">
	<div class="welcome-panel">
		<div class="welcome-panel-content">
			<h2>Activate WordPress To Instagram</h2>
			<div class="welcome-panel-column-container">
				<form action="" method="POST">
					<p>
						<input type="email" name="kreaxy_license_email" style="width:30%;"/>
						<br/><em>Enter your valid license email</em>
					</p>
					<p>
						<input type="submit" name="submit" value="Activate License" class="button-primary"/>
					</p>
				</form>
			</div>
		</div>
	</div>
</div>
LICENSEPAGE;
	}

	public function wordpresstoinstagram_display_page() {
		$tab = sanitize_text_field( $_GET['tab'] );
		$node = sanitize_text_field( $_GET['node'] );

		switch ( $tab ) {
			case 'instagrams':
				if ( $node == 'instagram' ) {
					require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'views/instagram.php' );
				} else {
					require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'views/tables/instagrams.tables.class.php' );
					require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'views/instagrams.php' );
				}
				break;
			default:
				require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'views/dashboard.php' );
				break;
		}
	}

	public function wordpresstoinstagram_init() {
		require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'codes/autoupdate.class.php' );
		$autoUpdate = new PluginUpdateChecker( WORDPRESSTOINSTAGRAM_PLUGIN_UPDATER_URL, __FILE__, WORDPRESSTOINSTAGRAM_SLUG );
	}
}new WordPressToInstagram();
