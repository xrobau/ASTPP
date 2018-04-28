<?php

namespace ASTPP;

class Customers extends Base {

	public function getAll() {
		// Taken from web_interface/astpp/application/modules/accounts/models/accounts_model.php
		// function get_customer_Account_list($flag, $start = 0, $limit = 0, $export = false)
		//
		return $this->db->query("SELECT * FROM `accounts` WHERE `deleted` = '0' AND `reseller_id` = 0 AND `type` IN (0,3) ORDER BY `number` desc")->fetchAll();
	}
}

