<?php

/**
 * Get the plugin model instance
 * 
 * @return Object
 */
function mlw_get_model() {
	return Multilingual_Woocommerce_Model::get_instance();
}

/**
 * Get the Settings handler instance
 * 
 * @return Object
 */
function mlw_get_settings_handler() {
	return Multilingual_Woocommerce_Settings_Handler::get_instance();
}

/**
 * Get the rate between two currencies
 *
 * @param String $l_curr Currency
 * @param String $r_curr Currency
 *
 * @return Float Currency rate
 */
function mlw_get_currency_rate( $l_curr, $r_curr ) {
	$model = mlw_get_model();
	$rate = $model->get_currency_rate( $l_curr, $r_curr );

	if ( ! $rate )
		$result = 1;
	else
		$result = floatval( $rate->rate );

	return apply_filters( 'mlw_currency_rate', $result, $l_curr, $r_curr );
}

/**
 * Insert a new rate between two currencies
 * @param String $l_curr Currency
 * @param String $r_curr Currency
 * @param Float $rate Currency rate
 */
function mlw_insert_new_currency_rate( $l_curr, $r_curr, $rate ) {
	$model = mlw_get_model();
	$model->insert_currency_rate( $l_curr, $r_curr, $rate );
}

/**
 * Update a new rate between two currencies
 * @param String $l_curr Currency
 * @param String $r_curr Currency
 * @param Float $new_rate Currency rate
 */
function mlw_update_currency_rate( $l_curr, $r_curr, $new_rate ) {
	$model = mlw_get_model();
	$model->update_currency_rate( $l_curr, $r_curr, $new_rate );
}

/**
 * Delete a rate between two currencies
 * @param String $l_curr Currency
 * @param String $r_curr Currency
 */
function mlw_delete_currency_rate( $l_curr, $r_curr ) {
	$model = mlw_get_model();
	$data = $model->get_currency_rate( $l_curr, $r_curr );
	$model->delete_currency_rate( $data->ID );
}