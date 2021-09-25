<?php

namespace y_ct_wp;

class y_ct_wp_rest_api {
	/**
	 * Hook WordPress
	 * @return void
	 */
	public function __construct(){
		add_filter('query_vars', array($this, 'add_query_vars'), 0);
		add_action('parse_request', array($this, 'sniff_requests'), 0);
		add_action('init', array($this, 'add_endpoint'), 0);
	}

	/**
	 * Add public query vars
	 * @param array $a_vars List of current public query vars
	 * @return array $a_vars
	 */
	public function add_query_vars($a_vars){
		$a_vars[] = '__yct_api';
		$a_vars[] = 'version';
		$a_vars[] = 'action';
		return $a_vars;
	}

	/**
	 * Sniff Requests
	 * This is where we hijack all API requests
	 * If $_GET['__yct_api'] is set, we kill WP and activate our API
	 * return die if API request
	 */
	public function sniff_requests(){
		global $wp;
		if(isset($wp->query_vars['__yct_api'])){
			$this->handle_request();
			exit;
		}
	}

	/**
	 * Add API Endpoint
	 * URL should first have the yct_api, then the version of the api being requested, and then the method being requested.
	 * We use regex for the version and method, pass it off to wp to turn the matches into parameters for this api file.
	 * @return void
	 */
	public function add_endpoint(){
		add_rewrite_rule('yct_api\/(v[0-9]+)\/([a-z]+)','index.php?__yct_api=1&version=$matches[1]&action=$matches[2]','top');
	}

	/**
	 * Handle Requests
	 * This is where we switch to the api version, and the method being requested.
	 * initially the api has one version, in the future, the methods for what will become older versions will be stored in separate files, and only read in if the case matches the request.
	 * Send results to this->send_response()
	 * @return void
	 */
	protected function handle_request(){
		global $wpdb; //This is required to interface with the SQL database. We could call for it only in the actions that need to interface with the db, but since most will, we just call it here
		global $wp;
		$yct_oTables= new yct_tables();
		$yct_version= $wp->query_vars['version'];

		if($yct_version == 'v1'){
			switch ($wp->query_vars['action']){
				case 'place':
					/**
					 * This statement is for testing purposes
					 * If these parameters are set, we generate new customer, payment auth, or product to test that type. Will not always be unique, but should suffice for testing.
					 */
					if(isset($_REQUEST['customer'])){
						$yct_newCustomer= rand();
					}
					if(isset($_REQUEST['payment'])){
						$yct_newPayment= rand();
					}
					if(isset($_REQUEST['product'])){
						$yct_newProduct= rand();
					}

					//region Json Data
					$yct_jsTestData='
					{
					   "customer":{
					      "email":"'.$yct_newCustomer.'john.doe@example.com"
					   },
					   "billing_address":{
					      "name":"John Doe",
					      "country":"SE",
					      "postcode":"111 52",
					      "city":"Stockholm",
					      "street":"Hantverkargatan 1"
					   },
					   "shipping_address":{
					      "name":"Mike Doe",
					      "country":"SE",
					      "postcode":"103 16",
					      "city":"Stockholm",
					      "street":"Stortorget 2"
					   },
					   "payment":{
					      "method":"card",
					      "authorization_id":"abc1234d'.$yct_newPayment.'"
					   },
					   "products":[
					      {
					         "sku":"yubikey-5-nfc'.$yct_newProduct.'",
					         "qty":2
					      },
					      {
					         "sku":"yubistyle-cover-urban-camo-acnfc",
					         "qty":1
					      },
					      {
					         "sku":"yubistyle-cover-purple-acnfc",
					         "qty":1
					      }
					   ]
					}
					';
					//endregion

					$yct_ch = curl_init( 'https://dev.ellenburgweb.host/yct/yct_api/v1/order' );
					//Setup request to send json via POST.
					curl_setopt( $yct_ch, CURLOPT_POSTFIELDS, $yct_jsTestData );
					curl_setopt( $yct_ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
					//Return response instead of printing.
					curl_setopt( $yct_ch, CURLOPT_RETURNTRANSFER, true );
					//Send request.
					$yct_aReturn = curl_exec($yct_ch);
					curl_close($yct_ch);
					//Print response.
					echo "<pre>$yct_aReturn</pre>";

					break;
				case 'order':
					$yct_jsOrder= file_get_contents("php://input");
					switch ($_SERVER['REQUEST_METHOD']){
						case 'POST':
							/**
							 * In this case, we are going to catch the json, parse it and do the following
							 * Create new Customer if does not exist: Email Address is primary identifier
							 * If we had a full customer profile module, we would also add/update addresses here
							 * Check payment auth and see if it exists on any other order, if it does kick back with error
							 * Make sure customer is not trying to reorder in a short time period
							 * Make sure the items being ordered are in our database, and is not fake items.
							 */
							$yct_aOrder= json_decode($yct_jsOrder, true);

							//region Find or Create the Customer
							$yct_customerEmail= $wpdb->_real_escape($yct_aOrder['customer']['email']);//Using the Wordpress call to mysqli_real_escape_string.
							if(filter_var($yct_customerEmail, FILTER_VALIDATE_EMAIL) == false){
								$yct_aReturn['status']= 'error';
								$yct_aReturn['message']= 'Invalid Email';
								$this->send_response($yct_aReturn);
							}
							$yct_aCustomer= $wpdb->get_row("SELECT * FROM $yct_oTables->yct_customers WHERE customer_email = '$yct_customerEmail'",ARRAY_A);
							if($yct_aCustomer == ''){
								$yct_newCustomer= 1;

								$yct_aCustomer= array(
									'customer_name_first'   => $yct_aOrder['billing_address']['name'],
									'customer_email'        => $yct_aOrder['customer']['email']
								);

								$yct_customerRecordResult= $wpdb->insert(
									$yct_oTables->yct_customers,
									$yct_aCustomer
								);

								if($yct_customerRecordResult === FALSE){
									$yct_aReturn['status']= 'error';
									$yct_aReturn['message']= $wpdb->last_error;
									$this->send_response($yct_aReturn);
								}
								else{
									$yct_aReturn['customer_status']= 'created';
								}
							}
							else{
								$yct_aReturn['customer_status']= 'found';
							}
							//endregion

							//region Compile Order Record
							//region Put the cart into order by SKU
							$yct_liSku= array_column($yct_aOrder['products'], 'sku');
							array_multisort($yct_liSku, SORT_DESC, $yct_aOrder['products']);
							$yct_jsProducts= json_encode($yct_aOrder['products']);
							//endregion

							//region Check for the Authorization ID and Order Time
							$yct_payment_auth= $yct_aOrder['payment']['authorization_id'];
							$yct_aPaymentAuth= $wpdb->get_row("SELECT * FROM $yct_oTables->yct_orders WHERE order_payment_authorization = '$yct_payment_auth'",ARRAY_A);
							if($yct_aPaymentAuth != ''){
								$yct_aReturn['status']= 'failed';
								$yct_aReturn['message']= 'Invalid Auth';
								$this->send_response($yct_aReturn);
							}
							//endregion

							//region Check Time Stamp and Cart
							$yct_aTimeCart= $wpdb->get_row("SELECT * FROM $yct_oTables->yct_orders WHERE order_products = '$yct_jsProducts'",ARRAY_A);
							if($yct_aTimeCart != ''){
								$yct_tooSoon= strtotime('+5 minutes', strtotime($yct_aPaymentAuth['order_date_time']));
								if($yct_tooSoon > strtotime(date('Y-m-j h:i:s'))){
									$yct_aReturn['status']= 'fast';
									$yct_aReturn['message']= 'You tried to order the same cart too soon';
									$this->send_response($yct_aReturn);
								}
							}
							//endregion

							//region Check that Products are Valid
							foreach($yct_aOrder['products'] as $yct_aProduct){
								$yct_productSku= $yct_aProduct['sku'];
								$yct_aProductValid= $wpdb->get_row("SELECT * FROM $yct_oTables->yct_line_items WHERE item_sku = '$yct_productSku'",ARRAY_A);
								if($yct_aProductValid == ''){
									$yct_aReturn['status']= 'invalid';
									$yct_aReturn['message']= 'You tried to order invalid products';
									$this->send_response($yct_aReturn);
								}
							}
							//endregion

							//region Create Order
							$yct_customerID= ($yct_newCustomer == 1) ? $wpdb->insert_id : $yct_aCustomer['id'];

							$yct_aOrderRecord= array(
								'order_customer_id'             => $yct_customerID,
								'order_billing_address'         => json_encode($yct_aOrder['billing_address']),
								'order_shipping_address'        => json_encode($yct_aOrder['order_shipping_address']),
								'order_products'                => $yct_jsProducts,
								'order_payment_method'          => $yct_aOrder['payment']['method'],
								'order_payment_authorization'   => $yct_aOrder['payment']['authorization_id'],
								'order_date_time'               => date('Y-m-j h:i:s'),
								'order_status'                  => 'In Process'
							);
							$yct_orderRecordResult= $wpdb->insert(
								$yct_oTables->yct_orders,
								$yct_aOrderRecord
							);

							if($yct_orderRecordResult === FALSE){
								$yct_aReturn['status']= 'failed';
								$yct_aReturn['message']= $wpdb->last_error;
								$this->send_response($yct_aReturn);
							}
							else{
								//region Create customer view ID
								/**
								 * Need to create unique ID for order, will be used for viewing the order.
								 * Normally this documentation would go into a separate file, but for the code test it will be here.
								 * For this unique ID, we will generate two random strings of 6 characters, and place the record ID between them
								 * This will give the code a pattern to look for if we ever need to extract the id, and it will allow us to create unique longform IDs in the table
								 * without having to search the table for any records with the randomly generated id.
								 */
								$yct_characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
								$yct_randomString = '';
								$yct_randomStringTwo = '';

								for ($i = 0; $i < 6; $i++) {
									$yct_index= rand(0, strlen($yct_characters) - 1);
									$yct_randomString.= $yct_characters[$yct_index];

									$yct_index = rand(0, strlen($yct_characters) - 1);
									$yct_randomStringTwo.= $yct_characters[$yct_index];
								}

								$yct_uniqueID= $yct_randomString.$wpdb->insert_id.$yct_randomString;
								$yct_orderRecordResult= $wpdb->update(
									$yct_oTables->yct_orders,
									array('order_view_id'   => $yct_uniqueID),
									array(
										'id'    => $wpdb->insert_id
									)
								);
								if($yct_orderRecordResult === false){
									//It did not Work
									$yct_aReturn['status']= 'error';
									$yct_aReturn['message']= $wpdb->last_error;
									$this->send_response($yct_aReturn);
								}
								//endregion
							}
							//endregion

							//endregion

							//region We only get here if everything worked
							$yct_aReturn['status']= 'success';
							$yct_aReturn['message']= 'Order has been placed.';
							$yct_aReturn['url']= "https://dev.ellenburgweb.host/yct/yct_api/v1/status/?id=$yct_uniqueID";
							$this->send_response($yct_aReturn);
							//endregion
							break;
						default:
							$yct_aReturn['status']= 'failed';
							$yct_aReturn['message']= "Unsupported Request Method";

							echo json_encode($yct_aReturn); //Current idea is that we will only return json encoded results.
							break;
					}
					break;
				case 'status':
					//I thought about putting this in the GET part of the order action, but decided against it since the order action is for getting and posting the full order,
					//Not for a simple status request
					$yct_uniqueID= $_REQUEST['id'];
					$yct_aOrder= $wpdb->get_row("SELECT order_status FROM $yct_oTables->yct_orders WHERE order_view_id = '$yct_uniqueID'",ARRAY_A);

					if($yct_aOrder != ''){
						echo "Your order status is: ".$yct_aOrder['order_status'];
					}
					else{
						echo "There is no order with that ID";
					}
					break;
				default:
					$yct_action= $wp->query_vars['action']; //For the time being, this is the only time we will assign $wp->query_vars['action'] to a variable, if that changes, we will move this assignment so that it is more accessable.
					$yct_aReturn['status']= 'failed';
					$yct_aReturn['message']= "No action with $yct_action exists";

					echo json_encode($yct_aReturn); //Current idea is that we will only return json encoded results.
					break;
			}
		}

	}

	/**
	 * Response Handler
	 * This sends a JSON response to the browser
	 */
	protected function send_response($yct_aReturn){
		echo json_encode($yct_aReturn)."\n";
		exit;
	}
}
