<?php

require_once( 'C:\wamp\www\chorilandia\wp-content\plugins\multilingual-woocommerce/multilingual-woocommerce.php' );
require_once( 'C:\wamp\www\phpunit-wp\wp-admin\includes\plugin.php' );

class MCC_Copy_Page extends WP_UnitTestCase {  


	function setUp() {  
        parent::setUp(); 

        global $multilingual_woocommerce;
        $this->plugin = $multilingual_woocommerce;
        $this->plugin->activate();

    } // end setup 


    function tearDown() {
    	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
			define( 'WP_UNINSTALL_PLUGIN', true );

        delete_site_option( Multilingual_Woocommerce::$version_option_slug );

        $model = mlw_get_model();
        delete_site_option( $model->schema_created_option_slug );
        $model->delete_tables();

    }

    function test_init_plugin() {
    	$this->assertNotEquals( $this->plugin, null );
    }

    function test_add_currency() {
    	$left = 'AUS';
    	$right = 'EUR';
    	$rate = 1.7;

    	mlw_insert_new_currency_rate( $left, $right, $rate );
    
    	$normal_rate = mlw_get_currency_rate( $left, $right );
    	$this->assertEquals( $rate, $normal_rate );

        $inverse_rate = mlw_get_currency_rate( $right, $left );
        $this->assertEquals( 1 / $rate, $inverse_rate );
    }

    function test_update_currency() {
        $left = 'AUS';
        $right = 'EUR';
        $rate = 1.7;

        mlw_insert_new_currency_rate( $left, $right, $rate );

        $new_rate = 2.3;
        mlw_update_currency_rate( $left, $right, $new_rate );

        $normal_rate = mlw_get_currency_rate( $left, $right );
        $this->assertEquals( $new_rate, $normal_rate );

        $inverse_rate = mlw_get_currency_rate( $right, $left );
        $this->assertEquals( 1 / $new_rate, $inverse_rate );
    }

    function test_delete_currency() {
        $left = 'AUS';
        $right = 'EUR';
        $rate = 1.7;

        mlw_insert_new_currency_rate( $left, $right, $rate );

        mlw_delete_currency_rate( $left, $right );

        $normal_rate = mlw_get_currency_rate( $left, $right );
        $this->assertEquals( 1, $normal_rate );

        $inverse_rate = mlw_get_currency_rate( $right, $left );
        $this->assertEquals( 1, $inverse_rate );
    }

    function test_convert_currency() {
        $left = 'AUS';
        $right = 'EUR';
        $rate = 1.7;
        mlw_insert_new_currency_rate( $left, $right, $rate );

        $settings = mlw_get_settings();
        $settings['decimals'] = 2;
        mlw_update_settings( $settings );

        $result = mlw_convert_currency( 'AUS', 2.45, 'EUR' );
        $this->assertTrue( number_format( 1.7*2.45, 2 ) == $result );

        $result = mlw_convert_currency( 'EUR', 2.45, 'AUS' );
        $supposed = number_format( 2.45/1.7, 2 );
        $this->assertTrue( $supposed == $result );
    }

}
