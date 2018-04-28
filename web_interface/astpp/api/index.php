<?php
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


$api->run();

print "off\n";


