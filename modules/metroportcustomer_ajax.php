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

use LMSMetroportPlugin\ApiInit;
use LMSMetroportPlugin\GlobalMetroport;

// Check if the user has access to edit and add customers.
if (ConfigHelper::checkPrivilege('metroportcustomerupdate_ajax')) {
    //Sprawdza czy klient jest 
    if (isset($_POST['customer_id'], $_POST['check_customer_in_mms']) &&  $_POST['check_customer_in_mms'] === 'true' ) 
    {
	$apiInit = LMSMetroportPlugin::getApiInitInstance();
	if (!$apiInit) {
	    throw new \Exception('Nie udało się pobrać ApiInit.');
	}
	$MetroportGlobal = new GlobalMetroport($apiInit);
	if (!$MetroportGlobal) {
	    throw new \Exception('Nie udało się pobrać GlobalMetroport.');
	} 

	$CustomerIdMMS= $MetroportGlobal->CheckMetroportCustomerExistInLMS($_POST['customer_id']);

	if ($CustomerIdMMS)
	{
		print json_encode(['success' => 'true', ]);
	} else{
	    print json_encode(['success' => 'false', ]);
	}
	exit();
    }
}
else {
    print json_encode(['success' => 'false', ]);
}