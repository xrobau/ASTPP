---
--- If you are using a replicated database like Percona, every table
--- needs to have a primary key. By default, freeswitch creates its
--- tables as if it was being used by SQLite (which is a fair and
--- reasonable assumption to make).  But that makes things messy when
--- you're trying to set up a fault tolerant cluster.  So this adds
--- unique keys to every table that is used, and alters the 'complete'
--- table so it has a unique key.
---


alter table freeswitch.calls add idxid bigint auto_increment primary key;
alter table freeswitch.tasks add idxid bigint auto_increment primary key;
alter table freeswitch.registrations add idxid bigint auto_increment primary key;
alter table freeswitch.channels add idxid bigint auto_increment primary key;
alter table freeswitch.interfaces add idxid bigint auto_increment primary key;
alter table freeswitch.recovery add idxid bigint auto_increment primary key;
alter table freeswitch.nat add idxid bigint auto_increment primary key;

alter table freeswitch.complete add primary key (a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,hostname);



