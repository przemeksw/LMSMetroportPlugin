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

$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled, type, userid, configid, divisionid) VALUES ('metroport', 'show_fsecure_in_customer_tab', '1', 'Pokaż na karcie klienta zakładkę F-Secure (1 - tak, 0 - nie)', 0, 7, NULL, NULL, NULL)");
$this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled, type, userid, configid, divisionid) VALUES ('metroport', 'show_metrotv_in_customer_tab', '1', 'Pokaż na karcie klienta zakładkę MetroTV (1 - tak, 0 - nie)', 0, 7, NULL, NULL, NULL)");
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026032302', 'dbversion_LMSMetroportPlugin'));

$this->CommitTrans();
