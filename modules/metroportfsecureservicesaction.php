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

if(ConfigHelper::checkPrivilege('metroportcustomerupdate_ajax')==false)
{
    die(json_encode([
		'status'         => 'error',
		'success'        => 'false',                
		'message'        => 'Brak uprawnień do działań na usługach Fsecure.',
		'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> Brak uprawnień do działań na usługach Fsecure.</div>',
		'lmscustomerid'  => $customerId,
		'api_result'     => $apiResult
	    ]));
}

try {
    $apiInit = LMSMetroportPlugin::getApiInitInstance();
    if (!$apiInit) {
        throw new \Exception('Nie udało się pobrać ApiInit.');
    }
    $MetroportGlobal = new GlobalMetroport($apiInit);
    if (!$MetroportGlobal) {
        throw new \Exception('Nie udało się pobrać GlobalMetroport.');
    }   
    $fsecure = new Fsecure($apiInit);
    if (!$fsecure) {
        throw new \Exception('Nie udało się pobrać fsecure.');
    }
    
    if (!empty($_GET['DeleteFsecureService']) && $_GET['DeleteFsecureService'] == true)
    {      
        $fsecure->DeleteCustomerFsecureService($_GET['fsecure_service_id']);
        $SESSION->redirect('?m=customerinfo&id='.$_GET['customer_id'] );      
        exit();
    }

    if (!empty($_GET['suspend']) && $_GET['suspend'] == true)
    {   
        $fsecure->SuspendCustomerFsecureService($_GET['fsecure_service_id']);
        $SESSION->redirect('?m=customerinfo&id='.$_GET['customer_id'] );      
        exit();
    }
    if (!empty($_GET['unsuspend']) && $_GET['unsuspend'] == true)
    {
        $fsecure->UnSuspendCustomerFsecureService($_GET['fsecure_service_id']);
        $SESSION->redirect('?m=customerinfo&id='.$_GET['customer_id'] );      
        exit();
    }

    if (!empty($_POST['edit_service_fsecure_for_customer']) && $_POST['edit_service_fsecure_for_customer'] == true) {
        $CustomerIdMMS= $MetroportGlobal->CheckMetroportCustomerExistInLMS($_POST['customer_id']);
        $customerMMS= $MetroportGlobal->GetCustomerMmsByID($CustomerIdMMS['metroport_user_id'])['data']['id'];
        $data = [
                'userid'          => $customerMMS,
                'secur_serviceid' => $_POST['productId'],
                'license_size'    => $_POST['license_size'],
                ];
        $ReasonEditServiceFsecureToClient=$apiInit->ApiPut("/services/security/".$_POST['fsecure_service_id'],$data);
        if (!empty($ReasonEditServiceFsecureToClient['success']) && !empty($ReasonEditServiceFsecureToClient['data'])) {
            
            print json_encode([
                'status'         => 'ok',
                'success'        => 'true',
                'message'        => 'Licencja została zmieniona.',
                'message_html'   => '<div style="background-color:#d4edda;color:#155724;padding:10px 15px;border:1px solid #c3e6cb;border-radius:4px;font-size:14px;"><strong>✅ Licenca F-SECURE została zmieniona.</strong></div>',
                'lmscustomerid'  => $customerId,
                'api_result'     => $apiResult
            ]);
        } else {
            // Error - display body
            $errorBody = $ReasonEditServiceFsecureToClient['body'] ?? $ReasonEditServiceFsecureToClient['error'] ?? 'Unknown error';
            print json_encode([
                'status'         => 'error',
                'success'        => false,                
                'message'        => 'Bład edycji klienta.',
                'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> ' . $errorBody . '</div>',
                'lmscustomerid'  => $customerId,
                'api_result'     => $apiResult
            ]);
        }

    }

    if (!empty($_POST['new_service_fsecure_for_customer']) && $_POST['new_service_fsecure_for_customer'] == true) {
        $CustomerIdMMS= $MetroportGlobal->CheckMetroportCustomerExistInLMS($_POST['customer_id']);
        $customerMMS= $MetroportGlobal->GetCustomerMmsByID($CustomerIdMMS['metroport_user_id'])['data']['id'];
        $data = [
                'userid'          => $customerMMS,
                'secur_serviceid' => $_POST['productId'],
                'license_size'    => $_POST['license_size'],
                ];
	
	$assignment = [
	    'customerid' => $_POST['customer_id'],
	    'servicetype'            => '0',
	    'tarifftype'             => '-1',
	    'tariffid'               => $_POST['tariffid'] ?? '',
	    'count'                  => $_POST['count'] ?? 1,
	    'period'                 => $_POST['period'] ?? '3',
	    'at'                     => $_POST['at'] ?? '1',
	    'datefrom'		     => !empty($_POST['datefrom']) ? strtotime($_POST['datefrom']) : 0,
	    'dateto'		     => !empty($_POST['dateto']) ? strtotime($_POST['dateto']) : '0',
	    'netvalue'		     => $_POST['netvalue'],
	    'value'		     => $_POST['value'],
	    'currency'               => $_POST['currency'] ?? 'PLN',
	    'discount_type'          => $_POST['discount_type'] ?? 1,
	    'type'                   => $_POST['type'] ?? -1,
	    'taxid'                  => $_POST['taxid'] ?? 1,
	    'paytype'                => $_POST['paytype'] ?? '',
	    'settlement'             => $_POST['settlement'] ?? 0,
	    'align-periods'          => $_POST['align-periods'] ?? 1,
	    'pdiscount'		     => '0',
	    'vdiscount'		     => '0',
	    'taxvalue' => $DB->GetOne('SELECT value FROM taxes WHERE id = ?', array($_POST['taxid'])),
	    'existing_assignments'   => $_POST['existing_assignments'] ?? ['operation' => 0],
	];

        $ReasonAddNewServiceFsecureToClient=$apiInit->ApiPost("/services/security",$data);
        if (!empty($ReasonAddNewServiceFsecureToClient['success']) && !empty($ReasonAddNewServiceFsecureToClient['data'])) {
	        if ($_POST['addtarrif']==1) { $tariffid = $LMS->AddAssignment($assignment); }
            print json_encode([
                'status'         => 'ok',
                'success'        => 'true',
                'message'        => 'Licencja została dodana.',
                'message_html'   => '<div style="background-color:#d4edda;color:#155724;padding:10px 15px;border:1px solid #c3e6cb;border-radius:4px;font-size:14px;"><strong>✅ Licenca F-SECURE została dodan do klienta.</strong></div>',
                'lmscustomerid'  => $customerId,
                'api_result'     => $apiResult
            ]);

        } else {
            $errorBody = $ReasonAddNewServiceFsecureToClient['body'] ?? $ReasonAddNewServiceFsecureToClient['error'] ?? 'Nieznany błąd';
            print json_encode([
                'status'         => 'error',
                'success'        => false,                
                'message'        => 'Bład.',
                'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> ' . $errorBody . '</div>',
                'lmscustomerid'  => $customerId,
                'api_result'     => $apiResult
            ]);
        }
        
        exit();
    }

} catch (\Exception $e) {
    if (!empty($_POST['new_client']) && $_POST['new_client'] == true) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Błąd: ' . $e->getMessage()
        ]);
        exit;
    }
    error_log("[MetroportGlobalModule] " . $e->getMessage());

}