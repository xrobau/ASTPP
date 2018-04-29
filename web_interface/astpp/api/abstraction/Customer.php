<?php

namespace ASTPP;

class Customer extends Base {

	public function getAll() {
		// Taken from web_interface/astpp/application/modules/accounts/models/accounts_model.php
		// function get_customer_Account_list($flag, $start = 0, $limit = 0, $export = false)
		//
		return $this->db->query("SELECT * FROM `accounts` WHERE `deleted` = '0' AND `reseller_id` = 0 AND `type` IN (0,3) ORDER BY `number` desc")->fetchAll();
	}

	public function getById($custid = false) {
		if (!$custid || !is_numeric($custid)) {
			throw new \Exception("Invalid customer ID");
		}

		$p = $this->db->prepare("SELECT * FROM `accounts` WHERE `id`=?");
		$p->execute([$custid]);
		$result = $p->fetchAll(\PDO::FETCH_ASSOC);
		if (!isset($result[0])) {
			throw new \Exception("Unknown customer ID '$custid'");
		}
		return $result[0];
	}

}

