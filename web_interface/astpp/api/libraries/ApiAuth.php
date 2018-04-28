<?php

namespace API;

/*
 * Middleware for SLIM to authenticate API calls.
 *
 * Currently only checks against IP addresses in astpp.conf
 */

class ApiAuth {
	public function __invoke($request, $response, $next) {
		$conf = Config::get();

		// Add this to the config file to enable API Access
		//
		// apihosts = ip.add.re.ss,other.ip.addr.ess, etc
		if (empty($conf['apihosts'])) {
			throw new \Exception("No API hosts defined");
		}

		// Seperate them by commas. 
		$tmphosts = explode(',', $conf['apihosts']);
		$validhosts = array();
		foreach ($tmphosts as $tmpip) {
			$ip = filter_var(trim($tmpip), FILTER_VALIDATE_IP);
			if (!$ip) {
				throw new \Exception("Invalid IP '$tmpip' in config");
			}
			$validhosts[$ip] = true;
		}

		// This is where the TCP connection came from.
		$remote = $_SERVER['REMOTE_ADDR'];

		// If you want to allow X-Forwarded-For (eg, if you're using haproxy), enable
		// it in the config
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			if (empty($conf['use_x_forward'])) {
				throw new \Exception("X-Forwarded-For provided in header, but not enabled. Security issue from $remote!");
			}
			$remote = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		// Is this IP allowed to use the API?
		if (!isset($validhosts[$remote])) {
			throw new \Exception("Host $remote not allowed to use the API");
		}

		$response = $next($request, $response);

		return($response);
	}
}

