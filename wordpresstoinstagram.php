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
define( 'WORDPRESSTOINSTAGRAM_PLUGIN_UPDATER_URL', 'http://kreaxy.com/updater/plugins/wordpresstoinstagram/metadata.json' );

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
		add_action( 'admin_init', array( $this, 'wordpresstoinstagram_handle_account' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wordpresstoinstagram_admin_enqueue_scripts' ) );
		add_filter( 'manage_posts_columns', array( $this, 'wordpresstoinstagram_posts_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'wordpresstoinstagram_posts_status' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'wordpresstoinstagram_handle_posttoinstagram' ) );
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
		$tableInstagramTracks = $wpdb->prefix . 'wpinstagram_tracks';
		$tableInstagramLogs = $wpdb->prefix . 'wpinstagram_logs';

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

		$sqlInstagramsTracks = <<<SQL
CREATE TABLE {$tableInstagramTracks} (
id INT(11) unsigned NOT NULL AUTO_INCREMENT,
id_post INT(11) NOT NULL,
total_posted INT(11) NOT NULL DEFAULT 0,
posted_at TIMESTAMP NOT NULL,
PRIMARY KEY id (id)
) DEFAULT CHARACTER SET utf8, DEFAULT COLLATE utf8_general_ci;
SQL;
		dbDelta( $sqlInstagramsTracks );

		$sqlInstagramsLogs = <<<SQL
CREATE TABLE {$tableInstagramLogs} (
id INT(11) unsigned NOT NULL AUTO_INCREMENT,
id_account INT(11) NOT NULL,
id_post INT(11) NOT NULL,
response_message TEXT NOT NULL,
logged_at TIMESTAMP NOT NULL,
PRIMARY KEY id (id)
) DEFAULT CHARACTER SET utf8, DEFAULT COLLATE utf8_general_ci;
SQL;
		dbDelta( $sqlInstagramsLogs );
	}
	
	/**
	 * Menu
	 */
	public function wordpresstoinstagram_admin_menu() {
		add_menu_page( __( 'WordPress To Instagram', WORDPRESSTOINSTAGRAM_SLUG ),
			__( 'WP To Instagram', WORDPRESSTOINSTAGRAM_SLUG ),
			'manage_options',
			WORDPRESSTOINSTAGRAM_SLUG,
			array( $this, 'wordpresstoinstagram_display_page' ), 'dashicons-camera'
		);
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
		$tab = '';
		if ( isset( $_GET['tab'] ) ) {
			$tab = sanitize_text_field( $_GET['tab'] );
		}

		$node = '';
		if ( isset( $_GET['node'] ) ) {
			$node = sanitize_text_field( $_GET['node'] );
		}

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
				require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'codes/tables/logs.tables.class.php' );
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
	 * PHP Curl
	 */
	private function cCurl( $url ) {
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => false,
			CURLOPT_FOLLOWLOCATION => true, 
			CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0',
			CURLOPT_AUTOREFERER => true,
			CURLOPT_TIMEOUT => 120, 
			CURLOPT_MAXREDIRS => 10,
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
				/**
				 * Trash
				 */
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

				/**
				 * Activate
				 */
				if ( isset( $_GET['node'] ) && $_GET['node'] == 'activate' ) {
					if ( isset( $_GET['wordpresstoinstagram_activate_account_nonce'] ) ) {
						if ( wp_verify_nonce( $_GET['wordpresstoinstagram_activate_account_nonce'], 'wordpresstoinstagram_activate_account' ) ) {
							global $wpdb;
							$tableInstagramAccounts = $wpdb->prefix . 'wpinstagram_accounts';
							$idAccount = sanitize_text_field( $_GET['account'] );

							$wpdb->query( $wpdb->prepare( "UPDATE $tableInstagramAccounts SET is_active = %d WHERE id = %d", 1, $idAccount ) );
							die( 'ACCOUNT_ACTIVATED' );
						} else {
							die( 'INVALID_REQUEST' );
						}
					}
				}

				/**
				 * Deactivate
				 */
				if ( isset( $_GET['node'] ) && $_GET['node'] == 'deactivate' ) {
					if ( isset( $_GET['wordpresstoinstagram_deactivate_account_nonce'] ) ) {
						if ( wp_verify_nonce( $_GET['wordpresstoinstagram_deactivate_account_nonce'], 'wordpresstoinstagram_deactivate_account' ) ) {
							global $wpdb;
							$tableInstagramAccounts = $wpdb->prefix . 'wpinstagram_accounts';
							$idAccount = sanitize_text_field( $_GET['account'] );

							$wpdb->query( $wpdb->prepare( "UPDATE $tableInstagramAccounts SET is_active = %d WHERE id = %d", 0, $idAccount ) );
							die( 'ACCOUNT_DEACTIVATED' );
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

	/**
	 * Custom column in WP Posts Table
	 */
	public function wordpresstoinstagram_posts_column( $columns ) {
		return array_merge( $columns, array( 'wordpresstoinstagram' => __( 'WP To Instagram' ) ) );
	}

	/**
	 * Custom column status
	 */
	public function wordpresstoinstagram_posts_status( $column, $idPost ) {
		global $wpdb;
		$tableInstagramTracks = $wpdb->prefix . 'wpinstagram_tracks';
		$totalPosted = 0;
		$track = $wpdb->get_row( $wpdb->prepare( "SELECT total_posted FROM $tableInstagramTracks WHERE id_post = %d", $idPost ) );
		if ( !empty( $track ) ) {
			$totalPosted = $track->total_posted;
		}
		$format = 'time';
		if ( $totalPosted > 1 ) {
			$format = 'times';
		}
		$postTitle = get_the_title( $idPost );
		$postNonceUrl = wp_nonce_url( admin_url() . 'admin.php?page=' . WORDPRESSTOINSTAGRAM_SLUG . '&tab=posttoinstagram&post=' . $idPost, 'wordpresstoinstagram_posttoinstagram', 'wordpresstoinstagram_posttoinstagram_nonce' );
		if ( $column == 'wordpresstoinstagram' ) {
			echo <<<HTML
<a href="{$postNonceUrl}" class="button-primary wordpresstoinstagram_post_to_instagram" title="Publish post {$postTitle} to Instagram" data-title="{$postTitle}"><span class="dashicons dashicons-camera"></span>Publish to Instagram</a>&nbsp;
<a class="button action wordpresstoinstagram_counts" title="Published {$totalPosted} {$format}" style="margin-top:5px;" disabled="disabled">{$totalPosted}</a>
HTML;
		}
	}

	public function wordpresstoinstagram_handle_posttoinstagram() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == WORDPRESSTOINSTAGRAM_SLUG ) {
			if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'posttoinstagram' ) {
				if ( isset( $_GET['post'] ) && !empty( $_GET['post'] ) ) {
					if ( isset( $_GET['wordpresstoinstagram_posttoinstagram_nonce'] ) && wp_verify_nonce( $_GET['wordpresstoinstagram_posttoinstagram_nonce'], 'wordpresstoinstagram_posttoinstagram' ) ) {
						require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'libraries/Instagram.php' );
						$instagram = new Instagram();
						$idPost = sanitize_text_field( $_GET['post'] );
						$idFeaturedImage = get_post_thumbnail_id( $idPost );
						if ( !empty( $idFeaturedImage ) ) {
							$imageUrl = wp_get_attachment_url( $idFeaturedImage );
							$postTitle = get_the_title( $idPost );
							$postPermalink = get_permalink( $idPost );
							$thePostTags = get_the_tags( $idPost );
							$postTags = '';
							if ( $thePostTags ) {
								foreach ( $thePostTags as $tPostTag ) {
									$postTags .= '#' . $tPostTag->name . ' ';
								}
							}
							
							global $wpdb;
							$tableInstagramAccounts = $wpdb->prefix . 'wpinstagram_accounts';
							$tableInstagramTracks = $wpdb->prefix . 'wpinstagram_tracks';
							$tableInstagramLogs = $wpdb->prefix . 'wpinstagram_logs';

							$igContent = $postTitle . ' - ' . $postPermalink . ' - ' . $postTags;
							$instagrams = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tableInstagramAccounts WHERE is_active = %d AND deleted_at IS NULL", 1 ) );
							if ( !empty( $instagrams ) ) {
								foreach ( $instagrams as $ig ) {
									$igResponse = $instagram->post( $ig->username, $ig->password, $imageUrl, $igContent );
									if ( $igResponse == 'SUCCESS' ) {
										$track = $wpdb->get_row( $wpdb->prepare( "SELECT id_post FROM $tableInstagramTracks WHERE id_post = %d", $idPost ) );
										if ( empty( $track ) ) {
											$wpdb->query( $wpdb->prepare( "INSERT INTO $tableInstagramTracks SET id_post = %d, total_posted = %d, posted_at = NOW()", $idPost, 1 ) );
										} else {
											$wpdb->query( $wpdb->prepare( "UPDATE $tableInstagramTracks SET total_posted = total_posted + 1, posted_at = NOW() WHERE id_post = %d", $idPost ) );
										}
									}
									if ( is_array( $igResponse ) ) {
										$igResponse = serialize( $igResponse );
									}

									$wpdb->query( $wpdb->prepare( "INSERT INTO $tableInstagramLogs SET id_account = %d, id_post = %d, response_message = %s, logged_at = NOW()", $ig->id, $idPost, $igResponse ) );
								}
							}
							die( 'POSTED_TO_INSTAGRAM' );
						} else {
							die( 'POST_DOES_NOT_CONTAIN_FEATURED_IMAGE' );
						}
					} else {
						die( 'INVALID_REQUEST' );
					}
				}
			}
		}
	}
}new WordPressToInstagram();