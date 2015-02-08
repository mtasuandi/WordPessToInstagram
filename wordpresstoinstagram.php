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
define( 'WORDPRESSTOINSTAGRAM_LICENSE_URL', 'http://kreaxy.com' );
define( 'WORDPRESSTOINSTAGRAM_PLUGIN_UPDATER_URL', 'http://kreaxy.com' );
define( 'WORDPRESSTOINSTAGRAM_LICENSE_STATUS', get_option( '__wordpresstoinstagram_license_status' ) );

class WordPressToInstagram {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'wordpresstoinstagram_admin_menu' );
	}
	
	public function wordpresstoinstagram_admin_menu() {
		$licenseStatus = WORDPRESSTOINSTAGRAM_LICENSE_STATUS;
		
		if ( empty( $licenseStatus ) ) {
			add_menu_page( __( 'WordPress To Instagram Activation', WORDPRESSTOINSTAGRAM_SLUG . '-activation' ),
				__( 'WordPress To Instagram', WORDPRESSTOINSTAGRAM_SLUG . '-activation' ),
				'manage_options',
				WORDPRESSTOINSTAGRAM_SLUG . '-activation',
				array( 'WordPress To Instagram', 'wordpresstoinstagram_display_page_license' ), 'dashicons-lock'
			);
		} else {
			add_menu_page( __( 'WordPress To Instagram', WORDPRESSTOINSTAGRAM_SLUG ),
				__( 'WordPress To Instagram', WORDPRESSTOINSTAGRAM_SLUG ),
				'manage_options',
				WORDPRESSTOINSTAGRAM_SLUG,
				array( 'WordPress To Instagram', 'wordpresstoinstagram_display_page' ), 'dashicons-lock'
			);
		}
	}

	public function wordpresstoinstagram_display_license_page() {

	}

	public function wordpresstoinstagram_display_page() {

	}
}new WordPressToInstagram();
