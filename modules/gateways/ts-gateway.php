<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function ts_MetaData()
{
    return array(
        'DisplayName' => 'TRTL Services',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function ts_Config(){
	return array(
		'FriendlyName' => array('Type' => 'System','Value' => 'TRTL Services'),
		'address' => array('FriendlyName' => 'TRTL Services Address','Type' => 'text','Size' => '187','Default' => '','Description' => 'Not used yet'),
		'secretkey' => array('FriendlyName' => 'Module Secret Key','Type' => 'text','Default' => '21ieudgqwhb32i7tyg','Description' => 'Enter a unique key to verify callbacks'),
		'webhook_token' => array('FriendlyName' => 'Wallet RPC Host','Type' => 'text','Default' => '','Description' => 'Connection settings for the TRTL Services Wallet RPC daemon.'),

		'discount_percentage' => array('FriendlyName' => 'Discount Percentage','Type'  => 'text','Default' => '0%','Description' => 'Percentage discount for paying with TRTL Services.')
    );
}

/*
*  
*  Get the current XMR price in several currencies
*  
*  @param String $currencies  List of currency codes separated by comma
*  
*  @return String  A json string in the format {"CURRENCY_CODE":PRICE}
*  
*/
function ts_retrivePriceList($currencies = 'BTC,USD,EUR,CAD,INR,GBP,BRL') {
	
	$source = 'https://min-api.cryptocompare.com/data/price?fsym=TRTL&tsyms='.$currencies.'&extraParams=trtl_woocommerce';
	
	if (ini_get('allow_url_fopen')) {
		
		return file_get_contents($source);
		
	}
	
	if (!function_exists('curl_init')) {
		
		echo 'cURL not available.';
		
		return false;
		
	}
	
	$options = array (
		CURLOPT_URL            => $source,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_TIMEOUT        => 20,
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $options);
	
	$trtl_price = curl_exec($ch);
	
	curl_close($ch);
	
	if ($trtl_price === false) {
		
		echo 'Error while retrieving TRTL price list';
		
	}
	
	return $trtl_price;
	
}

function ts_retriveprice($currency) {
	global $currency_symbol;
	$trtl_price = trtl_retrivePriceList('BTC,USD,EUR,CAD,INR,GBP,BRL');
    $price = json_decode($trtl_price, TRUE);
	if(!isset($price)){
		echo "There was an error";
	}
	if ($currency == 'USD') {
		$currency_symbol = "$";
		return $price['USD'];
	}
	if ($currency == 'EUR') {
		$currency_symbol = "€";
		return $price['EUR'];
	}
	if ($currency == 'CAD'){
		$currency_symbol = "$";
		return $price['CAD'];
	}
	if ($currency == 'GBP'){
		$currency_symbol = "£";
		return $price['GBP'];
	}
	if ($currency == 'INR'){
		$currency_symbol = "₹";
		return $price['INR'];
	}
	if ($currency == 'BRL'){
		$currency_symbol = "R$ ";
		return $price['BRL'];
	}
	if($currency == 'TRTL'){
		$price = '1';
		return $price;
	}
}

function ts_changeto($amount, $currency){
    $xmr_live_price = trtl_retriveprice($currency);
	$live_for_storing = $xmr_live_price * 100; //This will remove the decimal so that it can easily be stored as an integer
	$new_amount = $amount / $xmr_live_price;
	$rounded_amount = round($new_amount, 12);
    return $rounded_amount;
}

function ts_to_fiat($amount, $currency){
    $xmr_live_price = trtl_retriveprice($currency);
    $amount = $amount / 1000000000000;
	$new_amount = $amount * $xmr_live_price;
	$rounded_amount = round($new_amount, 2);
    return $rounded_amount;
}


function ts_link($params) {

	global $currency_symbol;
	$gatewaymodule = "ts";
	$gateway = getGatewayVariables($gatewaymodule);

	if(!$gateway["type"]) die("Module not activated");


	$invoiceid = $params['invoiceid'];
	$amount = $params['amount'];
	$discount_setting = $gateway['discount_percentage'];
	$discount_percentage = 100 - (preg_replace("/[^0-9]/", "", $discount_setting));
	$amount = money_format('%i', $amount * ($discount_percentage / 100));
	$currency = $params['currency'];
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	//$address = $params['address'];
	$systemurl = $params['systemurl'];
    // Transform Current Currency into TRTL Services
	$amount_xmr = trtl_changeto($amount, $currency);
	
	$post = array(
        'invoice_id'    => $invoiceid,
        'systemURL'     => $systemurl,
        'buyerName'     => $firstname . ' ' . $lastname,
        'buyerAddress1' => $address1,
        'buyerAddress2' => $address2,
        'buyerCity'     => $city,
        'buyerState'    => $state,
        'buyerZip'      => $postcode,
        'buyerEmail'    => $email,
        'buyerPhone'    => $phone,
        'address'       => $address,
        'amount_xmr'    => $amount_xmr,
        'amount'        => $amount,
        'currency'      => $currency     
    );
	$form = '<form action="' . $systemurl . '/modules/gateways/trtl/createinvoice.php" method="POST">';
    foreach ($post as $key => $value) {
        $form .= '<input type="hidden" name="' . $key . '" value = "' . $value .'" />';
    }
    $form .= '<input type="submit" value="' . $params['langpaynow'] . '" />';
    $form .= '</form>';
	$form .= '<p>'.$amount_xmr. " TRTL (". $currency_symbol . $amount . " " . $currency .')</p>';
	if ($discount_setting > 0) {
		$form .='<p><small>Discount Applied: ' . preg_replace("/[^0-9]/", "", $discount_setting) . '% </small></p>';
	}
    return $form;
}
