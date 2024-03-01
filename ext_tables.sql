#
# Table structure for table 'tx_ximaapiclient_reusablerequest'
#
CREATE TABLE tx_ximaapiclient_reusablerequest
(
	uid                 int(11) NOT NULL auto_increment,
	created             int(11) unsigned DEFAULT '0' NOT NULL,
	updated             int(11) unsigned DEFAULT '0' NOT NULL,
	name                varchar(255) DEFAULT '',
	description         text         DEFAULT '',
	clientAlias         varchar(255) DEFAULT '',
	endpoint            varchar(255) DEFAULT '',
	operationId         varchar(255) DEFAULT '',
	method              varchar(255) DEFAULT '',
	preparedEndpoint    text         DEFAULT '',
	acceptHeader        varchar(255) DEFAULT '',
	tag                 varchar(255) DEFAULT '',
	parameters          longtext     DEFAULT '',
	cacheLifetime       varchar(16)  DEFAULT '',
	cacheLifetimePeriod varchar(16)  DEFAULT '',
	registeredTemplates text         DEFAULT '',

	PRIMARY KEY (uid)
);

create table tt_content
(
	tx_ximaapiclient_request                   int(11) unsigned DEFAULT '0' NOT NULL,
	tx_ximaapiclient_template                  varchar(255) DEFAULT '',
	tx_ximaapiclient_detail_pid                int(11) unsigned DEFAULT '0' NOT NULL,
	tx_ximaapiclient_pagination_active         tinyint(4) unsigned default 0 not null,
	tx_ximaapiclient_pagination_page_parameter varchar(64)  DEFAULT '',
	tx_ximaapiclient_parameter_overrides       int(11) unsigned default '0' not null,

);

create table tx_ximaapiclient_parameter_override
(
	foreign_uid           int(11) default '0' not null,
	foreign_table         varchar(255) default '' not null,
	record_type           varchar(255) default '' not null,
	sorting               int(11) unsigned default '0' not null,

	parameter             varchar(64) default '' not null,
	allowGetOverride      tinyint(4) unsigned default 0 not null,
	overrideByStaticValue tinyint(4) unsigned default 0 not null,
	staticOverrideValue   varchar(255) default '' not null,
);
