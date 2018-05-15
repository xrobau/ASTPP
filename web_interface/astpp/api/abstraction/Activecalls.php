<?php

namespace ASTPP;

class Activecalls extends Base {

	public function getCalls($accountnum) {
		$sql = "select * from freeswitch.channels where accountcode=?";
		$p = $this->db->prepare($sql);
		$p->execute([$accountnum]);
		return $p->fetchAll(\PDO::FETCH_ASSOC);
	}
}

