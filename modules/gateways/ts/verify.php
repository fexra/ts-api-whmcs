<?php

include("../../../init.php"); 
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

use Illuminate\Database\Capsule\Manager as Capsule;

use TRTLservices\TRTLServices;

$config = [
    'token' => 'eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoidGVzdCIsImFwcElkIjo0LCJ1c2VySWQiOjYsInBlcm1pc3Npb25zIjpbImFkZHJlc3M6bmV3Il0sImlhdCI6MTUzNjU4NTM2NywiZXhwIjoxNTM5MTc3MzY3LCJhdWQiOiJ0dXJ0bGV3YWxsZXQuaW8iLCJpc3MiOiJUUlRMIFNlcnZpY2VzIiwianRpIjoiMzMifQ.AEHXmvTo8RfNuZ15Y3IGPRhZPaJxFSmOZvVv2YGN9L4We7bXslIPxhMv_n_5cNW8sIgE2Fr-46OTb5H5AFgpjA',
    'timeout' => 2000
];

$TS = new TRTLServices($config);

$fee = "0.1";
$status = "unknown";
$gatewaymodule = "ts";
$GATEWAY = getGatewayVariables($gatewaymodule);

$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
$invoice_id = $_POST['invoice_id'];
$payment_id = $_POST['payment_id'];
$amount_trtl = $_POST['amount_trtl'];
$amount = $_POST['amount'];
$hash = $_POST['hash'];
$currency = $_POST['currency'];


$secretKey = $GATEWAY['secretkey'];
$link = $GATEWAY['daemon_host'].":".$GATEWAY['daemon_port']."/json_rpc";

require_once('library.php');


function verify_payment($payment_id, $amount, $amount_trtl, $invoice_id, $fee, $status, $gatewaymodule, $hash, $secretKey, $currency) {
	global $currency_symbol;
	$trtl_daemon = new TS_rpc($link);
	$check_mempool = true;
	//Checks invoice ID is a valid invoice number 
	$invoice_id = checkCbInvoiceID($invoice_id, $gatewaymodule);

	if ($payment_id !="") {

		//Validate callback authenticity
		if ($hash != md5($invoice_id . $payment_id . $amount_trtl . $secretKey)) {
			return 'Hash Verification Failure';
		}
		$message = "Waiting for your payment.";

 		//payment_id is sometimes empty

		// send each trtl tx in the mempool to handle_whmcs
		if ($check_mempool) {
			$get_payments_method = $trtl_daemon->get_transfers('pool', true);

			foreach ($get_payments_method["pool"] as $tx => $transactions) {
				$txn_amt = $transactions["amount"];
				$txn_txid = $transactions["txid"];
				$txn_payment_id = $transactions["payment_id"];
				if(isset($txn_amt)) { 
					return handle_whmcs($invoice_id, $amount_trtl, $txn_amt, $txn_txid, $txn_payment_id, $payment_id, $currency, $gatewaymodule);
				}
			}
		}
		// send each trtl tx to handle_whmcs
		$get_payments_method = $trtl_daemon->get_payments($payment_id);

		foreach ($get_payments_method["payments"] as $tx => $transactions) {
			$txn_amt = $transactions["amount"];
			$txn_txid = $transactions["tx_hash"];
			$txn_payment_id = $transactions["payment_id"];
			if(isset($txn_amt)) { 
				return handle_whmcs($invoice_id, $amount_trtl, $txn_amt, $txn_txid, $txn_payment_id, $payment_id, $currency, $gatewaymodule);
			}
		}
	} else {
		return "Error: No payment ID.";
	}
	return $message;
}

function handle_whmcs($invoice_id, $amount_trtl, $txn_amt, $txn_txid, $txn_payment_id, $payment_id, $currency, $gatewaymodule) {
	$amount_atomic_units = $amount_trtl * 1000000000000;
	
	//check if trtl tx already exists in whmcs 
	$record = Capsule::table('tblaccounts')->where('transid', $txn_txid)->get();
	$transaction_exists = $record[0]->transid;
	if ($txn_payment_id == $payment_id) {
		if (!$transaction_exists) {
			//check one more time then add the payment if the transaction has not been added.
			checkCbTransID($txn_txid);
			$fiat_paid = xmr_to_fiat($txn_amt, $currency);
			add_payment("AddInvoicePayment", $invoice_id, $txn_txid, $gatewaymodule, $fiat_paid, $txn_amt / 1000000000000, $payment_id, $fee);
		}
		// add 2% when doing the comparison in case of price fluctuations?
		if ($txn_amt * 1.02 >= $amount_atomic_units) {
			return "Payment has been received.";
		} else {
			return "Error: Amount " . $txn_amt / 1000000000000 . " TRTL too small. Please send full amount or contact customer service. Transaction ID: " . $txn_txid . ". Payment ID: " . $payment_id;
		}
	}
}


function add_payment($command, $invoice_id, $txn_txid, $gatewaymodule, $fiat_paid, $amount_trtl, $payment_id, $fee) {
	$postData = array(
		'action' => $command,
		'invoiceid' => $invoice_id,
		'transid' => $txn_txid,
		'gateway' => $gatewaymodule,
		'amount' => $fiat_paid,
		'amount_trtl' => $amount_trtl,
		'paymentid' => $payment_id,
		'fees' => $fee,
	);
	// Add the invoice payment - either of the next two lines work
	// $results = localAPI($command, $postData, $adminUsername);
    	addInvoicePayment($invoice_id, $txn_txid, $fiat_paid, $fee, $gatewaymodule);
	logTransaction($gatewaymodule, $postData, "Success: ".$message);
}


/*
function stop_payment($payment_id, $amount, $invoice_id, $fee, $link){
	$verify = verify_payment($payment_id, $amount, $invoice_id, $fee, $link);
	if($verify){
		$message = "Payment has been received and confirmed.";
	}
	else{
		$message = "We are waiting for your payment to be confirmed";
	}
} */

$vefiry = verify_payment($payment_id, $amount, $amount_trtl, $invoice_id, $fee, $status, $gatewaymodule, $hash, $secretKey, $currency);
echo $vefiry;
