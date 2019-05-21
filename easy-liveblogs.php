<?php
/*
Plugin Name: Easy Liveblogs
Plugin URI: https://doubletakepigeon.com/easy-liveblogs
Description: Live blogging made easy with the Easy Liveblogs plugin from Double Take Pigeon.
Version: 1.1
Author: Double Take Pigeon
Author URI: https://www.doubletakepigeon.com
Text Domain: easy-liveblogs
Domain Path: /languages
License: GPL-2.0+

------------------------------------------------------------------------
Copyright 2019 Double Take Pigeon, The Netherlands.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

For the GNU General Public License, see http://www.gnu.org/licenses.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Easy_Liveblogs' ) ) {

class Easy_Liveblogs {
	private static $instance = null;
	private $plugin_path;
	private $plugin_url;
	private $plugin_name = 'Easy Liveblogs';
	private $plugin_version = '1.1';
	private $text_domain = 'easy-liveblogs';
	public $liveblog;

	/**
	 * Creates or returns an instance of this class.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Easy_Liveblogs ) ) {
			self::$instance = new Easy_Liveblogs;

			self::$instance->plugin_path = plugin_dir_path( __FILE__ );
			self::$instance->plugin_url  = plugin_dir_url( __FILE__ );

			self::$instance->define_constants();
			self::$instance->includes();

			self::$instance->liveblog = new ELB_Liveblog();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
			add_action( 'wp_enqueue_scripts', array( self::$instance, 'register_styles' ) );
			add_action( 'admin_enqueue_scripts', array( self::$instance, 'register_styles' ) );
			add_action( 'wp_enqueue_scripts', array( self::$instance, 'register_scripts' ) );
			add_Action( 'admin_enqueue_scripts', array( self::$instance, 'register_scripts' ) );
		}

		return self::$instance;
	}

	/**
	 * Includes
	 */
	public function includes() {
		global $elb_options;

		require_once( $this->get_plugin_path() . 'includes/admin/elb-register-settings.php' );

		$elb_options = elb_get_settings();

		require_once( $this->get_plugin_path() . 'includes/class-elb-liveblog.php' );
		require_once( $this->get_plugin_path() . 'includes/elb-post-types.php' );
		require_once( $this->get_plugin_path() . 'includes/elb-metabox.php' );
		require_once( $this->get_plugin_path() . 'includes/elb-functions.php' );
		require_once( $this->get_plugin_path() . 'includes/elb-filters.php' );
		require_once( $this->get_plugin_path() . 'includes/admin/elb-pages.php' );
	}

	/**
	 * Get plugin URL
	 */
	public function get_plugin_url() {
		return $this->plugin_url;
	}

	/**
	 * Get plugin path
	 */
	public function get_plugin_path() {
		return $this->plugin_path;
	}

	/**
	 * Enqueue and register JavaScript files here.
	 */
	public function register_scripts() {
		if ( is_admin() ) {
			wp_enqueue_script( 'selectize', $this->get_plugin_url() . 'assets/selectize/selectize.min.js', array( 'jquery' ), '0.12.4' );
			wp_enqueue_script( 'elb-admin', $this->get_plugin_url() . 'assets/js/easy-liveblogs-admin.js', array( 'jquery', 'selectize' ), $this->plugin_version );
		}

		if ( !is_admin() && is_singular( elb_get_supported_post_types() ) ) {
			wp_enqueue_script( 'elb', $this->get_plugin_url() . 'assets/js/easy-liveblogs.js', array( 'jquery' ), $this->plugin_version );
			wp_localize_script( 'elb', 'elb', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'interval' => elb_get_update_interval(),
				'status' => elb_get_liveblog_status(),
				'liveblog' => get_the_ID(),
				'new_post_msg' => __( 'There is %s update.', ELB_TEXT_DOMAIN ),
				'new_posts_msg' => __( 'There are %s updates.', ELB_TEXT_DOMAIN ),
				'now_more_posts' => __( "That's it.", ELB_TEXT_DOMAIN )
			) );
		}
	}


	/**
	 * Enqueue and register CSS files here.
	 */
	public function register_styles() {

		if ( is_admin() ) {

			wp_enqueue_style( 'selectize', $this->get_plugin_url() . 'assets/selectize/selectize.default.css', null, '0.12.4' );

		} else {

			if ( ! elb_is_liveblog() && !is_singular( elb_get_supported_post_types() ) ) {
				return;
			}

			$theme = elb_get_theme();

			if ( $theme !== 'none' ) {
				wp_enqueue_style( 'elb', $this->get_plugin_url() . 'assets/css/easy-liveblogs.css', null, $this->plugin_version );
			}

		}
	}

	/**
	 * Load textdomain
	 */
	public function load_textdomain() {
		$mofile = sprintf( '%1$s-%2$s.mo', ELB_TEXT_DOMAIN, get_locale() );

		// Check wp-content/languages/plugins/easy-liveblogs
		$mofile_global = WP_LANG_DIR . '/plugins/easy-liveblogs/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			load_textdomain( ELB_TEXT_DOMAIN, $mofile_global );
		} else {
			load_plugin_textdomain( ELB_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

	}

	/**
	 * Define Constants
	 */
	private function define_constants() {
		if ( ! defined( 'ELB_NAME' ) ) define( 'ELB_NAME', $this->plugin_name );
		if ( ! defined( 'ELB_PATH' ) ) define( 'ELB_PATH', $this->get_plugin_path() );
		if ( ! defined( 'ELB_URL' ) ) define( 'ELB_URL', $this->get_plugin_url() );
		if ( ! defined( 'ELB_VERSION' ) ) define( 'ELB_VERSION', $this->plugin_version );
		if ( ! defined( 'ELB_TEXT_DOMAIN' ) ) define( 'ELB_TEXT_DOMAIN', $this->text_domain );
	}

	/**
	* Settings
	*/
	public function settings() {
		return elb_get_settings();
	}
}
}

function ELB() {
	return Easy_Liveblogs::instance();
}

ELB();
