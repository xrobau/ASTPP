<?php
// ##############################################################################
// ASTPP - Open Source VoIP Billing Solution
//
// Copyright (C) 2016 iNextrix Technologies Pvt. Ltd.
// Samir Doshi <samir.doshi@inextrix.com>
// ASTPP Version 3.0 and above
// License https://www.gnu.org/licenses/agpl-3.0.html
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// ##############################################################################
class pricing_model extends CI_Model {
	function pricing_model() {
		parent::__construct ();
	}
	function getpricing_list($flag, $start = 0, $limit = 0) {
		$this->db_model->build_search ( 'price_list_search' );
		if ($this->session->userdata ( 'logintype' ) == 1 || $this->session->userdata ( 'logintype' ) == 5) {
			$account_data = $this->session->userdata ( "accountinfo" );
			$reseller = $account_data ['id'];
			// $where = array("reseller_id" => $reseller, "status" => "0");
			$where = array (
					"reseller_id" => $reseller,
					"status != " => "2" 
			);
		} else {
			$where = array (
					"reseller_id" => "0",
					"status != " => "2" 
			);
		}
		if ($flag) {
			$query = $this->db_model->Select ( "*", "pricelists", $where, "id", "ASC", $limit, $start );
		} else {
			$query = $this->db_model->countQuery ( "*", "pricelists", $where );
		}
		return $query;
	}
	function add_price($add_array) {
		unset ( $add_array ["action"] );
		unset ( $add_array ["id"] );
		/*
		 * ASTPP 3.0 Add Rategroup add creation date.
		 */
		$add_array ['creation_date'] = gmdate ( "Y-m-d H:i:s" );
		/**
		 * *********************************
		 */
		if ($this->session->userdata ( 'logintype' ) == 1 || $this->session->userdata ( 'logintype' ) == 5) {
			$account_data = $this->session->userdata ( "accountinfo" );
			$add_array ["reseller_id"] = $account_data ['id'];
		} else {
			$add_array ["reseller_id"] = "0";
		}
		$this->db->insert ( "pricelists", $add_array );
		
		return $this->db->insert_id ();
	}
	function edit_price($data, $id) {
		unset ( $data ["action"] );
		/*
		 * ASTPP 3.0 Edit Rategroup time update last mofied date.
		 */
		$data ['last_modified_date'] = gmdate ( "Y-m-d H:i:s" );
		/**
		 * ******************************************************
		 */
		$this->db->where ( "id", $id );
		$this->db->update ( "pricelists", $data );
		return true;
	}
	function get_price_list_for_cdrs() {
		if ($this->session->userdata ( 'username' ) != "" && $this->session->userdata ( 'logintype' ) != 2) {
			$this->db->where ( 'reseller', $this->session->userdata ( 'username' ) );
		} else {
			$this->db->where ( array (
					'reseller' => "0" 
			) );
		}
		$this->db->where ( 'status <', 2 );
		$this->db->order_by ( 'name', 'desc' );
		$query = $this->db->get ( "pricelists" );
		$price_list = array ();
		$result = $query->result_array ();
		foreach ( $result as $row ) {
			$price_list [$row ['name']] = $row ['name'];
		}
		return $price_list;
	}
}
