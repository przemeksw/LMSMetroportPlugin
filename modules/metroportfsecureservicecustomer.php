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
use LMSMetroportPlugin\Fsecure;
global $SESSION;

try {
    // Próba pobrania instancji API
    $apiInit = LMSMetroportPlugin::getApiInitInstance();
    if (!$apiInit) {
        throw new \Exception('Nie udało się pobrać ApiInit.');
    }
    $MetroportGlobal = new GlobalMetroport($apiInit);
    if (!$MetroportGlobal) {
        throw new \Exception('Nie udało się pobrać GlobalMetroport.');
    }   
    // Tworzymy instancję Fsecure
    $fsecure = new Fsecure($apiInit);
    if (!$fsecure) {
        throw new \Exception('Nie udało się pobrać fsecure.');
    }

    if (!empty($_POST['get_fsecure_customer_services_list']) && $_POST['get_fsecure_customer_services_list']== true)
    {
	$MetroportGlobal = new GlobalMetroport($apiInit);
	$MetroportCustomerId = $MetroportGlobal->CheckMetroportCustomerExistInLMS($_POST['lmscustomerid']);
	 
	if($MetroportCustomerId)
	{
	    $result=$fsecure->GetCustomerFsecureServicesByIdMms($MetroportCustomerId['metroport_user_id']);
	    if (isset($result['data']) && is_array($result['data'])) 
	    {
		print json_encode($result);
	    }
	} else {
	    $result['CustomerExistInMetroport'] = false;
	    $result['success'] = true;
	    $result['data'] = array();
	    print json_encode($result);
	}
	exit();
    }

    if (!empty($_POST['Get_Fsecure_Services_List']) && $_POST['Get_Fsecure_Services_List'] == true) {
        $CustomerIdMMS= $MetroportGlobal->CheckMetroportCustomerExistInLMS($_POST['customer_id']);
        $customerMMS= $MetroportGlobal->GetCustomerMmsByID($CustomerIdMMS['metroport_user_id'])['data'];
        print json_encode(['fsecure'=>$fsecure->GetFsecureServicesList(),'customer'  => $customerMMS]);
        exit();
    }
    
    if (!empty($_POST['Get_tarfiffs']) && $_POST['Get_tarfiffs'] == true) {
	    print_r(json_encode($LMS->GetTariffs()));
    }
    // Obsługa GET (strona / Smarty)
    if (!empty($_GET['m'])) {
        if ($_GET['m'] == 'customerinfo') {
            $customer_id = $_GET['id'];
        } elseif ($_GET['m'] == 'nodeinfo') {
            $customer_id = $LMS->GetNode($_GET['id'])['ownerid'];
        } else {
            $customer_id = null;
        }
    }

    if (!empty($customer_id)) {
	$SMARTY->assign('LmsCustomerId', $customer_id ?: false);        
    }

} catch (\Exception $e) {
    if (!empty($_POST['new_client']) && $_POST['new_client'] == true) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Błąd: ' . $e->getMessage()
        ]);
        exit;
    }
    // Jeśli GET / Smarty, logujemy błąd i przypisujemy zmienną do Smarty
    error_log("[MetroportGlobalModule] " . $e->getMessage());
}

if (!empty($customer_id)) {
    $SMARTY->assign('lmscustomerid', $customer_id);
}
