<?php

namespace ASTPP;

class Dids extends Base {

	public function getAll() {
		// Based on web_interface/astpp/application/modules/did/models/did_model.php
		// function getdid_list
		//
		$sql = "select 
				d.*,
				cc.country as countryname,
				a.first_name,
				a.last_name 
			from 
				dids as d,
				countrycode as cc,
				accounts as a
			where
				d.country_id = cc.id and d.provider_id=a.id";

		return $this->db->query($sql)->fetchAll();
	}
}

