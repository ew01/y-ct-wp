<?php
/**
 * Created by PhpStorm.
 * Author: David
 * Date: 1/6/2020
 * Time: 11:59
 * Name:
 * Desc:
 */





function yct_database_data($yct_dbDataVersion){
	global $wpdb;
	$yct_oTables= new \y_ct_wp\yct_tables();

	//region Line Items
	$yct_aLineItems= array(
		/*Template array(
			'item_sku'   => 'product sku'
		),*/
		array(
			'item_sku'   => 'yubikey-5-nfc',
		),
		array(
			'item_sku'   => 'yubistyle-cover-urban-camo-acnfc'
		),
		array(
			'item_sku'   => 'yubistyle-cover-purple-acnfc'
		),
	);

	foreach($yct_aLineItems as $yct_aLineItem){
		$yct_lineItemSku= $yct_aLineItem['item_sku'];

		$yct_aRecord= $wpdb->insert(
			$yct_oTables->yct_line_items,
			$yct_aLineItem
		);

		/*
		if($yct_aRecord === false){
			echo $wpdb->last_error;
			exit;
		}
		*/
	}
	//endregion

	update_option( 'yct_db_data_version', $yct_dbDataVersion );
}

function yct_update_db_data_check() {
	$yct_dbDataVersion = '0.0.5';
	if (get_option( 'yct_db_data_version' ) < $yct_dbDataVersion) {
		yct_database_data($yct_dbDataVersion);
	}
}

/**
 * Add the action to WP process.
 * This should be called after the DB is built/updated. If your data is not going in, make sure this line is used in the DB update check.
 * We keep it commented here to ensure that it does not fire before the DB update function.
 */
//add_action( 'plugins_loaded', 'yct_update_db_data_check' );
