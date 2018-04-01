drop TABLE subscriber;
CREATE TABLE IF NOT EXISTS `subscriber` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` char(64) NOT NULL DEFAULT '',
  `domain` char(64) NOT NULL DEFAULT '',
  `password` char(25) NOT NULL DEFAULT '',
  `email_address` char(64) NOT NULL DEFAULT '',
  `ha1` char(64) NOT NULL DEFAULT '',
  `ha1b` char(64) NOT NULL DEFAULT '',
  `rpid` char(64) DEFAULT NULL,
  `accountcode` varchar(20) NOT NULL,
  `pricelist_id` int(11) NOT NULL DEFAULT '0',
  `channel_limit` int(5) DEFAULT NULL,
  `effective_caller_id_name` varchar(50) NOT NULL,
  `effective_caller_id_number` varchar(50) NOT NULL,
  `creation_date` datetime NOT NULL DEFAULT '1980-01-01 00:00:00',
  `last_modified_date` datetime NOT NULL DEFAULT '1980-01-01 00:00:00',
  `reseller_id` int(4) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_idx` (`username`,`domain`),
  KEY `username_idx` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=ascii AUTO_INCREMENT=6 ;