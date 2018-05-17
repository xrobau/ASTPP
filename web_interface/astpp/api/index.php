<?php

// Note: There is PHP 5.4 syntax here. This is deliberate.

// SlimPHP needs PHP 5.5 or higher, and you SHOULD be using
// PHP 7.1 (for its massive performance improvements)

// Any code in the API folder should assume PHP 7.0 as the minimum.

// I'm leaving this commented out, but if anyone EVER asks me about why
// this code isn't working, it's going in.
//
// if (version_compare(PHP_VERSION, '7.0.0) == -1 ) {
//   throw new \Exception("Cmon. PHP 7.0 or higher. We're not here to fuck spiders.");
// }

include __DIR__.'/../vendor/autoload.php';

$api = new \Slim\App( [
         'settings' => [ 'displayErrorDetails' => true ]
] );

/**
 * Always auth all calls
 */
$api->add(new \API\ApiAuth);


$api->get("/ping", function($req, $resp) {
	return $resp->withJson(["pong" => true]);
});

/**
 * Get all customers.
 *
 * @returns json
 */
$api->get("/v1/customers", function($req, $resp) {
	$cust = new ASTPP\Customer;
	return $resp->withJson($cust->getAll());
});

/**
 * Get all DIDs.
 *
 * @returns json
 */
$api->get("/v1/dids", function($req, $resp) {
	$dids = new ASTPP\Dids;
	return $resp->withJson($dids->getAll());
});


/**
 * Get all SIP Devices
 * 
 * @param all (bool) If set, returns all devices, not just active ones
 *
 * @returns json
 */
$api->get("/v1/sipdevices", function($req, $resp) {
	$sip = new ASTPP\Sipdevices;

	// An unset param defaults to false
	$all = $req->getQueryParam('all');

	return $resp->withJson($sip->getAll($all));
});

/*********************
 *  Customer Section *
 *********************/

/*
 * Get all SIP Devices for a customer
 * 
 * @param all (bool) If set, returns all devices, not just active ones
 *
 * @returns json
 */
$api->get("/v1/customer/devices", function($req, $resp) {
	$custid = $req->getQueryParam('customer');
	$cust = new ASTPP\Customer;

	// This will throw an exception if the customer doesn't exist or fails
	// validation somehow, we don't use the result.
	$details = $cust->getById($custid);

	// An unset param defaults to false
	$showall = $req->getQueryParam('all');

	// Now just return this customer's sip devices
	$sip = new ASTPP\Sipdevices;
	return $resp->withJson($sip->getAllByCustomer($custid, $showall));
});

$api->get("/v1/customer/createdevice", function($req, $resp) {
	$custid = $req->getQueryParam('customer');
	$cust = new ASTPP\Customer;

	// This will throw an exception if the customer doesn't exist or fails
	// validation somehow, we don't use the result.
	$details = $cust->getById($custid);

	$sip = new ASTPP\Sipdevices;

	$result = $sip->createSipDevice($custid);
	return $resp->withJson($result);

});

$api->get("/v1/recentcalls/{acctname}", function($req, $resp) {
	$acct = $req->getAttribute('acctname');
	$calls = new ASTPP\RecentCalls;
	return $resp->withJson($calls->getRecent($acct));
});

$api->get("/v1/registrations/{acctname}", function($req, $resp) {
	$acct = $req->getAttribute('acctname');
	$calls = new ASTPP\Registrations;
	return $resp->withJson($calls->getRegs($acct));
});

$api->get("/v1/currentcalls/{acctname}", function($req, $resp) {
	$acct = $req->getAttribute('acctname');
	$calls = new ASTPP\Activecalls;
	return $resp->withJson($calls->getCalls($acct));
});

$api->get("/v1/balance/{acctname}", function($req, $resp) {
	$acct = $req->getAttribute('acctname');
	$bal = new ASTPP\Balance;
	return $resp->withJson($bal->getBalance($acct));
});



$api->run();


