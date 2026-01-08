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

$result = '';

try {
    $api = new ApiInit(true);
    $token = $api->getToken();
    $result = [
        'status' => 'success',
        'message1' => "Połączenie z API Metroport zostało pomyślnie nawiązane.",
        'message2' => "Nastąpiło pobranie nowego tokenu.",
        'token' => $token,
        'login_url' => $api->getLoginUrl(),
    ];
} catch (\Exception $e) {
    $result = [
        'status' => 'error',
        'message1' => "Błąd połączenia z API Metroport: " . $e->getMessage(),
    ];
    error_log('[MetroportApiTest] Błąd połączenia z API: ' . $e->getMessage());
}
$SMARTY->assign('metroportTestResult', $result);
$SMARTY->display('metroportconnectiontest.html');
