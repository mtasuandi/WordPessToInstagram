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

/**
 * Prevent the plugin accessed direclty
 */
if ( ! defined( 'ABSPATH' ) ) die( 'Cheating, uh?' );

define( 'WORDPRESSTOINSTAGRAM_VERSION', '0.1' );
define( 'WORDPRESSTOINSTAGRAM_SLUG', 'wordpresstoinstagram' );
define( 'WORDPRESSTOINSTAGRAM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WORDPRESSTOINSTAGRAM_LICENSE_URL', 'http://opensource.kreaxy.com' );
define( 'WORDPRESSTOINSTAGRAM_PLUGIN_UPDATER_URL', 'http://kreaxy.com/updater/plugins/wordpresstoinstagram/metadata.json' );
define( 'WORDPRESSTOINSTAGRAM_LICENSE_STATUS', get_option( '__wordpresstoinstagram_license_status' ) );

/**
 * Class constructor
 */
class WordPressToInstagram {
	/**
	 * WordPress Hooks
	 */
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'wordpresstoinstagram_activation' ) );
		add_action( 'admin_menu', array( $this, 'wordpresstoinstagram_admin_menu' ) );
		add_action( 'init', array( $this, 'wordpresstoinstagram_init' ) );
		add_action( 'admin_init', array( $this, 'wordpresstoinstagram_handle_license' ) );
		add_action( 'admin_init', array( $this, 'wordpresstoinstagram_handle_account' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wordpresstoinstagram_admin_enqueue_scripts' ) );
	}

	/**
	 * Create related table during plugin activation
	 */
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
	
	/**
	 * Check for the license value
	 * If the license is empty then display the licensing page otherwise display normal page
	 */
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

	/**
	 * HTML Form for licensing
	 */
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
						<input type="submit" name="validate_license" value="Activate License" class="button-primary"/>
					</p>
				</form>
			</div>
		</div>
	</div>
</div>
LICENSEPAGE;
	}

	/**
	 * Plugin page
	 */
	public function wordpresstoinstagram_display_page() {
		$tab = sanitize_text_field( $_GET['tab'] );
		$node = sanitize_text_field( $_GET['node'] );

		switch ( $tab ) {
			case 'accounts':
				if ( $node == 'account' ) {
					require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'views/accounts/account.php' );
				} else {
					require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'codes/tables/accounts.tables.class.php' );
					require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'views/accounts/accounts.php' );
				}
				break;
			default:
				require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'views/dashboard.php' );
				break;
		}
	}

	/**
	 * Plugin Autoupdater
	 * The url is pointed to http://opensource.kreaxy.com/
	 */
	public function wordpresstoinstagram_init() {
		require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'codes/autoupdate.class.php' );
		$autoUpdate = new PluginUpdateChecker( WORDPRESSTOINSTAGRAM_PLUGIN_UPDATER_URL, __FILE__, WORDPRESSTOINSTAGRAM_SLUG );
	}

	/**
	 * Handle validate license
	 */
	public function wordpresstoinstagram_handle_license() {
		if ( !empty( $_POST['validate_license'] ) && $_POST['validate_license'] == 'Activate License' ) {
			$licenseEmail = sanitize_text_field( $_POST['kreaxy_license_email'] );

			if ( empty( $licenseEmail ) ) {
				echo '<div class="error"><p>Please enter license email.</p></div>';
			}
			
			$apiParamsTest = array(
				'kreaxyLicense' => 'true',
				'action' => 'testConnection',
			);
			$apiTestUrl = add_query_arg( $apiParamsTest, WORDPRESSTOINSTAGRAM_LICENSE_URL );
			$connectTest = wp_remote_get( $apiTestUrl );
			if ( is_wp_error( $connectTest ) ) {
				$connectTest = $this->cCurl( $apiTestUrl );
			} else {
				$connectTest = wp_remote_retrieve_body( $connectTest );
			}
			
			if ( $connectTest != 'CONNECTION_OK' ) {
				echo <<<ERRORMASGAN
<div class="error"><p>Unable to connect to the licensing server. Please contact <a href="mailto:connect@kreaxy.com">connect@kreaxy.com</a> to get assistance with activating your license.</p></div>
ERRORMASGAN;
			} else {
				if ( !empty( $licenseEmail ) ) {
					$apiParams = array(
						'kreaxyLicense' => 'true',
						'action' => 'activateLicense',
						'kreaxyLicenseEmail' => urlencode( trim( $licenseEmail ) ),
						'kreaxyLicensePluginSlug' => urlencode( WORDPRESSTOINSTAGRAM_SLUG )
					);
					
					$urlApi = add_query_arg( $apiParams, WORDPRESSTOINSTAGRAM_LICENSE_URL );
					$dataLicense = wp_remote_get( $urlApi );
					if ( is_wp_error( $dataLicense ) ) {
						$dataLicense = $this->cCurl( $urlApi );
					} else {
						$dataLicense = wp_remote_retrieve_body( $dataLicense );
					}

					if ( !empty( $dataLicense ) ) {
						$objRequestStatus = json_decode( $dataLicense );
						if ( $objRequestStatus->code == 'SUCCESS' ) {
							update_option( '__wordpresstoinstagram_license_status', 'LICENSE_OK:' . date( 'Y-m-d H:i:s' ) );
							
							wp_safe_redirect( admin_url() . 'admin.php?page=' . WORDPRESSTOINSTAGRAM_SLUG );
							exit();
						} else {
							echo '<div class="error"><p>ERROR. Response: ' . $objRequestStatus->code . ' Message: ' . $objRequestStatus->message . '</p></div>';
						}
					} else {
						echo '<div class="error"><p>Invalid license!</p></div>';
					}
				}
			}
		}
	}

	/**
	 * PHP Curl
	 */
	private function cCurl( $url ) {
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => false,
			CURLOPT_FOLLOWLOCATION => true, 
			CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0',
			CURLOPT_AUTOREFERER    => true,
			CURLOPT_TIMEOUT        => 120, 
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_SSL_VERIFYPEER => false 
	  );

		$ch = curl_init( $url ); 
	  curl_setopt_array( $ch, $options ); 
	  $content = curl_exec( $ch );
	  curl_close( $ch );
	  return $content;
	}

	/**
	 * Handle account
	 */
	public function wordpresstoinstagram_handle_account() {
		global $wpdb;
		$tableInstagramAccounts = $wpdb->prefix . 'wpinstagram_accounts';
		
		if ( isset( $_POST['submit_wordpresstoinstagram_account'] ) ) {
			if ( isset( $_POST['wordpresstoinstagram_account_form_nonce'] ) && wp_verify_nonce( $_POST['wordpresstoinstagram_account_form_nonce'], 'wordpresstoinstagram_account_form' ) ) {
				$idAccount = sanitize_text_field( $_POST['wordpresstoinstagram_account_hidden_id'] );
				$username = sanitize_text_field( $_POST['instagram_username'] );
				$password = sanitize_text_field( $_POST['instagram_password'] );

				if ( empty( $idAccount ) ) {
					$wpdb->query( $wpdb->prepare( "INSERT INTO $tableInstagramAccounts SET username = %s, password = %s, created_at = NOW()", $username, $password ) );
					$idAccount = $wpdb->insert_id;
				} else {
					$wpdb->query( $wpdb->prepare( "UPDATE $tableInstagramAccounts SET username = %s, password = %s, updated_at = NOW() WHERE id = %d", $username, $password, $idAccount ) );
				}

				wp_safe_redirect( admin_url() . 'admin.php?page=' . WORDPRESSTOINSTAGRAM_SLUG . '&tab=accounts&node=account&account=' . $idAccount );
				exit();
			}
		}

		/**
		 * Remove account
		 */
		if ( isset( $_GET['page'] ) && $_GET['page'] == WORDPRESSTOINSTAGRAM_SLUG ) {
			if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'accounts' ) {
				if ( isset( $_GET['node'] ) && $_GET['node'] == 'trash' ) {
					if ( isset( $_GET['wordpresstoinstagram_trash_account_nonce'] ) ) {
						if ( wp_verify_nonce( $_GET['wordpresstoinstagram_trash_account_nonce'], 'wordpresstoinstagram_trash_account' ) ) {
							global $wpdb;
							$tableInstagramAccounts = $wpdb->prefix . 'wpinstagram_accounts';
							$idAccount = sanitize_text_field( $_GET['account'] );

							$wpdb->query( $wpdb->prepare( "UPDATE $tableInstagramAccounts SET deleted_at = NOW() WHERE id = %d", $idAccount ) );
							die( 'ACCOUNT_TRASHED' );
						} else {
							die( 'INVALID_REQUEST' );
						}
					}
				}
			}
		}
	}

	/**
	 * Include Js file to admin page
	 */
	public function wordpresstoinstagram_admin_enqueue_scripts() {
		wp_enqueue_style( WORDPRESSTOINSTAGRAM_SLUG . '-app', plugins_url( 'css/app.css', __FILE__ ), false, 'screen' );
		wp_enqueue_style( WORDPRESSTOINSTAGRAM_SLUG . '-messenger', plugins_url( 'scripts/messenger/css/messenger.css', __FILE__ ), false, 'screen' );
		wp_enqueue_style( WORDPRESSTOINSTAGRAM_SLUG . '-messenger-spinner', plugins_url( 'scripts/messenger/css/messenger-spinner.css', __FILE__ ), false, 'screen' );
		wp_enqueue_style( WORDPRESSTOINSTAGRAM_SLUG . '-messenger-theme', plugins_url( 'scripts/messenger/css/messenger-theme-flat.css', __FILE__ ), false, 'screen' );

		wp_enqueue_script( 'jquery' );

		wp_enqueue_script( WORDPRESSTOINSTAGRAM_SLUG . '-messenger', plugins_url( 'scripts/messenger/js/messenger.min.js', __FILE__ ), 'jquery' );
		wp_enqueue_script( WORDPRESSTOINSTAGRAM_SLUG . '-messenger-theme', plugins_url( 'scripts/messenger/js/messenger-theme-flat.js', __FILE__ ), 'jquery' );
		wp_enqueue_script( WORDPRESSTOINSTAGRAM_SLUG . '-app', plugins_url( 'scripts/app.admin.js', __FILE__ ), array(), get_bloginfo( 'version' ), true );
	}
}new WordPressToInstagram();