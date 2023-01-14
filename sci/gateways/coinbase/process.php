<?php
require_once('./../lib/coinbase.lib.php');

use Coinbase\Wallet\Client;
use Coinbase\Wallet\Value\Money;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Resource\Checkout;

if (isset($gateway) && $gateway === 'coinbase') {

  // generate gateway return URLs
  $cancel_url = $site_url.'?page=return&result=cancel&delay=2&code='.$order_code;
  $success_url = $site_url.'?page=return&result=success&delay=2&code='.$order_code;

  // insert new order into database
  if (is_numeric($total_btc)) {
    if (!isset($_GET['ocode'])) {
      $order_id = save_order($account_id, $total_btc, $shipping, $cart_str, 
	  $address, $note, $order_code, "empty:coinbase.com wallet");
	}
  } else {
    die(LANG('PROB_CALC_TOTAL').' '.LANG('TRY_REFRESHING'));
  }
  
  // generate Coinbase gateway link
  try {
	
	$cb_config = Configuration::apiKey($coinbase_api_key, $coinbase_api_secret);
	$cb_client = Client::create($cb_config);
	
	$params = array(
		'type'				=> 'order',
		'name'        		=> LANG('ORDER')." #$order_id",
		'amount'      		=> new Money($total_btc, 'BTC'),
		'success_url' 		=> $success_url,
		'cancel_url'  		=> $cancel_url,
		'auto_redirect' 	=> true,
		'metadata'      	=> array('order_code' => $order_code)
	);
	
	$cb_checkout = new Checkout($params);
	$cb_client->createCheckout($cb_checkout);
	$checkout_code = $cb_checkout->getEmbedCode();
	$gateway_url = "https://www.coinbase.com/checkouts/$checkout_code";	
	$coinbase_online = true;
	
  } catch (Exception $e) {
	if ($debug_coinbase) { die($e->getMessage()); }
	$coinbase_online = false;
  }
  
  // check the order was inserted into database
  if (isset($order_id) && is_numeric($order_id)) {
	
	// save order data to array
	$order_data = array(
	$total_btc, $buyer, $note, $order_id, 
    $exch_rate, $gateway, time());
	
	if (!isset($_GET['ocode'])) {
	  // encrypt order data
	  $t_data = bitsci::save_pay_query($order_data);
	
	  // save encrypted data to file
	  if (file_put_contents('t_data/'.$order_code, $t_data) !== false) {
	    chmod('t_data/'.$order_code, 0600);
	  } else {
        die(LANG('ERROR_CREATE_TRAN').' '.LANG('TRY_AGAIN_BACK'));
	  }
	}

    // go to coinbase gateway if online
	if ($coinbase_online) {
      redirect($gateway_url);
	} else {
      die('Coinbase '.LANG('GATEWAY_OFFLINE').' '.LANG('TRY_AGAIN_BACK'));
	}
  } else {
    die(LANG('ERROR_CREATE_TRAN').' '.LANG('TRY_AGAIN_BACK'));
  }
} else {
  die(LANG('ERROR_CREATE_TRAN').' '.LANG('TRY_AGAIN_BACK'));
}
?>