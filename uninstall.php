<?php

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

exit();
$plugin_dir = plugin_dir_path( __FILE__ );

require_once( $plugin_dir . 'multilingual-woocommerce.php' );
require_once( $plugin_dir . 'model/model.php' );
require_once( $plugin_dir . 'inc/settings-handler.php' );

delete_site_option( Multilingual_Woocommerce::$version_option_slug );

$model = mlw_get_model();
delete_site_option( $model->schema_created_option_slug );
$model->delete_tables();

delete_site_option( Multilingual_Woocommerce_Settings_Handler::$settings_slug );
