<?php

namespace ASTPP;

class Sipprofiles extends Base {

	public function getDefaultProfile() {
		$res = $this->db->query("select id,name from sip_profiles where name='default'")->fetchAll();
		if (isset($res[0])) {
			return $res[0]['id'];
		}

		// Nothing called 'default'?  Crash.
		throw new \Exception("Unable to find sip profile called 'default'");
	}
}
