<?php
/**
 * Plugin Name: Yubico Code Test in Wordpress
 * Plugin URI:
 * Description:
 * Author: David Ellenburg (ew01)
 * Version: 0.0.1
 * Author URI: http://www.ellenburgweb.com
 **/





//region Includes
include_once ( __DIR__ . "/includes/php/y_ct_wp_rest_api.php" );
//endregion

//region Start the API
new \y_ct_wp\y_ct_wp_rest_api();
//endregion
