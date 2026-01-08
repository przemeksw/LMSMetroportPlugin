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
use LMSMetroportPlugin\MetroTV;

$result = [];

try {
    $apiInit = LMSMetroportPlugin::getApiInitInstance();

    if (!$apiInit) {
        throw new \Exception('Nie udało się pobrać ApiInit.');
    }

    $metrotv = LMSMetroportPlugin::getMetroTvInstance($apiInit);
    if (!$metrotv) {
        throw new \Exception('Nie udało się pobrać metrotv.');
    }
    $ModelList = $metrotv->GetMetroTvStbModelList();
    $result = ApiInit::Success($ModelList, 'Lista podsieci MetroTV pobrana pomyślnie.');

} catch (\Exception $e) {
    $result = ApiInit::Error('Błąd pobierania listy podsieci MetroTV: ' . $e->getMessage());
    error_log("[MetroTVModule] " . $e->getMessage());
}

$SMARTY->assign('metroportMetroTVResult', $result);
$SMARTY->registerPlugin('modifier', 'long2ip', 'long2ip');
$SMARTY->display('metroportmetrotvstbmodellist.html');
