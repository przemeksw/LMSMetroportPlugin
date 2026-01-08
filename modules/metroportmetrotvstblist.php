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

$result = [];

try {
    $apiInit = LMSMetroportPlugin::getApiInitInstance();
    if (!$apiInit) {
        throw new \Exception('Nie udało się pobrać ApiInit.');
    }
    $metrotv = LMSMetroportPlugin::getMetroTvInstance($apiInit);
    $MetroportGlobal = LMSMetroportPlugin::getMetroGlobalInstance($apiInit);
    if (!$metrotv) {
        throw new \Exception('Nie udało się pobrać metrotv.');
    }
    $stbs = $metrotv->GetMetroTvStbList();
    $ModelList = $metrotv->GetMetroTvStbModelList();
    $result = ApiInit::Success($stbs, 'Lista modeli stb MetroTV pobrana pomyślnie.');

} catch (\Exception $e) {
    $result = ApiInit::Error('Błąd pobierania listy podsieci MetroTV: ' . $e->getMessage());
    error_log("[MetroTVModule] " . $e->getMessage());
}

if (isset($_GET['getModelList']) && $_GET['getModelList'] == true) {
    $ModelList = $metrotv->GetMetroTvStbModelList();
    die(json_encode($ModelList));
    exit();
}

if (isset($_GET['DeleteStb']) && $_GET['DeleteStb'] === 'true' && $_GET['id']) {
    $stb_del_result=$metrotv->DeleteSTB($_GET['id']);
    $SESSION->redirect('?m=metroportmetrotvstblist' ); 
}

if (isset($_POST['AddModel']) && $_POST['AddModel'] == true) {
    $param=array('ModelName' => $_POST['model'], 'serialnumber' => $_POST['serial'],'mac' => $_POST['mac']);
    $addStb = $metrotv->AddSTB($param);
    die(json_encode($addStb));
    exit();
}

$order = [
    'wydane' => 0,
    'w magazynie' => 1,
    'zarezerwowane' => 2,
    'uszkodzone' => 3,
    'usunięte' => 4,
];
if ($result['status']=='success')
{
    usort($result['data'], function($a, $b) use ($order) {
        return $order[$a['StatusName']] <=> $order[$b['StatusName']];
    });

    $modelMap = [];
    foreach ($ModelList as $model) {
	    $modelMap[$model['id']] = $model['name'];
    }

    foreach ($result['data'] as $key => $item) {
        $modelId = $item['modelid'] ?? null;
        if ($modelId !== null && isset($modelMap[$modelId])) {
            $result['data'][$key]['modelname'] = $modelMap[$modelId];
        } else {
            $result['data'][$key]['modelname'] = null;
        }
    }

    foreach ($result['data'] as &$customer) {
        $userid = $customer['userid'];
        $lmsId  = $MetroportGlobal->GetCustomerLmsIdByIdMms($userid);
        $lmsUsername = $LMS->GetCustomerName($lmsId);
        $mmsUsernameArray = $MetroportGlobal->GetCustomerMmsByID($userid);
        $mmsUsername=$mmsUsernameArray['data']['name'].' '.$mmsUsernameArray['data']['lastname'];
        if (!empty($lmsId)) {
            $customer['lms_user_id'] = $lmsId;
            $customer['lms_username'] = $lmsUsername;
            $customer['mms_username'] = $mmsUsername;
        } else {
            $customer['lms_user_id'] = '';
            $customer['lms_username'] = '';
            $customer['mms_username'] = $mmsUsername;
        }
    }

}

$SMARTY->assign('metroportMetroTVResult', $result);
$SMARTY->registerPlugin('modifier', 'long2ip', 'long2ip');
$SMARTY->display('metroportmetrotvstblist.html');
