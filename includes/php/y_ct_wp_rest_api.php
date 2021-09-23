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
			echo 'api '.$wp->query_vars['__yct_api']."<br />";
			echo 'version '.$wp->query_vars['version']."<br />";
			echo 'action '.$wp->query_vars['action']."<br />";
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
	 * Send Json response
	 */
	protected function handle_request(){
		global $wpdb; //This is required to interface with the SQL database. We could call for it only in the actions that need to interface with the db, but since most will, we just call it here
		global $wp;
		$yct_version= $wp->query_vars['version'];

		if($yct_version == 'v1'){
			switch ($wp->query_vars['action']){
				case 'test':
					echo "Begin <br />";
					//region Json Data
					$yct_jsTestData='
					{
					   "customer":{
					      "email":"john.doe@example.com"
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
					      "authorization_id":"abc1234d"
					   },
					   "products":[
					      {
					         "sku":"yubikey-5-nfc",
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

					echo "End";
					break;
				case 'order':
					$yct_jsOrder= file_get_contents("php://input");

					switch ($_SERVER['REQUEST_METHOD']){
						case 'POST':
							echo $yct_aReturn['message']= $yct_jsOrder;
							break;
						default:
							$yct_aReturn['status']= 'failed';
							$yct_aReturn['message']= "Unsupported Request Method";

							echo json_encode($yct_aReturn); //Current idea is that we will only return json encoded results.
							break;
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
}
