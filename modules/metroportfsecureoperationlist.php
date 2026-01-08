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
$result = [];

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
    $FsecureOperationList = $fsecure->GetFsecureOperationList();
    if (!empty($FsecureOperationList['error'])) {
        $result = ApiInit::Error("Błąd pobierania listy operacji: ".$FsecureOperationList['error']); 
    } else {
        $result = ApiInit::Success($FsecureOperationList['data'] ?? $FsecureOperationList, 'Lista operacji pobrana pomyślnie.');
    }

} catch (\Exception $e) {
    $result = ApiInit::Error('Błąd pobierania listy operacji: ' . $e->getMessage());
    error_log("[MetroportGlobalModule] " . $e->getMessage());
}

$result['count'] = count($result['data']);
$listdata['total'] = $result['count'];
unset($customerlist['total']);
unset($customerlist['order']);
unset($customerlist['direction']);
$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit=$listdata['total'];
$start = ($page - 1) * '30';

if ($result['status']=='success')
{
    foreach ($result['data'] as &$customer) {
	$userid = $customer['userid'];
	$lmsId  = $MetroportGlobal->GetCustomerLmsIdByIdMms($userid);
	$lmsUsername = $LMS->GetCustomerName($lmsId);
	$mmsUsernameArray = $MetroportGlobal->GetCustomerMmsByID($userid);
	$mmsUsername=$mmsUsernameArray['data']['name'].' '.$mmsUsernameArray['data']['lastname'];
	$customer['creationdate']=$MetroportGlobal->TimestampToDate($customer['creationdate']);
	$customer['execdate']=$MetroportGlobal->TimestampToDate($customer['execdate']);

	if (!empty($lmsId)) {
	    $customer['lms_user_id'] = $lmsId;
	    $customer['lms_username'] = $lmsUsername;
	    $customer['mms_username'] = $mmsUsername;
	} else {
	    $customer['lms_user_id'] = ''; // utwórz klucz, ale bez wartości
	    $customer['lms_username'] = '';
	    $customer['mms_username'] = $mmsUsername;
	}
    }
}

$SMARTY->assign('page', $page);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('start', $start);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('metroportfsecureoperationlistResult', $result);
$SMARTY->display('metroportfsecureoperationlist.html');
