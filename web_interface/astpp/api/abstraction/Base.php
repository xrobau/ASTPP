<?php

namespace ASTPP;

class Base {

	// Static instance is recycled with everything that implements Base
	public static $db_static;

	// This is just a reference to it.
	protected $db;

	public function __construct() {
		if (!self::$db_static) {
			self::$db_static = $this->connectDB();
		}
		$this->db = self::$db_static;
	}

	private function connectDB() {
		$conf = \API\Config::get();
		// Build our DSN

		// If you're using MySQL 5.6 or higher, you should be using 'charset=utf8mb4'.
		$dsn = "mysql:dbname=".$conf['dbname'].";host=".$conf['dbhost'].";charset=utf8";

		$pdo = new \PDO($dsn, $conf['dbuser'], $conf['dbpass'], array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION));
		return $pdo;
	}

}
