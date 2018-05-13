<?php

namespace API;

/*
 * Get astpp-config
 */

class Config {
	private static $current = false;
	private static $conffile = "/var/lib/astpp/astpp-config.conf";

	public static function get() {
		if (is_array(self::$current)) {
			return self::$current;
		}

		self::$current = @parse_ini_file(self::$conffile, true);
		if (empty(self::$current)) {
			throw new \Exception("Can't read ".self::$conffile);
		}

		return self::$current;
	}
}

