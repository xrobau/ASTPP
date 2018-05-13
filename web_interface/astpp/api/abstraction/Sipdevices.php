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
		// the RHS of a k => v assignment, as foreach iterates over the COPY of the 
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

	public function createSipDevice($client) {

		// Generate a unique sip device number
		$maxtries = 50;
		$found = false;
		$p = $this->db->prepare("select id, username from sip_devices where username=?");

		while (!$found) {
			$maxtries--;
			if (!$maxtries) {
				throw new \Exception("Madness. Was unable to generate an unused sip_device accunt??");
			}
			$accountnum = mt_rand(100000000, 999999999);
			$p->execute([$accountnum]);
			$res = $p->fetchAll();
			if (isset($res[0])) {
				// This number already exists. Try again.
				continue;
			}
			// Found it!
			$found = true;
		}

		$sql = "insert into sip_devices (
				username, sip_profile_id, reseller_id, accountid,
				dir_params, dir_vars, status, creation_date,
				last_modified_date, call_waiting
			) values (
				:username, :sip_profile_id, :reseller_id, :accountid,
				:dir_params, :dir_vars, :status, NOW(),
				NOW(), :call_waiting
			)";

		$dir_params = [ 
			"password" => $this->generatePassword(),
			"vm-enabled" => false,
			"vm-password" => "",
			"vm-mailto" => "",
			"vm-attach-file" => false,
			"vm-keep-local-after-email" => false,
			"vm-email-all-messages" => true
		];

		$dir_vars = [
			"effective_caller_id_name" => "Tommy Tutone",
			"effective_caller_id_number" => "8675309",
		];
		
		$params = [
			"username" => $accountnum,
			"sip_profile_id" => $this->getDefaultSipProfile(),
			"reseller_id" => 0,
			"accountid" => $client,
			"dir_params" => json_encode($dir_params),
			"dir_vars" => json_encode($dir_vars),
			"status" => 0,
			"call_waiting" => 0
		];

		$p = $this->db->prepare($sql);
		$p->execute($params);
		return $params;
	}

	private function generatePassword($length = 16) {
		// Generate a $length password
		//
		// We use Base58 to generate our password, as it doesn't
		// have any confusing chars (I/1, 0/O etc).
		//
		// Generate our random stuff to make into a string
		$random = openssl_random_pseudo_bytes($length);

		// This actually generates a string $length * 1.33 chars long.
		$base58 = new \StephenHill\Base58();
		$text = $base58->encode($random);

		// So skip the first 3 (... why not?) and return $length from there.
		return substr($text, 3, $length);
	}

	private function getDefaultSipProfile() {
		$sip = new Sipprofiles;
		return $sip->getDefaultProfile();
	}

}

