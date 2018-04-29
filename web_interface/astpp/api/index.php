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

/**
 * Get all customers.
 *
 * @returns json
 */
$api->get("/v1/customers", function($req, $resp) {
	$cust = new ASTPP\Customers;
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

$api->run();


