<?php

namespace ASTPP;

class Sipdevices extends Base {

	public function getAll($activeonly = true) {
		$sql = "select 
				s.id, s.username, s.sip_profile_id, s.accountid, s.reseller_id, s.status,
				a.first_name, a.last_name,
				p.name as profile_name
			from 
				sip_devices as s,
				accounts as a,
				sip_profiles as p
			where
			s.accountid=a.id and s.sip_profile_id=p.id";

		if ($activeonly) {
			$sql .= " and s.status=0";
		}

		return $this->db->query($sql)->fetchAll();
	}

	public function getAllByCustomer($cid = false, $showall = false) {
		$sql = "select username as idx, id, username, dir_params, dir_vars from sip_devices where accountid=?";
		if (!$showall) {
			$sql .= " and status=0";
		}
		$p = $this->db->prepare($sql);
		$p->execute([$cid]);

		// Whenever I use PDO::FETCH_UNIQUE, it really annoys me that
		// it's so poorly documented.
		$result = $p->fetchAll(\PDO::FETCH_UNIQUE|\PDO::FETCH_ASSOC);

		// Now go and un-json everything in the result.
		//
		// Note this is written with php7 compatibility in mind, where you can't edit
		// the RHS of a k => v assignment, as foreach iterates over the COPY of an
		// array.
		foreach (array_keys($result) as $username) {
			$result[$username]['dir_params'] = @json_decode($result[$username]['dir_params'], true);
			$result[$username]['dir_vars'] = @json_decode($result[$username]['dir_vars'], true);

			// Make sure we never accidentally leak secrets
			unset($result[$username]['dir_params']['password']);
			unset($result[$username]['dir_params']['vm-password']);
		}

		return $result;
	}
}

