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

class MetroportCustomerHandler
{

    // Hook after client validation
    public static function customeredit_validation_before_submit(array $hook_data)
    { 
		global $LMS;
		$LmsUserId=Auth::GetCurrentUser();
		$LmsUserRightArray=$LMS->GetUserRights($LmsUserId);
		$apiInit = LMSMetroportPlugin::getApiInitInstance();
		if (in_array('metroportcustomerupdate_ajax', $LmsUserRightArray, true)) {
			if (!$apiInit) {
				throw new \Exception('Nie udało się pobrać ApiInit.');
			}

			$MetroportGlobal = new GlobalMetroport($apiInit);
			if (!$MetroportGlobal) {
				throw new \Exception('Nie udało się pobrać GlobalMetroport.');
			}       

			if ($apiInit->automatically_update_customer_data=="true")
			{
				if (!$hook_data['error'] || !$hook_data['warning'])
				{
					$CustomerIdLMS= $hook_data['customerdata']['id'];
					$CustomerIdMMS= $MetroportGlobal->CheckMetroportCustomerExistInLMS($CustomerIdLMS);
					if ($CustomerIdMMS)
					{
					$CustomerIdMMS=$CustomerIdMMS['metroport_user_id'] ?? 0;
					$MmsUserInfoEdit=array('name' => $customer_info['name'],
					'customertype' => ( $hook_data['customerdata']['type'] ?? 0) == 1 ? '1' : '2',
					'lastname' =>  $hook_data['customerdata']['lastname'] ?? '',
					'name' =>  $hook_data['customerdata']['name'] ?? '',
					'pesel' =>  $hook_data['customerdata']['ssn'] ?? '',
					'nip' =>  $hook_data['customerdata']['ten'] ?? '',
					'city1'       =>  $hook_data['customerdata']['addresses']['0']['location_city_name'] ?? '',
					'zip1'        => $hook_data['customerdata']['addresses']['0']['location_zip'] ?? '',
					'streetname1' => $hook_data['customerdata']['addresses']['0']['location_short_street_name'] ?? '',
					'streetno1'   => $hook_data['customerdata']['addresses']['0']['location_house'] ?? '',
					'local1'      => $hook_data['customerdata']['addresses']['0']['location_flat'] ?? '',
					'email' => $hook_data['customerdata']['emails']['0']['contact']) ?? '';
					$ReasonUpdateClientInMMS = $apiInit->ApiPut("/Users/users/$CustomerIdMMS", $MmsUserInfoEdit);
					$errorBody = $ReasonUpdateClientInMMS['body'] ?? $ReasonUpdateClientInMMS['error'] ?? null;
					if ($errorBody) {
						$hook_data['error'] = 'true';
						$hook_data['warning'] = 'true';
						$hook_data['customerdata']['mms'] = $errorBody;
					}
					}
				}
			}
		}
		return $hook_data;
    }

    public function customerinfo_BeforeDisplay(array $hook_data)
    {
        global $LMS;
        $SMARTY = $hook_data['smarty'];
        $resource_tabs = $SMARTY->getTemplateVars('resource_tabs');

        if (!isset($resource_tabs['fsecure_customer_services']) || $resource_tabs['fsecure_customer_services']) {
            require_once(PLUGINS_DIR . '/' . LMSMetroportPlugin::PLUGIN_DIRECTORY_NAME . '/modules/metroportfsecureservicecustomer.php');
        }

        return $hook_data;
    }
}