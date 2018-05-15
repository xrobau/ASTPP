<?php

namespace ASTPP;

class RecentCalls extends Base {

	public function getRecent($accountnum, $limit = 50) {
		$sql = "select cdrs.type,callstart,callerid,callednum,billseconds,callerip,notes,debit,disposition from cdrs, accounts where accounts.id=cdrs.accountid and accounts.number=? order by callstart desc limit $limit";
		$p = $this->db->prepare($sql);
		$p->execute([$accountnum]);
		return $p->fetchAll(\PDO::FETCH_ASSOC);
	}
}

