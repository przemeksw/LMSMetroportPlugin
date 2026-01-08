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

$CustomerIdLMS=$_POST['customer_id'];
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
    $clients = $fsecure->GetAllCustomerFsecureServices();
    $result = ApiInit::Success($clients, 'Lista klientów pobrana pomyślnie.');

} catch (\Exception $e) {
    $result = ApiInit::Error('Błąd pobierania listy klientów usługi F-SECURE: ' . $e->getMessage());
    error_log("[MetroportGlobalModule] " . $e->getMessage());
}

if(ConfigHelper::checkPrivilege('metroportcustomerupdate_ajax')==false)
{
    die(json_encode([
		'status'         => 'error',
		'success'        => 'false',                
		'message'        => 'Brak uprawnień do działań na kliencie.',
		'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> Brak uprawnień o działąń na kliencie.</div>',
		'lmscustomerid'  => $customerId,
		'api_result'     => $apiResult
	    ]));
}

//Dodawnie nowego klienta do MMS
if (!empty($_POST['new_client']) && $_POST['new_client'] == true) {

    $customerId = $_POST['lmscustomerid'];
    $customer_info=$LMS->GetCustomer($customerId);
    $nip=$MetroportGlobal->formatNip($customer_info['ten']) ?? '';
    $pesel=$customer_info['ssn'] ?? '';
    $typ=$customer_info['type'];
    //type 0=fizyczna 1=firma

    if ($typ=="0")
    {
		if ($pesel!="")
			$ReasonCheckClientInMMS=$MetroportGlobal->CheckMetroportCustomerExistByPesel("$pesel");
		else {
			print json_encode([
			'status'         => 'error',
			'success'        => 'false',                
			'message'        => 'Brak pola PESEL w karcie klienta LMS.',
			'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> Uzupełnij PESEL klienta.</div>',
			'lmscustomerid'  => $customerId,
			'api_result'     => $apiResult
			]);
			exit();
		}
	} else {
		if ($nip!="")
			$ReasonCheckClientInMMS=$apiInit->CheckMetroportCustomerExistByNip("$nip");    
		else {
			echo json_encode([
				'status'         => 'error',
				'success'        => 'false',                
				'message'        => 'Brak pola NIP w karcie klienta LMS.',
				'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> Uzupełnij NIP klienta.</div>',
				'lmscustomerid'  => $customerId,
				'api_result'     => $apiResult
				]);
			exit();
		}
    }

    //If the client exists in the Metroport database, connect to the LMS client
    if ($ReasonCheckClientInMMS["success"]=="1")
    {
		echo json_encode([
			'status'        => 'update',
			'message'       => 'Klient widnieje w bazie MMS - dokonuje parowania',
			'lmscustomerid' => $customerId,
			'message_html' => '<div style="font-size:14px; color:#2c3e50; line-height:1.6;"><ol style="padding-left:20px; margin:10px 0;"><li><b>Klient występuje w bazie MMS</b></li><li><b>Wykonano powiązanie MMS → LMS</b></li></ol></div>',
			'api_result'    => $ReasonCheckClientInMMS['data']['1']['0']
		]);
		$sql_insert_new_profile="INSERT INTO metroport_customers (metroport_user_id,lms_user_id) VALUES (?,?)";
		$sql_insert_new_profile_param=array($ReasonCheckClientInMMS['data']['1']['0']['userid'],$_POST['lmscustomerid']);                
		$res1 = $DB->Execute($sql_insert_new_profile,$sql_insert_new_profile_param);
    } else {
		$firstAddress = reset($customer_info['addresses']); 
		$MmsUserInfoAdd=array('name' => $customer_info['name'],
			'customertype' => ($customer_info['type'] ?? 0) == 1 ? '1' : '2',
			'lastname' => $customer_info['lastname'] ?? '',
			'name' => $customer_info['name'] ?? '',
			'pesel' => $customer_info['ssn'] ?? '',
			'nip' => $customer_info['ten'] ?? '',
			'city1'       => $firstAddress['location_city_name'] ?? '',
			'zip1'        => $firstAddress['location_zip'] ?? '',
			'streetname1' => $firstAddress['location_short_street_name'] ?? '',
			'streetno1'   => $firstAddress['location_house'] ?? '',
			'local1'      => $firstAddress['location_flat'] ?? '',
			'email' => $customer_info['emails']['0']['email']) ?? '';

		$ReasonAddClientToMMS=$apiInit->ApiPost("/Users/users",$MmsUserInfoAdd);
		if (!empty($ReasonAddClientToMMS['success']) && !empty($ReasonAddClientToMMS['data'])) {
			// Poprawnie - zapisujemy id klienta
			$clientId = $ReasonAddClientToMMS['data'];
			$sql_insert_new_profile="INSERT INTO metroport_customers (metroport_user_id,lms_user_id) VALUES (?,?)";
			$sql_insert_new_profile_param=array($clientId,$_POST['lmscustomerid']);                
			$res1 = $DB->Execute($sql_insert_new_profile,$sql_insert_new_profile_param);
			print json_encode([
			'status'         => 'ok',
			'success'        => 'true',
			'message'        => 'Brak klienta w bazie MMS - Dodaje',
			'message_html'   => '<div style="background-color:#d4edda;color:#155724;padding:10px 15px;border:1px solid #c3e6cb;border-radius:4px;font-size:14px;"><strong>✅ Klient został dodany do bazy MMS:</strong> ' . $clientId . '</div>',
			'lmscustomerid'  => $customerId,
			'api_result'     => $apiResult
			]);
		} else {
			// Error - display body
			$errorBody = $ReasonAddClientToMMS['body'] ?? $ReasonAddClientToMMS['error'] ?? 'Nieznany błąd';
			print json_encode([
			'status'         => 'error',
			'success'        => false,                
			'message'        => 'Klient już jest w bazie danych MMS',
			'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> ' . $errorBody . '</div>',
			'lmscustomerid'  => $customerId,
			'api_result'     => $apiResult
			]);
		}
    }
    exit;
}
//Aktaulizacja danych Klienta w MMS
if (isset($_POST['customer_id'], $_POST['customer_update']) &&  $_POST['customer_update'] === 'true' ) 
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
		$customer_info = $LMS->GetCustomer($CustomerIdLMS);
		$addresses = $customer_info['addresses'] ?? [];
		$emails    = $customer_info['emails'] ?? [];
		$MmsUserInfoEdit = [
			'name'          => $customer_info['name'] ?? '',
			'customertype'  => ( ($customer_info['type'] ?? 0) == 1 ? '1' : '2' ),
			'lastname'      => $customer_info['lastname'] ?? '',
			'firstname'     => $customer_info['name'] ?? '',
			'pesel'         => $customer_info['ssn'] ?? '',
			'nip'           => $hook_data['ten'] ?? '',
			'city1'         => current(array_filter(array_column($addresses, 'location_city_name'))) ?? '',
			'zip1'          => current(array_filter(array_column($addresses, 'location_zip'))) ?? '',
			'streetname1'   => current(array_filter(array_column($addresses, 'location_short_street_name'))) ?? '',
			'streetno1'     => current(array_filter(array_column($addresses, 'location_house'))) ?? '',
			'local1'        => current(array_filter(array_column($addresses, 'location_flat'))) ?? '',
			'email'         => current(array_filter(array_column($emails, 'contact'))) ?? '',
		];

		$ReasonUpdateClientInMMS = $apiInit->ApiPut('/Users/users/'.$CustomerIdMMS['metroport_user_id'], $MmsUserInfoEdit);
		$errorBody = $ReasonUpdateClientInMMS['body'] ?? $ReasonUpdateClientInMMS['error'] ?? null;
		if ($errorBody) {
			print json_encode([
					'status'         => 'error',
					'success'        => 'false',                
					'message'        => 'Klient już jest w bazie danych MMS',
					'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> ' . $errorBody . '</div>',
					'lmscustomerid'  => $customerId,
					'api_result'     => $apiResult
				]);
		}
		else {
			print json_encode([
					'status'         => 'ok',
					'success'        => 'true',
					'message'        => 'Dane klienta zaktualizowane',
					'message_html'   => '<div style="background-color:#d4edda;color:#155724;padding:10px 15px;border:1px solid #c3e6cb;border-radius:4px;font-size:14px;"><strong>✅ Dane klienta zaktualizowane:</strong> ' . $CustomerIdMMS['metroport_user_id'] . '</div>',
					'lmscustomerid'  => $customerId,
					'api_result'     => $apiResult
				]);
		}
    }
}