<?php


class Multilingual_Woocommerce_Product_Updater {

	// Will save the meta_keys to update
	private $meta_list = array();

	public function __construct() {
		// TODO: set the meta_keys to update based on the plugin settings

		add_action( 'updated_post_meta', array( &$this, 'update_product_meta' ), 10, 4 );

		// TODO: add_action( 'init', array( &$this, 'execute_queue' ) )
	}

	public function update_product_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
		global $wpdb;

		if ( '_regular_price' == $meta_key ) {
			$blog_id = get_current_blog_id();
			$product_id = $object_id;

			$linked_elements = Mlp_Helpers::load_linked_elements( $product_id, '', $blog_id );

			foreach ( $linked_elements as $blog_id => $product_id ) {
				// TODO: Control if the blog exists
				switch_to_blog( $blog_id, false );
				// TODO: Get currency and set the rate
				// TODO: Instead of updating is better to save all the queries in the database
				// columns in the table: ID,blog_id,action(name of the function),args for the function
				update_post_meta( $product_id, $meta_key, $meta_value );
				restore_current_blog();
			}
		}

		
	}

	public function execute_queue() {
		// Get all items in queue for blog_id = current blog ID
	}

}
