<?php

namespace y_ct_wp;

/**
 * Class $rpgtk_tables
 * Allows us to get our custom table names consistently throughout our plugin.
 *
 * @property string yct_orders
 * @property string yct_customers
 * @property string yct_addresses
 * @property string yct_line_items
 *
 */

class yct_tables {
	public function __construct() {
		//region Global Variables, Classes, Class Variables, Local Variables
		global $wpdb;
		//endregion Global Variables, Classes, Class Variables, Local Variables


		$this->yct_orders=      $wpdb->prefix . 'yct_orders';
		$this->yct_customers=   $wpdb->prefix . 'yct_customers';
		$this->yct_addresses=   $wpdb->prefix . 'yct_addresses';
		$this->yct_line_items=  $wpdb->prefix . 'yct_line_items';
	}
}
