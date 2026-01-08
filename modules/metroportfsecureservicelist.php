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
use LMSMetroportPlugin\Fsecure;

$result = [];

try {
    // Get ApiInit instance from plugin
    $apiInit = LMSMetroportPlugin::getApiInitInstance();

    if (!$apiInit) {
        throw new \Exception('Failed to fetch ApiInit.');
    }

    // Create Fsecure instance
    $fsecure = new Fsecure($apiInit);
    if (!$fsecure) {
        throw new \Exception('Failed to fetch fsecure.');
    }
    // Get F-Secure service list
    $services = $fsecure->GetFsecureServicesList();

    // Consistent SUCCESS message
    $result = ApiInit::Success($services, 'Lista pakietów F-Secure pobrana pomyślnie.');

} catch (\Exception $e) {
    // Consistent ERROR message
    $result = ApiInit::Error('Błąd pobierania listy pakietów F-Secure: ' . $e->getMessage());

    // Log to LMS error log
    error_log("[FsecureModule] " . $e->getMessage());
}
// Pass to Smarty
$SMARTY->assign('metroportFsecureResult', $result);

// Display template
$SMARTY->display('metroportfsecureservicelist.html');
