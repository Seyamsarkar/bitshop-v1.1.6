<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/../../../lib/common.lib.php');
require_once(dirname(__FILE__).'/../../../lib/coinbase.lib.php');

use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;

// set up a few directory strings
$dat_file = dirname(__FILE__).'/../../t_data/';
$ipn_file = dirname(__FILE__).'/../../ipn-control.php';

// get data being passed to us
$raw_body = file_get_contents('php://input');
$post_data = json_decode($raw_body, true);

// ensure callback secret matches
if (empty($_GET['s']) || $_GET['s'] !== $coinbase_call_secret) {
  header('HTTP/1.1 400 Bad request', true, 400);
  exit;
}

// create coinbase client
$cb_config = Configuration::apiKey($coinbase_api_key, $coinbase_api_secret);
$cb_client = Client::create($cb_config);

// verify authenticity of callback
if (empty($_SERVER['HTTP_CB_SIGNATURE']) || 
!$cb_client->verifyCallback($raw_body, $_SERVER['HTTP_CB_SIGNATURE'])) {
  header('HTTP/1.1 400 Bad request', true, 400);
  exit;
}
	
// get transaction details
$tran_id = $post_data['data']['id'];
$amount_paid = $post_data['data']['amount']['amount'];
$currency = $post_data['data']['amount']['currency'];
$cust_code = $post_data['data']['metadata']['order_code'];

switch ($post_data['type']) {
case 'wallet:orders:paid':
  $pay_status = 'paid';
  break;
case 'wallet:orders:mispaid':
  $pay_status = 'mispaid';
  break;
default:
  $pay_status = 'invalid';
}

// get order data from file
$t_code = preg_replace("/[^a-z0-9]/i", '', $cust_code);
$t_data = get_key_data($dat_file, $t_code);

// make sure the order was found
if ($t_data !== false) {

  $t_data = bitsci::read_pay_query($t_data);
  list($total, $buyer, $note, $order_id, 
  $exch_rate, $gateway, $order_time) = $t_data;

  switch ($pay_status) {
  case 'mispaid':
	$status = 'Underpaid';
	break;
  case 'paid':
	require($ipn_file);
	if ($error !== false) {
	  $status = 'Callback Error';
	}
	break;
  case 'expired':
	$status = 'Expired/Invalid';
	break;
  default: 
	$status = 'Unknown';
  }

} else {
  header('HTTP/1.1 500 Internal Error', true, 500);
  exit;
}

if (isset($status)) {      
  // connect to database
  $hide_crash = true;
  $conn = connect_to_db();

  // update payment status in db
  set_order_status($order_id, $status);
}

// return error code if unable to confirm order
if (isset($error) && $error !== false) {
  header('HTTP/1.1 500 Internal Error', true, 500);
} else {
  header('HTTP/1.1 200 OK', true, 200);
}
?>
