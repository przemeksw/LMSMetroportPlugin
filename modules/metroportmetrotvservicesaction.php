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
use LMSMetroportPlugin\MetroTV;
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
    $metrotv = LMSMetroportPlugin::getMetroTvInstance($apiInit);
    if (!$metrotv) {
        throw new \Exception('Nie udało się pobrać metrotv.');
    }
    
    if (!empty($_GET['DeleteStbFromAccountPackage']) && $_GET['DeleteStbFromAccountPackage']== true && $_GET['account_id']!="" && $_GET['mac']!="")
    {
		$data = [
					'mac'          => $_GET['mac'],
					'accountid' => $_GET['account_id']
					];
		
		$ReasonDeleteStbFromAccountPackage=$apiInit->ApiPut("/Iptv/Stbs/unlinkSTB",$data);
		$SESSION->redirect('?m=customerinfo&id='.$_GET['customerid'] );  
		exit();
    }

    if (!empty($_GET['DeletePackageFromAccount']) && $_GET['DeletePackageFromAccount']== true && $_GET['account_id']!="" && $_GET['package_id']!="")
    {
		$data = [
					'orderid'          => $_GET['package_id'],
					'accountid' => $_GET['account_id']
					];

		$ReasonDeletePackageFromAccount=$apiInit->ApiDelete("/Iptv/Orders/".$_GET['package_id'],$data);
		$SESSION->redirect('?m=customerinfo&id='.$_GET['customerid'] );  
		exit();
    }

    if (!empty($_GET['DeleteAccount']) && $_GET['DeleteAccount']== true && $_GET['account_id']!="")
    {
		$data = [
                'accountid' => $_GET['account_id']
                ];
		$ReasonDeleteAccount=$apiInit->ApiDelete("/Iptv/Accounts/".$_GET['account_id'],$data);
		$SESSION->redirect('?m=customerinfo&id='.$_GET['customerid'] );  
		exit();

    }

    if (!empty($_POST['get_metrotv_customer_account']) && $_POST['get_metrotv_customer_account']== true)
    {
		$MetroportCustomerId = $MetroportGlobal->CheckMetroportCustomerExistInLMS($_POST['lmscustomerid']);
		if($MetroportCustomerId)
		{
			$AccrontsInfo=$metrotv->GetFullAccountsMetroTvByUserId($MetroportCustomerId['metroport_user_id']);
			$AccrontsInfo['CustomerExistInMetroport'] = true;
			$AccrontsInfo['success'] = true;
			print json_encode($AccrontsInfo);
		}
		else {
			$result['CustomerExistInMetroport'] = false;
			$result['success'] = true;
			$result['data'] = array();
			print json_encode($result);
		}
		exit();
    }

    if (!empty($_POST['get_metrotv_customer_new_account_data']) && $_POST['get_metrotv_customer_new_account_data']== true)
    {
		$MetroportCustomerId = $MetroportGlobal->CheckMetroportCustomerExistInLMS($_POST['lmscustomerid']);

		if($MetroportCustomerId)
		{
			$new_account['userid']=$MetroportCustomerId;
			$new_account['networks']=$metrotv->GetMetroTvNetworkList();
			$new_account['address']=$LMS->getCustomerAddresses($_POST['lmscustomerid']);
			$new_account['CustomerExistInMetroport'] = true;
			$new_account['success'] = true;
			print json_encode($new_account);
			exit();
		}
		else {
			$result['CustomerExistInMetroport'] = false;
			$result['success'] = true;
			$result['data'] = array();
			print json_encode($result);
		}
		exit();
    }


    if (!empty($_POST['create_new_metrotv_account']) && $_POST['create_new_metrotv_account']== true)
    {
		$MetroportCustomerId = $MetroportGlobal->CheckMetroportCustomerExistInLMS($_POST['lmscustomerid']);
		if($MetroportCustomerId)
		{
			$data = [
			'userid'          =>  $_POST['metroport_user_id'],
			'iptv_networkid' => $_POST['network_id'],
			'locationstreetname' => $_POST['location_short_street_name'],
			'locationstreetno' => $_POST['location_house'],
			'locationlocal' => $_POST['location_flat'],
			'locationzip' => $_POST['location_zip'],
			'locationcity' => $_POST['location_city_name']
			];
			$ReasonAddNewMetroTvAccount=$apiInit->ApiPost("/Iptv/Accounts",$data);
			if (!empty($ReasonAddNewMetroTvAccount['success']) && !empty($ReasonAddNewMetroTvAccount['data'])) {
				print json_encode([
					'status'         => 'true',
					'success'        => 'true',
					'message'        => 'Konto MetroTV zostało dodane.',
					'message_html'   => '<div style="background-color:#d4edda;color:#155724;padding:10px 15px;border:1px solid #c3e6cb;border-radius:4px;font-size:14px;"><strong>✅ Konto MetroTV zostało dodane do klienta.</strong></div>',
					'lmscustomerid'  => $customerId,
					'api_result'     => $apiResult
				]);
			} else {
				$errorBody = $ReasonAddNewMetroTvAccount['body'] ?? $ReasonAddNewMetroTvAccount['error'] ?? 'Nieznany błąd';
				print json_encode([
					'status'         => 'error',
					'success'        => false,                
					'message'        => 'Bład.',
					'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> ' . $errorBody . '</div>',
					'lmscustomerid'  => $customerId,
					'api_result'     => $apiResult
				]);
			}
		}
		exit();
    }

    if (!empty($_POST['get_metrotv_customer_account_by_id']) && $_POST['get_metrotv_customer_account_by_id']== true && $_POST['accountid']!='')
    {
		$MetroportCustomerId = $MetroportGlobal->CheckMetroportCustomerExistInLMS($_POST['lmscustomerid']);
		if($MetroportCustomerId)
		{
			$MetroTvAccountData = $metrotv->GetAccountMetroTvByAccountId($_POST['accountid']);
			$MetroTvAccountData['networks']=$metrotv->GetMetroTvNetworkList();  
			print json_encode($MetroTvAccountData);
		}
		exit();
    }
    
    if (!empty($_POST['edit_exist_account']) && $_POST['edit_exist_account']== true)
    {
		$MetroportCustomerId = $MetroportGlobal->CheckMetroportCustomerExistInLMS($_POST['lmscustomerid']);
		if($MetroportCustomerId)
		{
			$data = [
			'userid'          =>  $MetroportCustomerId,
			'iptv_networkid' => $_POST['network_id'],
			'locationstreetname' => $_POST['location_short_street_name'],
			'locationstreetno' => $_POST['location_house'],
			'locationlocal' => $_POST['location_flat'],
			'locationzip' => $_POST['location_zip'],
			'locationcity' => $_POST['location_city_name']
			];
			$ReasonAddNewMetroTvAccount=$apiInit->ApiPut("/Iptv/Accounts/".$_POST['accountid'],$data);
			if (!empty($ReasonAddNewMetroTvAccount['success']) && !empty($ReasonAddNewMetroTvAccount['data'])) {
				print json_encode([
					'status'         => 'true',
					'success'        => 'true',
					'message'        => 'Konto zaktualizowane.',
					'message_html'   => '<div style="background-color:#d4edda;color:#155724;padding:10px 15px;border:1px solid #c3e6cb;border-radius:4px;font-size:14px;"><strong>✅ Konto MetroTV zostało zaktualizowane.</strong></div>',
					'lmscustomerid'  => $customerId,
					'api_result'     => $apiResult
				]);
			}
			else {
				$errorBody = $ReasonAddNewMetroTvAccount['body'] ?? $ReasonAddNewMetroTvAccount['error'] ?? 'Nieznany błąd';
				print json_encode([
					'status'         => 'error',
					'success'        => false,                
					'message'        => 'Bład edycji konta.',
					'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> ' . $errorBody . '</div>',
					'lmscustomerid'  => $customerId,
					'api_result'     => $apiResult
				]);
			}
		}
		exit();
    }

    if (!empty($_POST['get_basic_packages']) && $_POST['get_basic_packages']== true &&  $_POST['type']!='')
    {
		$packages=$metrotv->GetMetroTvSPackageListByType($_POST['type']);
		if (!empty($packages))
		{
			print json_encode([
			'status'         => 'true',
			'success'        => 'true',
			'message'        => 'Pobrano listę pakietów.',
			'message_html'   => '<div style="background-color:#d4edda;color:#155724;padding:10px 15px;border:1px solid #c3e6cb;border-radius:4px;font-size:14px;"><strong>✅ Konto MetroTV zostało zaktualizowane.</strong></div>',
			'api_result'     => $packages
			]);
		}
		else {
			$errorBody = $packages['body'] ?? $packages['error'] ?? 'Nieznany błąd';
			print json_encode([
			'status'         => 'error',
			'success'        => false,                
			'message'        => 'Bład pobierania listy pakietów.',
			'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> ' . $errorBody . '</div>',
			'api_result'     => $packages
			]);
		}
		exit();
    }

    if (!empty($_POST['add_basic_package']) && $_POST['add_basic_package']== true && $_POST['package_id']!='')
    {
		$MetroportCustomerId = $MetroportGlobal->CheckMetroportCustomerExistInLMS($_POST['lmscustomerid']);
		if($MetroportCustomerId)
		{  
			$data = [
			'accountid'          =>  $_POST['account_id'],
			'pkg_id' => $_POST['package_id'],
			'pricebrutto' => $_POST['price']
			];
			$ReasonAddBasicPackage=$apiInit->ApiPost("/Iptv/Orders", $data);
			if (!empty($ReasonAddBasicPackage['success']) && !empty($ReasonAddBasicPackage['data'])) {
				print json_encode([
					'status'         => 'true',
					'success'        => 'true',
					'message'        => 'Dodano pakiet podstawowy.',
					'message_html'   => '<div style="background-color:#d4edda;color:#155724;padding:10px 15px;border:1px solid #c3e6cb;border-radius:4px;font-size:14px;"><strong>✅ Dodano pakiet.</strong></div>',
					'lmscustomerid'  => $customerId,
					'api_result'     => $apiResult
				]);
			} else {
				$errorBody = $ReasonAddBasicPackage['body'] ?? $ReasonAddBasicPackage['error'] ?? 'Nieznany błąd';
				print json_encode([
					'status'         => 'error',
					'success'        => false,                
					'message'        => 'Bład.',
					'message_html'   => '<div style="background-color:#f8d7da;color:#721c24;padding:10px 15px;border:1px solid #f5c6cb;border-radius:4px;font-size:14px;"><strong>❌ Błąd:</strong> ' . $errorBody . '</div>',
					'lmscustomerid'  => $customerId,
					'api_result'     => $apiResult
				]);
			}
		}
		exit();
    }

    if (!empty($_GET['block']) && $_GET['block']== true && $_GET['account_id']!="")
    {
	  	$data = [
                'accountid' => $_GET['account_id']
                ];

		$ReasonBlockAccount=$apiInit->ApiPut("/Iptv/Accounts/".$_GET['account_id']."/blockAccount",$data);
		$SESSION->redirect('?m=customerinfo&id='.$_GET['customerid'] );  
		exit();
    }

    if (!empty($_GET['unblock']) && $_GET['unblock']== true && $_GET['account_id']!="")
    {
	  $data = [
                'accountid' => $_GET['account_id']
                ];

		$ReasonUnblockAccount=$apiInit->ApiPut("/Iptv/Accounts/".$_GET['account_id']."/unblockAccount",$data);
		$SESSION->redirect('?m=customerinfo&id='.$_GET['customerid'] );  
		exit();
    }

    if (!empty($_POST['get_stb_list']) && $_POST['get_stb_list']== true)
    {
		$ReasonGetStbList = $metrotv->GetMetroTvStbList(1);
		die(json_encode($ReasonGetStbList));
		exit();
    }

    if (!empty($_POST['add_stb_to_package']) && $_POST['add_stb_to_package']== true && $_POST['account_id']!="" && $_POST['packageid']!="" && $_POST['stb_mac']!="")
    {
	  $data = [
                'accountid' => $_POST['account_id'],
		'orderid' => $_POST['packageid'],
		'networkid' => $_POST['network_id'],
		'mac' => $_POST['stb_mac']                ];

		$ReasonAddStbToPackage=$apiInit->ApiPut("/Iptv/Stbs/linkSTB",$data);
		if (!empty($ReasonAddStbToPackage['success']) && !empty($ReasonAddStbToPackage['data'])) {
			print json_encode([
				'status'         => 'true',
				'success'        => 'true',
				'message'        => 'Dodano pakiet podstawowy.',
				'message_html'   => '<div style="background-color:#d4edda;color:#155724;padding:10px 15px;border:1px solid #c3e6cb;border-radius:4px;font-size:14px;"><strong>✅ Dodano STB.</strong></div>',
				'lmscustomerid'  => $customerId,
				'api_result'     => $apiResult
			]);
		}
		else {
			$errorBody = $ReasonEAddStb['body'] ?? $ReasonEAddStb['error'] ?? 'Nieznany błąd';
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