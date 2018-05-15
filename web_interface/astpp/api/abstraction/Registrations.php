<?php

namespace ASTPP;

class Registrations extends Base {

	public function getRegs($accountnum, $limit = 50) {
		$sql = "select d.username,r.reg_user,r.hostname as sipserver,r.expires,r.url from astpp.sip_devices d left join freeswitch.registrations r on d.username=r.reg_user, astpp.accounts a where d.accountid=a.id and a.number=?";
		$p = $this->db->prepare($sql);
		$p->execute([$accountnum]);
		return $p->fetchAll(\PDO::FETCH_ASSOC);
	}
}

