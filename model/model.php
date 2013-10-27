<?php
class Multilingual_Woocommerce_Model {

	static $instance;

	// This option will tell WP if the schema has been created
	// Instead of using the activation hook, we'll use this
	// TODO: Change slug
	public $schema_created_option_slug = 'multilingual_woocommerce_schema_created';

	// Tables names
	private $queue_table;

	// Charset and Collate
	private $db_charset_collate;


	/**
	 * Return an instance of the class
	 * 
	 * @since 0.1
	 * 
	 * @return Object
	 */
	public static function get_instance() {
		if ( self::$instance === null )
			self::$instance = new self();
            
        return self::$instance;
	}
 
	/**
	 * Set the tables names, charset, collate and creates the schema if needed.
	 * This way, the schema will be created when the model is created for first time.
	 */
	protected function __construct() {
		global $wpdb;

		$this->queue_table = $wpdb->base_prefix . 'mlw_queue';
		$this->currencies_table = $wpdb->base_prefix . 'mlw_currencies';

		// Get the correct character collate
        $db_charset_collate = '';
        if ( ! empty($wpdb->charset) )
          $this->db_charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
          $this->db_charset_collate .= " COLLATE $wpdb->collate";

      	// Have we created the DB schema?
      	if ( false === get_site_option( $this->schema_created_option_slug ) ) {
      		$this->create_schema();

      		// TODO: Uncomment this whenever you want to create the schema
      		update_site_option( $this->schema_created_option_slug, true );
      	}
	}

	/**
	 * Create the required DB schema
	 * 
	 * @since 0.1
	 */
	public function create_schema() {
		$this->create_currencies_table();
		do_action( 'mlw_schema_created' );
	}

	/**
	 * Create the Currencies table
	 */
	private function create_currencies_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE $this->currencies_table (
              ID bigint(20) NOT NULL AUTO_INCREMENT,
              left_currency varchar(200) NOT NULL,
              right_currency varchar(200) NOT NULL,
              rate varchar(200) NOT NULL,
              PRIMARY KEY  (ID)
            )  ENGINE=MyISAM $this->db_charset_collate;";
       	
       	// TODO: Uncomment this whenever you want to create the schema
        dbDelta($sql);
	}

	/**
	 * Upgrades for the 0.2 version schema
	 */
	public function upgrade_schema_02() {
		// Be aware that the upgrade schema must be the same than
		// the create_schema code
		// So, if we need to upgrading table 2 is like creating it
		// Thanks to dbDelta function, this is easy:

		//$this->create_table2();
	}

	/**
	 * Drop the schema
	 */
	public function delete_tables() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS $this->currencies_table;" );
	}

	/**
	 * Get a the rate between two currencies
	 * 
	 * @param String $left_curr Currency
	 * @param String $right_cur Currency
	 * @return mixed False if no results found, Object with currency relationship data in other case
	 */
	public function get_currency_rate( $left_curr, $right_curr ) {
		global $wpdb;

		$results = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $this->currencies_table
				WHERE left_currency = %s
				AND right_currency = %s",
				$left_curr,
				$right_curr
			)
		);

		if ( empty( $results ) )
			return false;

		return $results;

	}

	/**
	 * Get a currency rate data by ID
	 * @param Integer $curr_id 
	 * @return Object Row with the data
	 */
	public function get_currency_rate_by_id( $curr_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->currencies_table WHERE ID = %d", $curr_id ) );
	}

	/**
	 * Insert a new currency rate
	 * 
	 * @param String $left_curr Currency
	 * @param String $right_curr Currency
	 * @param Float $rate 
	 */
	public function insert_currency_rate( $left_curr, $right_curr, $rate ) {
		global $wpdb;

		// Does the data exists already??
		$result = $this->get_currency_rate( $left_curr, $right_curr );
		if ( ! empty( $result ) ) {
			$this->update_currency_rate( $left_curr, $right_curr, $rate );
			return;
		}

		// Insert the currency rate
		$wpdb->insert(
			$this->currencies_table,
			array(
				'left_currency' => $left_curr,
				'right_currency' => $right_curr,
				'rate' => $rate
			),
			array( '%s', '%s', '%s' )
		);

		// Insert the inverse currency rate
		$wpdb->insert(
			$this->currencies_table,
			array(
				'right_currency' => $left_curr,
				'left_currency' => $right_curr,
				'rate' => 1 / $rate
			),
			array( '%s', '%s', '%s' )
		);
	}



	public function update_currency_rate( $left_curr, $right_curr, $new_rate ) {
		global $wpdb;
		
		// Get the currency rate data
		$result = $this->get_currency_rate( $left_curr, $right_curr );

		if ( ! empty( $result ) ) {

			// Get the inverse currency rate data
			$inverse_result = $this->get_currency_rate( $right_curr, $left_curr );

			// Update both
			$wpdb->update(
				$this->currencies_table,
				array( 'rate' => $new_rate ),
				array( 'ID' => $result->ID ),
				array( '%s' ),
				array( '%d' )
			);

			$wpdb->update(
				$this->currencies_table,
				array( 'rate' => 1 / $new_rate ),
				array( 'ID' => $inverse_result->ID ),
				array( '%s' ),
				array( '%d' )
			);
		}
		else {
			$this->insert_currency_rate( $result->left_currency, $result->right_currency, $new_rate );
		}
	}

	/**
	 * Delete a currency relationship
	 * 
	 * @param Integer $curr_id Currency ID
	 */
	public function delete_currency_rate( $curr_id ) {
		global $wpdb;

		// Get the currency rate data
		$results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->currencies_table WHERE ID = %d", $curr_id ) );

		if ( ! empty( $results ) ) {
			$l_curr = $results->left_currency;	
			$r_curr = $results->right_currency;

			// Get the inverse currency rate data
			$inverse_result = $this->get_currency_rate( $r_curr, $l_curr );

			// Delete both
			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->currencies_table WHERE ID = %d OR ID = %d", $curr_id, $inverse_result->ID ) );
		}
	}

	public function is_currency_rate( $left_curr, $right_curr ) {
		global $wpdb;

		$result = $this->get_currency_rate( $left_curr, $right_curr );
		if ( ! empty( $result ) )
			return true;

		return false;
	}



}