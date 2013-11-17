<?php

require_once( 'C:\wamp\www\chorilandia\wp-content\plugins\multilingual-woocommerce/multilingual-woocommerce.php' );
require_once( 'C:\wamp\www\phpunit-wp\wp-admin\includes\plugin.php' );

class MCC_Copy_Page extends WP_UnitTestCase {  

	private $spanish_blog_id = 1;
	private $english_blog_id = 2;

    private $spanish_product_price = 150;
    private $english_product_price = 120;

    private $spanish_currency = 120;
    private $english_currency = 120;
    private $curr_rate = 1.19;

	function setUp() {  
        parent::setUp(); 

        global $multilingual_woocommerce;
        $this->plugin = $multilingual_woocommerce;
        $this->plugin->activate();

        switch_to_blog($this->spanish_blog_id);
        $this->activate_woocommerce();
        $this->activate_multilingual();

        //Product
        $this->product_spanish_id = $this->factory->post->create_object(
            array(
                'post_type' => 'product',
                'post_content' => 'A product in spanish'
            )
        );

        //Multilingual Press
        update_option('inpsyde_multilingual_flag_url', '');
        update_option('inpsyde_multilingual_blog_relationship', 'a:2:{i:0;i:1;i:1;i:2;}');
        update_option('inpsyde_multilingual_default_actions', 'a:1:{s:22:"always_translate_posts";b:1;}');
        update_option('inpsyde_multilingual_redirect', '1');
        restore_current_blog();

        switch_to_blog($this->english_blog_id);
        $this->activate_woocommerce();

        //Product
        $this->product_english_id = $this->factory->post->create_object(
            array(
                'post_type' => 'product',
                'post_content' => 'A product in english'
            )
        );


        //Multilingual Press
        update_option('inpsyde_multilingual_flag_url', '');
        update_option('inpsyde_multilingual_blog_relationship', 'a:2:{i:0;i:1:1;i:1;i:2;}');
        update_option('inpsyde_multilingual_default_actions', 'a:1:{s:22:"always_translate_posts";b:1;}');
        update_option('inpsyde_multilingual_redirect', '');
        restore_current_blog();

        $this->multilingual_plugin->set_linked_element( $this->product_spanish_id, $this->product_spanish_id, $this->spanish_blog_id, '', $this->spanish_blog_id );
        $this->multilingual_plugin->set_linked_element( $this->product_english_id, $this->product_spanish_id, $this->spanish_blog_id, '', $this->english_blog_id );

    } // end setup 

    function activate_woocommerce() {
    	global $woocommerce;
    	activate_plugin( WP_CONTENT_DIR . '/plugins/woocommerce/woocommerce.php', '', true, true );
        $woocommerce->activate();
        $woocommerce->init();

        switch_to_blog( $this->spanish_blog_id );
        $this->spanish_currency == 'EUR';
        $this->spanish_weight_unit = 'kg';
        $this->spanish_dimension_unit = 'cm';
        update_option('woocommerce_currency', $this->spanish_currency );
        update_option('woocommerce_weight_unit', $this->spanish_weight_unit );
        update_option('woocommerce_dimension_unit', $this->spanish_dimension_unit );
        restore_current_blog();

        switch_to_blog( $this->english_blog_id );
        $this->english_currency == 'GBP';
        $this->english_weight_unit = 'lbs';
        $this->english_dimension_unit = 'in';
        update_option('woocommerce_currency', $this->english_currency );
        update_option('woocommerce_weight_unit', $this->english_weight_unit );
        update_option('woocommerce_dimension_unit', $this->english_dimension_unit );
        restore_current_blog();
    }

    function deactivate_plugins() {
        deactivate_plugins( 
            array(
                WP_CONTENT_DIR . '/plugins/woocommerce/woocommerce.php',
                WP_CONTENT_DIR . '/plugins/multilingual-press-pro/multilingual-press.php'
            ), 
            true 
        );
    }

    function activate_multilingual() {
        activate_plugin( WP_CONTENT_DIR . '/plugins/multilingual-press-pro/multilingual-press.php', '', true, true );
        Multilingual_Press::install_plugin();
        $this->multilingual_plugin = Multilingual_Press::get_object();
    }

    function tearDown() {
        parent::tearDown();

    	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
			define( 'WP_UNINSTALL_PLUGIN', true );

        delete_site_option( Multilingual_Woocommerce::$version_option_slug );

        $model = mlw_get_model();
        delete_site_option( $model->schema_created_option_slug );
        $model->delete_tables();

    	switch_to_blog( $this->spanish_blog_id );
        include( WP_CONTENT_DIR . '/plugins/woocommerce/uninstall.php' );
        $this->deactivate_plugins();
    	restore_current_blog();

    	switch_to_blog( $this->english_blog_id );
    	include( WP_CONTENT_DIR . '/plugins/woocommerce/uninstall.php' );
        $this->deactivate_plugins();
    	restore_current_blog();

        global $wpdb;

        $table = $wpdb->base_prefix . 'multilingual_linked';
        $wpdb->query( "DROP TABLE IF EXISTS $table");
    }

    function test_check_linked_elements() {
        $results = mlp_get_linked_elements( $this->product_spanish_id, '', $this->spanish_blog_id );
        $this->assertTrue( count( $results ) == 1 );

        $first_key = key( $results );
        $this->assertTrue( $results[ $first_key ] == $this->product_english_id );

        $results = mlp_get_linked_elements( $this->product_english_id, '', $this->english_blog_id );
        $this->assertTrue( count( $results ) == 1 );

        $first_key = key( $results );
        $this->assertTrue( $results[ $first_key ] == $this->product_spanish_id );
    }

    function test_update_prices() {
        $left = $this->english_currency;
        $right = $this->spanish_currency;
        $rate = $this->curr_rate;

        mlw_insert_new_currency_rate( $left, $right, $rate );

        do_action('init');

        // Changing spanish product price
        $new_price = 230;
        switch_to_blog( $this->spanish_blog_id );
        update_post_meta( $this->product_spanish_id, '_regular_price', $new_price );
        restore_current_blog();

        switch_to_blog( $this->english_blog_id );
        $current_price = get_post_meta( $this->product_english_id, '_regular_price', true );
        restore_current_blog();

        $supposed = mlw_convert_currency( $right, $new_price, $left );
        $this->assertTrue( $current_price == $supposed );

        // Changing english product price
        $new_price = 500;
        switch_to_blog( $this->english_blog_id );
        update_post_meta( $this->product_english_id, '_regular_price', $new_price );
        restore_current_blog();

        switch_to_blog( $this->spanish_blog_id );
        $current_price = get_post_meta( $this->product_spanish_id, '_regular_price', true );
        restore_current_blog();

        $supposed = mlw_convert_currency( $left, $new_price, $right );
        $this->assertTrue( $current_price == $supposed );

    }

    function test_update_sale_prices() {
        $left = $this->english_currency;
        $right = $this->spanish_currency;
        $rate = $this->curr_rate;

        mlw_insert_new_currency_rate( $left, $right, $rate );

        do_action('init');

        // Changing spanish product price
        $sale_price = 100;
        switch_to_blog( $this->spanish_blog_id );
        update_post_meta( $this->product_spanish_id, '_sale_price', $sale_price );
        restore_current_blog();

        switch_to_blog( $this->english_blog_id );
        $current_price = get_post_meta( $this->product_english_id, '_sale_price', true );
        restore_current_blog();

        $supposed = mlw_convert_currency( $right, $sale_price, $left );
        $this->assertTrue( $current_price == $supposed );

        // Changing english product price
        $sale_price = 130;
        switch_to_blog( $this->english_blog_id );
        update_post_meta( $this->product_english_id, '_sale_price', $sale_price );
        restore_current_blog();

        switch_to_blog( $this->spanish_blog_id );
        $current_price = get_post_meta( $this->product_spanish_id, '_sale_price', true );
        restore_current_blog();

        $supposed = mlw_convert_currency( $left, $sale_price, $right );
        $this->assertTrue( $current_price == $supposed );

    }

    function test_update_sale_dates() {
        $new_date_from = time();
        $new_date_to = $new_date_from + 1000;

        do_action('init');

        // Changing spanish dates
        switch_to_blog( $this->spanish_blog_id );
        update_post_meta( $this->product_spanish_id, '_sale_price_dates_from', $new_date_from );
        update_post_meta( $this->product_spanish_id, '_sale_price_dates_to', $new_date_to );
        restore_current_blog();

        switch_to_blog( $this->english_blog_id );
        $current_date_from = get_post_meta( $this->product_english_id, '_sale_price_dates_from', true );
        $current_date_to = get_post_meta( $this->product_english_id, '_sale_price_dates_to', true );
        restore_current_blog();

        $this->assertTrue( $current_date_from == $new_date_from );
        $this->assertTrue( $new_date_to == $new_date_to );


        $new_date_from = $new_date_to;
        $new_date_to = $new_date_from + 1000;

        // Changing english dates
        switch_to_blog( $this->english_blog_id );
        update_post_meta( $this->product_english_id, '_sale_price_dates_from', $new_date_from );
        update_post_meta( $this->product_english_id, '_sale_price_dates_to', $new_date_to );
        restore_current_blog();

        switch_to_blog( $this->spanish_blog_id );
        $current_date_from = get_post_meta( $this->product_spanish_id, '_sale_price_dates_from', true );
        $current_date_to = get_post_meta( $this->product_spanish_id, '_sale_price_dates_to', true );
        restore_current_blog();

        $this->assertTrue( $current_date_from == $new_date_from );
        $this->assertTrue( $new_date_to == $new_date_to );
    }

    function update_stock() {
        $new_stock = 15;

        do_action('init');

        // Changing spanish dates
        switch_to_blog( $this->spanish_blog_id );
        update_post_meta( $this->product_spanish_id, '_stock', $new_stock );
        update_post_meta( $this->product_spanish_id, '_stock_status', 'outofstock' );
        update_post_meta( $this->product_spanish_id, '_manage_stock', 'yes' );
        update_post_meta( $this->product_spanish_id, '_backorders', 'no' );
        update_post_meta( $this->product_spanish_id, '_sold_individually', '' );
        restore_current_blog();

        switch_to_blog( $this->english_blog_id );
        $stock = get_post_meta( $this->product_english_id, '_stock', true );
        $stock_status = get_post_meta( $this->product_english_id, '_stock_status', true );
        $manage_stock = get_post_meta( $this->product_english_id, '_manage_stock', true );
        $backorders = get_post_meta( $this->product_english_id, '_backorders', true );
        $sold_individually = get_post_meta( $this->product_english_id, '_sold_individually', true );
        restore_current_blog();

        $this->assertTrue( $stock == $new_stock );
        $this->assertTrue( $stock_status == 'outofstock' );
        $this->assertTrue( $manage_stock == 'yes' );
        $this->assertTrue( $backorders == 'no' );
        $this->assertTrue( $sold_individually == '' );


        $new_stock = 20;

        // Changing english dates
        switch_to_blog( $this->english_blog_id );
        update_post_meta( $this->product_english_id, '_stock', $new_stock );
        update_post_meta( $this->product_english_id, '_stock_status', 'withstock' );
        update_post_meta( $this->product_english_id, '_manage_stock', 'no' );
        update_post_meta( $this->product_english_id, '_backorders', 'yes' );
        update_post_meta( $this->product_english_id, '_sold_individually', 'yes' );
        restore_current_blog();

        switch_to_blog( $this->spanish_blog_id );
        $current_date_from = get_post_meta( $this->product_spanish_id, '_stock', true );
        $stock_status = get_post_meta( $this->product_spanish_id, '_stock_status', true );
        $manage_stock = get_post_meta( $this->product_spanish_id, '_manage_stock', true );
        $backorders = get_post_meta( $this->product_spanish_id, '_backorders', true );
        $sold_individually = get_post_meta( $this->product_spanish_id, '_sold_individually', true );
        restore_current_blog();

        $this->assertTrue( $stock == $new_stock );
        $this->assertTrue( $stock_status == 'withstock' );
        $this->assertTrue( $manage_stock == 'no' );
        $this->assertTrue( $backorders == 'yes' );
        $this->assertTrue( $sold_individually == 'yes' );
    }

    function test_update_downloads() {
        $new_file_paths = array(
            'http:/www.mydomain.es/file.zip'
        );
        $new_download_limit = 3;
        $new_download_expiry = 30;

        do_action('init');

        // Changing spanish dates
        switch_to_blog( $this->spanish_blog_id );
        update_post_meta( $this->product_spanish_id, '_file_paths', $new_file_paths );
        update_post_meta( $this->product_spanish_id, '_download_limit', $new_download_limit );
        update_post_meta( $this->product_spanish_id, '_download_expiry', $new_download_expiry );
        restore_current_blog();

        switch_to_blog( $this->english_blog_id );
        $file_paths = get_post_meta( $this->product_english_id, '_file_paths', true );
        $download_limit = get_post_meta( $this->product_english_id, '_download_limit', true );
        $download_expiry = get_post_meta( $this->product_english_id, '_download_expiry', true );
        restore_current_blog();

        $this->assertTrue( $file_paths == $new_file_paths );
        $this->assertTrue( $download_limit == $new_download_limit );
        $this->assertTrue( $download_expiry == $new_download_expiry );


        $new_file_paths = array(
            'http:/www.mydomain.es/file2.zip'
        );
        $new_download_limit = 5;
        $new_download_expiry = 15;

        // Changing english dates
        switch_to_blog( $this->english_blog_id );
        update_post_meta( $this->product_english_id, '_file_paths', $new_file_paths );
        update_post_meta( $this->product_english_id, '_download_limit', $new_download_limit );
        update_post_meta( $this->product_english_id, '_download_expiry', $new_download_expiry );
        restore_current_blog();

        switch_to_blog( $this->spanish_blog_id );
        $file_paths = get_post_meta( $this->product_spanish_id, '_file_paths', true );
        $download_limit = get_post_meta( $this->product_spanish_id, '_download_limit', true );
        $download_expiry = get_post_meta( $this->product_spanish_id, '_download_expiry', true );
        restore_current_blog();

        $this->assertTrue( $file_paths == $new_file_paths );
        $this->assertTrue( $download_limit == $new_download_limit );
        $this->assertTrue( $download_expiry == $new_download_expiry );
    }


    function test_update_shipping() {
        $new_weight = 15; //Kg
        $new_length = 23; //cm
        $new_width = 8; //cm
        $new_height = 34; //cm

        do_action('init');

        // Changing spanish dates
        switch_to_blog( $this->spanish_blog_id );
        update_post_meta( $this->product_spanish_id, '_weight', $new_weight );
        update_post_meta( $this->product_spanish_id, '_length', $new_length );
        update_post_meta( $this->product_spanish_id, '_width', $new_width );
        update_post_meta( $this->product_spanish_id, '_height', $new_height );
        restore_current_blog();

        switch_to_blog( $this->english_blog_id );
        $weight = get_post_meta( $this->product_english_id, '_weight', true );
        $length = get_post_meta( $this->product_english_id, '_length', true );
        $width = get_post_meta( $this->product_english_id, '_width', true );
        $height = get_post_meta( $this->product_english_id, '_height', true );
        restore_current_blog();

        $this->assertTrue( $weight == 33.069 );
        $this->assertTrue( $length == 9.0551 );
        $this->assertTrue( $width == 3.1496 );
        $this->assertTrue( $height == 13.3858 );


        $new_weight = 18; //lbs
        $new_length = 23; // in
        $new_width = 8; //in
        $new_height = 34; //in

        // Changing english dates
        switch_to_blog( $this->english_blog_id );
        update_post_meta( $this->product_english_id, '_weight', $new_weight );
        update_post_meta( $this->product_english_id, '_length', $new_length );
        update_post_meta( $this->product_english_id, '_width', $new_width );
        update_post_meta( $this->product_english_id, '_height', $new_height );
        restore_current_blog();

        switch_to_blog( $this->spanish_blog_id );
        $weight = get_post_meta( $this->product_spanish_id, '_weight', true );
        $length = get_post_meta( $this->product_spanish_id, '_length', true );
        $width = get_post_meta( $this->product_spanish_id, '_width', true );
        $height = get_post_meta( $this->product_spanish_id, '_height', true );
        restore_current_blog();

        $this->assertTrue( $weight == 8.1648 );
        $this->assertTrue( $length == 58.42 );
        $this->assertTrue( $width == 20.32 );
        $this->assertTrue( $height == 86.36 );
    }



    

}
