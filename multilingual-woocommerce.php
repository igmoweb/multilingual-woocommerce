<?php
/*
Plugin Name: Multilingual Woocommerce Integration
Plugin URI: 
Description: Does nothing at all
Author: igmoweb
Version:0.1
Author URI:
Text Domain: multilingual-woo
Network:false
*/

/**
 * The main class of the plugin
 */

// TODO: Change the class name
class Multilingual_Woocommerce {

	// The version slug for the DB
	public static $version_option_slug = 'multilingual_woocommerce_version';

	// Admin pages. THey could be accesed from other points
	// So they're statics
	static $network_main_menu_page;

	private $product_updater;

	public function __construct() {
		$this->set_globals();

		$this->includes();

		add_action( 'init', array( &$this, 'maybe_upgrade' ) );

		add_action( 'init', array( &$this, 'init_plugin' ) );

		add_action( 'plugins_loaded', array( &$this, 'load_text_domain' ) );

		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );

		// We don't use the activation hook here
		// As sometimes is not very helpful and
		// we would need to check stuff to install not only when
		// we activate the plugin
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

	}

	public function enqueue_scripts() {
	}


	public function enqueue_styles() {
		wp_enqueue_style( 'multilingual-woo-icons', MULTILINGUAL_WOO_ASSETS_URL . 'css/icons.css' );
	}



	/**
	 * Set the plugin constants
	 */
	private function set_globals() {

		//TODO: Change the constant names

		// Basics
		define( 'MULTILINGUAL_WOO_VERSION', '0.1' );
		define( 'MULTILINGUAL_WOO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'MULTILINGUAL_WOO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'MULTILINGUAL_WOO_PLUGIN_FILE_DIR', plugin_dir_path( __FILE__ ) . 'multilingual-woocommerce.php' );

		// Language domain
		define( 'MULTILINGUAL_WOO_LANG_DOMAIN', 'multilingual-woo' );

		// URLs
		define( 'MULTILINGUAL_WOO_ASSETS_URL', MULTILINGUAL_WOO_PLUGIN_URL . 'assets/' );

		// Dirs
		define( 'MULTILINGUAL_WOO_ADMIN_DIR', MULTILINGUAL_WOO_PLUGIN_DIR . 'admin/' );
		define( 'MULTILINGUAL_WOO_FRONT_DIR', MULTILINGUAL_WOO_PLUGIN_DIR . 'front/' );
		define( 'MULTILINGUAL_WOO_MODEL_DIR', MULTILINGUAL_WOO_PLUGIN_DIR . 'model/' );
		define( 'MULTILINGUAL_WOO_INCLUDES_DIR', MULTILINGUAL_WOO_PLUGIN_DIR . 'inc/' );

	}

	/**
	 * Include files needed
	 */
	private function includes() {
		// Model
		require_once( MULTILINGUAL_WOO_MODEL_DIR . 'model.php' );

		// Libraries
		require_once( MULTILINGUAL_WOO_INCLUDES_DIR . 'admin-page.php' );
		require_once( MULTILINGUAL_WOO_INCLUDES_DIR . 'errors-handler.php' );
		require_once( MULTILINGUAL_WOO_INCLUDES_DIR . 'product-updater.php' );

		// Settings Handler
		require_once( MULTILINGUAL_WOO_INCLUDES_DIR . 'settings-handler.php' );

		// Helpers
		require_once( MULTILINGUAL_WOO_INCLUDES_DIR . 'helpers.php' );

		// Admin Pages
		require_once( MULTILINGUAL_WOO_ADMIN_DIR . 'pages/network-main-page.php' );
	}

	/**
	 * Upgrade the plugin when a new version is uploaded
	 */
	public function maybe_upgrade() {
		$current_version = get_option( self::$version_option_slug );

		if ( ! $current_version )
			$current_version = '0.1'; // This is the first version that includes some upgradings

		// For the second version, we're just saving the version in DB
		if ( version_compare( $current_version, '0.2', '<=' ) ) {
			require_once( MULTILINGUAL_WOO_INCLUDES_DIR . 'upgrade.php' );
			// Call upgrade functions here
		}

		// This is the third version (still not released)
		if ( version_compare( $current_version, '0.3', '<' ) ) {
			require_once( MULTILINGUAL_WOO_INCLUDES_DIR . 'upgrade.php' );
			// Call upgrade functions here	
		}

		update_option( self::$version_option_slug, MULTILINGUAL_WOO_VERSION );
	}

	public function activate() {
		$model = mlw_get_model();
		$model->create_schema();
	}

	/**
	 * Actions executed when the plugin is deactivated
	 */
	public function deactivate() {
		// HEY! Do not delete anything from DB here
		// You better use the uninstall functionality
	}

	/**
	 * Load the plugin text domain and MO files
	 * 
	 * These can be uploaded to the main WP Languages folder
	 * or the plugin one
	 */
	public function load_text_domain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), MULTILINGUAL_WOO_LANG_DOMAIN );

		load_textdomain( MULTILINGUAL_WOO_LANG_DOMAIN, WP_LANG_DIR . '/' . MULTILINGUAL_WOO_LANG_DOMAIN . '/' . MULTILINGUAL_WOO_LANG_DOMAIN . '-' . $locale . '.mo' );
		load_plugin_textdomain( MULTILINGUAL_WOO_LANG_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Initialize the plugin
	 */
	public function init_plugin() {

		$this->product_updater = new Multilingual_Woocommerce_Product_Updater();
		
		// MENUS: You do not need to check if you're on admin side
		// Main menu
		// A network menu
		$args = array(
			'menu_title' => __( 'Multilingual Woocommerce', MULTILINGUAL_WOO_LANG_DOMAIN ),
			'page_title' => __( 'Multilingual Woocommerce Integration', MULTILINGUAL_WOO_LANG_DOMAIN ),
			'network_menu' => true,
			'screen_icon_slug' => 'origin',
			'parent' => 'settings.php'
		);
		self::$network_main_menu_page = new Multilingual_Woocommerce_Network_Main_Menu( 'multilingual_woocommerce_settings', 'manage_network', $args );
	}

}

// TODO: Change the name of the class
global $multilingual_woocommerce;
$multilingual_woocommerce = new Multilingual_Woocommerce();