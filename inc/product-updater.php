<?php


class Multilingual_Woocommerce_Product_Updater {

	// Will save the meta_keys to update
	private $meta_list = array();
	private $updatable_meta = array();

	public function __construct() {
		// TODO: set the meta_keys to update based on the plugin settings
		$this->add_updated_meta_action();
		
		$this->updatable_meta = array(
			'product_data' => array( '_downloadable', '_virtual' ), //TODO: Product type is based on terms!!!
			'prices' => array( '_regular_price' ),
			'sale_prices' => array( '_sale_price' ),
			'sale_prices_dates' => array( '_sale_price_dates_from', '_sale_price_dates_to' ),
			'stock' => array( '_stock', '_stock_status', '_manage_stock', '_backorders', '_sold_individually' ),
			'downloads' => array( '_file_paths', '_download_limit', '_download_expiry' ),
			'shipping' => array( '_weight', '_length', '_width', '_height' ), //TODO: product_shipping_class is based on terms!!!
			'linked_products' => array( '_upsell_ids', '_crosssell_ids' ), //THese can be deleted too AND previous_parent_id is different
			'attributes' => array( '_product_attributes' ),
			'advanced' => array( '_purchase_note', 'menu_order', 'comment_status' ) //menu_order and comment_status is in posts table

		);

		$this->deletable_meta = array(
			'linked_products' => array( '_upsell_ids', '_crosssell_ids' )
		);
		// TODO: add_action( 'init', array( &$this, 'execute_queue' ) )


	}

	private function add_updated_meta_action() {
		add_action( 'updated_post_meta', array( &$this, 'update_product_meta' ), 10, 4 );
		add_action( 'added_post_meta', array( &$this, 'update_product_meta' ), 10, 4 );
		add_action( 'deleted_post_meta', array( &$this, 'delete_product_meta' ), 10, 3 );
	}

	private function remove_updated_meta_action() {
		remove_action( 'updated_post_meta', array( &$this, 'update_product_meta' ), 10, 4 );
		remove_action( 'added_post_meta', array( &$this, 'update_product_meta' ), 10, 4 );
		remove_action( 'deleted_post_meta', array( &$this, 'delete_product_meta' ), 10, 3 );
	}

	public function update_product_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
		global $wpdb;

		if ( in_array( $meta_key, $this->updatable_meta['prices'] ) ) {
			$this->update_currency_meta_value( $object_id, $meta_key, $meta_value );
		}

		if ( in_array( $meta_key, $this->updatable_meta['sale_prices'] ) ) {
			$this->update_currency_meta_value( $object_id, $meta_key, $meta_value );
		}

		if ( in_array( $meta_key, $this->updatable_meta['sale_prices_dates'] ) ) {
			$this->update_simple_meta_value( $object_id, $meta_key, $meta_value );
		}

		if ( in_array( $meta_key, $this->updatable_meta['stock'] ) ) {
			$this->update_simple_meta_value( $object_id, $meta_key, $meta_value );
		}

		if ( in_array( $meta_key, $this->updatable_meta['downloads'] ) ) {
			$this->update_simple_meta_value( $object_id, $meta_key, $meta_value );
		}

		if ( in_array( $meta_key, $this->updatable_meta['shipping'] ) ) {
			if ( $meta_key == '_weight' )
				$this->update_weight_meta_value( $object_id, $meta_key, $meta_value );
			else
				$this->update_dimension_meta_value( $object_id, $meta_key, $meta_value );
		}

	}

	public function delete_product_meta( $meta_id, $object_id, $meta_key ) {
		if ( in_array( $meta_key, $this->deletable_meta['linked_products'] ) ) {
			$blog_id = get_current_blog_id();
			$linked_elements = Mlp_Helpers::load_linked_elements( $product_id, '', $blog_id );
			$this->remove_updated_meta_action();

			$current_blog_id = get_current_blog_id();
			foreach ( $linked_elements as $blog_id => $product_id ) {

				switch_to_blog( $blog_id );

				delete_post_meta( $product_id, $meta_key );
			}

			$this->add_updated_meta_action();

			switch_to_blog( $current_blog_id );
		}
	}

	public function execute_queue() {
		// Get all items in queue for blog_id = current blog ID
	}

	private function update_simple_meta_value( $product_id, $meta_key, $meta_value, $blog_id = 0 ) {
		$blog_id = ( $blog_id == 0 ) ? get_current_blog_id() : $blog_id;

		$linked_elements = Mlp_Helpers::load_linked_elements( $product_id, '', $blog_id );

		$this->remove_updated_meta_action();

		$current_blog_id = get_current_blog_id();
		foreach ( $linked_elements as $blog_id => $product_id ) {

			switch_to_blog( $blog_id );

			update_post_meta( $product_id, $meta_key, $meta_value );
		}

		$this->add_updated_meta_action();

		switch_to_blog( $current_blog_id );
	}

	private function update_currency_meta_value( $product_id, $meta_key, $meta_value, $blog_id = 0 ) {
		$blog_id = ( $blog_id == 0 ) ? get_current_blog_id() : $blog_id;

		$linked_elements = Mlp_Helpers::load_linked_elements( $product_id, '', $blog_id );
		$src_curr = get_woocommerce_currency();

		$this->remove_updated_meta_action();

		$current_blog_id = get_current_blog_id();
		foreach ( $linked_elements as $blog_id => $product_id ) {

			switch_to_blog( $blog_id );
			$dest_curr = get_woocommerce_currency();

			$new_value = mlw_convert_currency( $src_curr, $meta_value, $dest_curr );

			update_post_meta( $product_id, $meta_key, $new_value );
		}

		$this->add_updated_meta_action();

		switch_to_blog( $current_blog_id );
	}

	private function update_weight_meta_value( $product_id, $meta_key, $meta_value, $blog_id = 0 ) {
		$blog_id = ( $blog_id == 0 ) ? get_current_blog_id() : $blog_id;

		$linked_elements = Mlp_Helpers::load_linked_elements( $product_id, '', $blog_id );

		$this->remove_updated_meta_action();

		$current_blog_id = get_current_blog_id();

		foreach ( $linked_elements as $blog_id => $product_id ) {

			$weight_unit = strtolower( get_blog_option( $blog_id, 'woocommerce_weight_unit') );
			$new_value = woocommerce_get_weight( $meta_value, $weight_unit );

			switch_to_blog( $blog_id );
			update_post_meta( $product_id, $meta_key, $new_value );
			restore_current_blog();
		}

		$this->add_updated_meta_action();


	}



	private function update_dimension_meta_value( $product_id, $meta_key, $meta_value, $blog_id = 0 ) {
		$blog_id = ( $blog_id == 0 ) ? get_current_blog_id() : $blog_id;

		$linked_elements = Mlp_Helpers::load_linked_elements( $product_id, '', $blog_id );

		$this->remove_updated_meta_action();

		$current_blog_id = get_current_blog_id();
		foreach ( $linked_elements as $blog_id => $product_id ) {

			$dimension_unit = strtolower( get_blog_option( $blog_id, 'woocommerce_dimension_unit') );
			$new_value = woocommerce_get_dimension( $meta_value, $dimension_unit );

			switch_to_blog( $blog_id );
			update_post_meta( $product_id, $meta_key, $new_value );
			restore_current_blog();
		}

		$this->add_updated_meta_action();

	}

}
