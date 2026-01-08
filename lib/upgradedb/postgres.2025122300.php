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
	ALTER TABLE metroport_customers ADD COLUMN IF NOT EXISTS metrotv_suspend smallint DEFAULT 0 NOT NULL;
	ALTER TABLE metroport_customers ADD COLUMN IF NOT EXISTS metrotv_suspend_billing smallint DEFAULT 0 NOT NULL;
	ALTER TABLE metroport_customers ADD COLUMN IF NOT EXISTS metro_fsecure_suspend smallint DEFAULT 0 NOT NULL;
	ALTER TABLE metroport_customers ADD COLUMN IF NOT EXISTS metro_fsecure_suspend_billing smallint DEFAULT 0 NOT NULL;
    INSERT INTO uiconfig (section, var, value, description, disabled, type, userid, configid, divisionid) VALUES ('metroport', 'metrotv_suspend_billing', '1', 'Zawieś wystawianie faktur dla zablokowanych kont MetroTV', 0, 7, NULL, NULL, NULL);
    INSERT INTO uiconfig (section, var, value, description, disabled, type, userid, configid, divisionid) VALUES ('metroport', 'fsecure_suspend_billing', '1', 'Zawieś wystawianie faktur dla zablokowanych kont Fsecure', 0, 7, NULL, NULL, NULL);
    ");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2025122300', 'dbversion_LMSMetroportPlugin'));

$this->CommitTrans();
