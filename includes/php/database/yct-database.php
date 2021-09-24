<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 1/8/2019
 * Time: 19:20
 * Name: Database Setup
 * Desc: Runs on install or update if the stored dbVersion is less than the current
 */





/**
 * Name: EW yct Install Database
 * @param string $yct_dbVersion
 */
function yct_install_database($yct_dbVersion){
	//region Global Variables, Local Variables, Classes
	global $wpdb;
	$yct_oTables= new \y_ct_wp\yct_tables();
	$charset_collate= $wpdb->get_charset_collate();
	//endregion

	//region Include WP file that lets us use DB Delta
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	//endregion

	//region Create Customer Table
	$yct_sql= "
		CREATE TABLE $yct_oTables->yct_customers (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			customer_name_first text,
			customer_name_last text,
			customer_email text,
			unique key id (id),
			primary key id (id)
		)
		$charset_collate;
	";
	dbDelta($yct_sql); //Hand the SQL off to Wordpress to execute.
	//endregion

	//region Create Addresses Table
	$yct_sql= "
		CREATE TABLE $yct_oTables->yct_addresses (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			address_customer_id mediumint(9) NOT NULL,
			address_name text NOT NULL,
			address_type text NOT NULL,
			address_street text NOT NULL,
			address_city text NOT NULL,
			address_postcode text NOT NULL,
			address_country text NOT NULL,
			unique key id (id),
			primary key id (id)
		)
		$charset_collate;
	";
	dbDelta($yct_sql); //Hand the SQL off to Wordpress to execute.
	//endregion

	//region Create Order Table
	$yct_sql= "
		CREATE TABLE $yct_oTables->yct_orders (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			order_customer_id mediumint(9) NOT NULL,
			order_billing_address text NOT NULL,
			order_shipping_address text NOT NULL,
			order_payment_method text NOT NULL,
			order_payment_authorization text NOT NULL,
			order_products text NOT NULL,
			order_date_time timestamp NOT NULL,
			unique key id (id),
			primary key id (id)
		)
		$charset_collate;
	";
	dbDelta($yct_sql); //Hand the SQL off to Wordpress to execute.
	//endregion

	//region Create Line Items Table
	$yct_sql= "
		CREATE TABLE $yct_oTables->yct_line_items (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			item_sku text NOT NULL,
			item_price text,
			unique key id (id),
			primary key id (id)
		)
		$charset_collate;
	";
	dbDelta($yct_sql); //Hand the SQL off to Wordpress to execute.
	//endregion

	update_option( 'yct_db_version', $yct_dbVersion );
}

//region Call the DB install function when the plugin is activated
register_activation_hook( __FILE__, 'yct_install_database' );
//endregion

//region Database Update Process
//region Call the DB install function if there is a new version
function yct_update_db_check() {
	$yct_dbVersion = '0.0.1';//db.table.field
	//if (get_option( 'yct_db_version' ) < $yct_dbVersion) {
		yct_install_database($yct_dbVersion);
	//}

	yct_update_db_data_check();
}
//endregion

//region //Run the db update check
add_action( 'plugins_loaded', 'yct_update_db_check' );
//endregion
//endregion
