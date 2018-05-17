<?php

namespace ASTPP;

class Balance extends Base {

	public function getBalance($accountnum) {
		$sql = "select number,balance from accounts where number=?";
		$p = $this->db->prepare($sql);
		$p->execute([$accountnum]);
		return $p->fetchAll(\PDO::FETCH_ASSOC);
	}
}

