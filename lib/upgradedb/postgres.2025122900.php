<?php

/*
 * METROPORT LMS Plugin
 *
 *  (C) Copyright 2024 VIPHOST
 *
 *  Developed by VIPHOST for LMS integration with METROPORT.
 *  For more information about authors, please contact: biuro@viphost.it
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

$this->BeginTrans();

$this->Execute("
	CREATE TABLE IF NOT EXISTS metroport_metrotv_packages (
		id BIGSERIAL PRIMARY KEY UNIQUE NOT NULL,
		firmid BIGINT NOT NULL,
		iptv_program VARCHAR(255),
		active SMALLINT DEFAULT 0,
		sort BIGINT DEFAULT 0,
		creationdate BIGINT DEFAULT 0,
		moddate BIGINT DEFAULT 0,
		deletedate BIGINT DEFAULT 0,
		http_export SMALLINT DEFAULT 0,
		provider VARCHAR(255),
		ext_pkg_id VARCHAR(255),
		tariffcode VARCHAR(255),
		pkg_name VARCHAR(255),
		pkg_desc TEXT,
		pkg_tvpanel_desc TEXT,
		pkg_autoinclude TEXT,
		pkg_denied TEXT,
		pkg_valid_from DATE,
		pkg_valid_to DATE,
		self_service SMALLINT DEFAULT 0,
		npvr_quota BIGINT DEFAULT 0,
		timeshift_allow SMALLINT DEFAULT 0,
		ext_device_allow BIGINT DEFAULT 0,
		tvonline_maxsessions BIGINT DEFAULT 0,
		pkg_group BIGINT DEFAULT 0,
		iptv_ozz SMALLINT DEFAULT 0,
		firmName VARCHAR(255),
		contracttime BIGINT DEFAULT 0,
		pricenetto NUMERIC(10,2) DEFAULT 0.00,
		vat BIGINT DEFAULT 0,
		pricebrutto NUMERIC(10,2) DEFAULT 0.00,
		pricenetto_provider NUMERIC(10,2) DEFAULT 0.00,
		vat_provider BIGINT DEFAULT 0,
		pricebrutto_provider NUMERIC(10,2) DEFAULT 0.00,
		promotiontime BIGINT DEFAULT 0,
		promotion_pricenetto_provider NUMERIC(10,2) DEFAULT 0.00,
		promotion_pricebrutto_provider NUMERIC(10,2) DEFAULT 0.00,
		countActive BIGINT DEFAULT 0
	)
");

$this->Execute("CREATE INDEX IF NOT EXISTS metroport_metrotv_packages_firmid ON metroport_metrotv_packages (firmid)");
$this->Execute("CREATE INDEX IF NOT EXISTS metroport_metrotv_packages_active ON metroport_metrotv_packages (active)");
$this->Execute("CREATE INDEX IF NOT EXISTS metroport_metrotv_packages_provider ON metroport_metrotv_packages (provider)");

$this->Execute("
	CREATE TABLE IF NOT EXISTS metroport_fsecure_packages (
		id BIGSERIAL PRIMARY KEY UNIQUE NOT NULL,
		ext_id VARCHAR(255),
		name VARCHAR(255) NOT NULL,
		description TEXT,
		license_size BIGINT DEFAULT 0
	)
");

$this->Execute("CREATE INDEX IF NOT EXISTS metroport_fsecure_packages_ext_id ON metroport_fsecure_packages (ext_id)");
$this->Execute("CREATE INDEX IF NOT EXISTS metroport_fsecure_packages_license_size ON metroport_fsecure_packages (license_size)");

$this->Execute("
	CREATE TABLE IF NOT EXISTS metroport_metrotv_stb (
		id BIGSERIAL PRIMARY KEY UNIQUE NOT NULL,
		firmid BIGINT DEFAULT 0,
		iptv_program VARCHAR(255),
		iptv_portalid BIGINT DEFAULT 0,
		status BIGINT DEFAULT 0,
		userid BIGINT DEFAULT 0,
		contractid BIGINT DEFAULT 0,
		actualtariffid BIGINT DEFAULT 0,
		networkid BIGINT DEFAULT 0,
		ext_linked BIGINT DEFAULT 0,
		mac VARCHAR(255) UNIQUE NOT NULL,
		ipaddr VARCHAR(255),
		serialnumber VARCHAR(255),
		vcasid VARCHAR(255),
		customer_sn VARCHAR(255),
		hdcp_ksv VARCHAR(255),
		modelid BIGINT DEFAULT 0,
		devel SMALLINT DEFAULT 0,
		servicetype VARCHAR(255),
		servicetype_force SMALLINT DEFAULT 0,
		must_reboot SMALLINT DEFAULT 0,
		description TEXT,
		activedate BIGINT DEFAULT 0,
		error_log BIGINT DEFAULT 0,
		fwlog BIGINT DEFAULT 0,
		boot_options TEXT,
		creationdate BIGINT DEFAULT 0,
		creatorid BIGINT DEFAULT 0,
		moddate BIGINT DEFAULT 0,
		modid BIGINT DEFAULT 0,
		nodeid BIGINT DEFAULT 0,
		private SMALLINT DEFAULT 0,
		active SMALLINT DEFAULT 0,
		AddressIp VARCHAR(255),
		ModelName VARCHAR(255),
		iptv_accountid BIGINT DEFAULT 0,
		order_id VARCHAR(255),
		firmname VARCHAR(255),
		UserName VARCHAR(255),
		usertype VARCHAR(255),
		StatusName VARCHAR(255),
		channels TEXT,
		iptv_portal VARCHAR(255)
	)
");

$this->Execute("CREATE INDEX IF NOT EXISTS metroport_metrotv_stb_firmid ON metroport_metrotv_stb (firmid)");
$this->Execute("CREATE INDEX IF NOT EXISTS metroport_metrotv_stb_mac ON metroport_metrotv_stb (mac)");
$this->Execute("CREATE INDEX IF NOT EXISTS metroport_metrotv_stb_serialnumber ON metroport_metrotv_stb (serialnumber)");
$this->Execute("CREATE INDEX IF NOT EXISTS metroport_metrotv_stb_status ON metroport_metrotv_stb (status)");
$this->Execute("CREATE INDEX IF NOT EXISTS metroport_metrotv_stb_userid ON metroport_metrotv_stb (userid)");
$this->Execute("CREATE INDEX IF NOT EXISTS metroport_metrotv_stb_iptv_accountid ON metroport_metrotv_stb (iptv_accountid)");
$this->Execute("CREATE INDEX IF NOT EXISTS metroport_metrotv_stb_active ON metroport_metrotv_stb (active)");

// Unikalność numeru seryjnego dla poprawnego UPSERT
$this->Execute("ALTER TABLE metroport_metrotv_stb ADD CONSTRAINT IF NOT EXISTS metroport_metrotv_stb_serial_unique UNIQUE (serialnumber)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2025122900', 'dbversion_LMSMetroportPlugin'));

$this->CommitTrans();
