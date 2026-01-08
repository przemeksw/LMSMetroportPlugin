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
namespace LMSMetroportPlugin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class MetroTV
{
    private ApiInit $apiInit;
    private Client $client;
    private string $baseUrl;
	private $DB;
    public function __construct(ApiInit $apiInit)
    {
		$this->DB = \LMSDB::getInstance();
        $this->apiInit = $apiInit;
        $this->client = new Client([
            'timeout'     => 10,
            'http_errors' => true,
            'verify'      => true,
            'headers'     => [
                'Accept'     => 'application/json',
                'User-Agent' => 'LMSMetroportPlugin/1.0',
            ],
        ]);

		// Fetch base URL from config
        $baseUrl = trim(\ConfigHelper::getConfig('metroport.serwer_url', ''));
        if (empty($baseUrl)) {
            throw new \Exception('Brak konfiguracji serwera Metroport.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
    }

	    protected function findAccountInArrayById(array $arr, $accountId)
	    {
		foreach ($arr as $key => $val) {
		    if (is_array($val)) {
				// if this sub-array has a direct 'id'
				if (isset($val['id']) && (string) $val['id'] === $accountId) {
					return $val;
				}
				// or has 'data' with an 'id'
				if (isset($val['data']) && is_array($val['data']) && isset($val['data']['id']) && (string) $val['data']['id'] === $accountId) {
					return $val['data'];
				}
				// recursion
				$res = $this->findAccountInArrayById($val, $accountId);
				if ($res !== null) {
					return $res;
				}
		    }
		}
		return null;
	    }

    public function GetMetroTvServicesList(): array
    {
		// Fetch sess_ci token from ApiInit
        $token = $this->apiInit->getToken();

		// Full URL to the F-Secure endpoint
        $url = $this->baseUrl . '/Iptv/Packages';

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Cookie'     => 'sess_ci=' . $token,
                    'Accept'     => 'application/json',
                    'User-Agent' => 'LMSMetroportPlugin/1.0',
                ],
            ]);

            $data = json_decode((string)$response->getBody(), true);

            if ($data === null) {
                throw new \Exception('Nie udało się zdekodować odpowiedzi JSON z API.');
            }

            return $data;

        } catch (RequestException $e) {
            $body   = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'brak statusu';
            $reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

            $msg = "Błąd połączenia z API Metroport (MetroTV services): $status $reason";
            if (!empty($body)) {
                $msg .= "\nTreść odpowiedzi:\n$body";
            }

            throw new \Exception($msg);
        }
    }

    public function GetMetroTvSPackageListByType(?string $type = null): array
    {
		// Fetch sess_ci token from ApiInit
        $token = $this->apiInit->getToken();

        // Full URL to the F-Secure endpoint
		$url = $this->baseUrl . '/Iptv/Packages';

	try {
	    $response = $this->client->get($url, [
			'headers' => [
				'Cookie'     => 'sess_ci=' . $token,
				'Accept'     => 'application/json',
				'User-Agent' => 'LMSMetroportPlugin/1.0',
			],
	    ]);

	    $data = json_decode((string)$response->getBody(), true);

	    if ($data === null) {
			throw new \Exception('Nie udało się zdekodować odpowiedzi JSON z API.');
	    }

	    // Types to filter by
	    if ($type !== null) {
			// Determine which groups to filter by
			if ($type === 'podstawowy') {
				$groups = [1];
			} elseif ($type === 'multiroom') {
				$groups = [3];
			} elseif ($type === 'dodatkowy' || $type === 'ppv' || $type === 'multiroom') {
				$groups = [2, 4,3];
			} else {
				$groups = []; // Invalid type, will return nothing
			}

			// If groups are set, filter
			if (!empty($groups)) {
				$data = array_filter($data, function ($item) use ($groups) {
				return isset($item['pkg_group']) && in_array(intval($item['pkg_group']), $groups);
				});
				$data = array_values($data);
			} else {
				$data = [];
			}
	    }

	    // Filter only active=1
	    $data = array_filter($data, function ($item) {
			return isset($item['active']) && intval($item['active']) === 1;
	    });
	    $data = array_values($data);

	    return $data;

	} catch (RequestException $e) {
	    $body   = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
	    $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'brak statusu';
	    $reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

	    $msg = "Błąd połączenia z API Metroport (MetroTV services): $status $reason";
	    if (!empty($body)) {
			$msg .= "\nTreść odpowiedzi:\n$body";
	    }

	    throw new \Exception($msg);
	}
    }

    public function GetMetroTvNetworkList(): array
    {
        // Fetch sess_ci token from ApiInit
        $token = $this->apiInit->getToken();

		// Full URL to the F-Secure endpoint
        $url = $this->baseUrl . '/Iptv/Networks?count=1';

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Cookie'     => 'sess_ci=' . $token,
                    'Accept'     => 'application/json',
                    'User-Agent' => 'LMSMetroportPlugin/1.0',
                ],
            ]);

            $data = json_decode((string)$response->getBody(), true);

            if ($data === null) {
                throw new \Exception('Nie udało się zdekodować odpowiedzi JSON z API.');
            }

            return $data;

        } catch (RequestException $e) {
            $body   = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'brak statusu';
            $reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

            $msg = "Błąd połączenia z API Metroport (MetroTV services): $status $reason";
            if (!empty($body)) {
                $msg .= "\nTreść odpowiedzi:\n$body";
            }

            throw new \Exception($msg);
        }
    }

    public function GetMetroTvStbModelList(): array
    {
		// Fetch sess_ci token from ApiInit
        $token = $this->apiInit->getToken();

		// Full URL to the F-Secure endpoint
        $url = $this->baseUrl . '/Iptv/StbModels';

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Cookie'     => 'sess_ci=' . $token,
                    'Accept'     => 'application/json',
                    'User-Agent' => 'LMSMetroportPlugin/1.0',
                ],
            ]);

            $data = json_decode((string)$response->getBody(), true);

            if ($data === null) {
                throw new \Exception('Nie udało się zdekodować odpowiedzi JSON z API.');
            }

            return $data;

        } catch (RequestException $e) {
            $body   = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'brak statusu';
            $reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

            $msg = "Błąd połączenia z API Metroport (MetroTV services): $status $reason";
            if (!empty($body)) {
                $msg .= "\nTreść odpowiedzi:\n$body";
            }

            throw new \Exception($msg);
        }
    }
    
    public function GetMetroTvStbList(int $state = null): array
    {
		// Fetch sess_ci token from ApiInit
		$token = $this->apiInit->getToken();

		// Full URL to the F-Secure endpoint
		$url = $this->baseUrl . '/Iptv/Stbs';

		try {
			$response = $this->client->get($url, [
				'headers' => [
					'Cookie'     => 'sess_ci=' . $token,
					'Accept'     => 'application/json',
					'User-Agent' => 'LMSMetroportPlugin/1.0',
				],
			]);

			$data = json_decode((string)$response->getBody(), true);

			if ($data === null) {
				throw new \Exception('Nie udało się zdekodować odpowiedzi JSON z API.');
			}

			// Filter by status if $state is provided
			if ($state !== null) {
				$data = array_filter($data, function($item) use ($state) {
				return isset($item['status']) && intval($item['status']) === $state;
			});
				// array_filter keeps original keys; reindex to 0,1,2,... for continuity
				$data = array_values($data);
			}

			return $data;

		} catch (RequestException $e) {
			$body   = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
			$status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'brak statusu';
			$reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

			$msg = "Błąd połączenia z API Metroport (MetroTV services): $status $reason";
			if (!empty($body)) {
				$msg .= "\nTreść odpowiedzi:\n$body";
			}

			throw new \Exception($msg);
		}
    }

	public function GetMetroTvPacketsForAccount($accountid = 0): array
	{
		$token = $this->apiInit->getToken();
		$url = $this->baseUrl . '/Iptv/Orders?accountid=' . $accountid;

		try {
			$response = $this->client->get($url, [
				'headers' => [
					'Cookie'     => 'sess_ci=' . $token,
					'Accept'     => 'application/json',
					'User-Agent' => 'LMSMetroportPlugin/1.0',
				],
			]);

			$body = (string)$response->getBody();

			// If the response is empty, return an empty array instead of an error
			if (trim($body) === '') {
				return [];
			}

			$data = json_decode($body, true);

			// Throw an exception only if JSON is invalid
			if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
				throw new \Exception('Nie udało się zdekodować odpowiedzi JSON z API: (' . $url . ').');
			}

			return $data ?? [];

		} catch (RequestException $e) {
			$status = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;

			// If the API returns 404, return an empty array
			if ($status === 404) {
				return [];
			}

			$body   = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
			$reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

			$msg = "Błąd połączenia z API Metroport (MetroTV services): $status $reason";
			if (!empty($body)) {
				$msg .= "\nTreść odpowiedzi:\n$body";
			}

			throw new \Exception($msg);
		}
    }

    public function GetAllCustomerMetroTvServices(): array
    {
        $ReasonGetCustomerMetroTvService = $this->apiInit->ApiGet("/Iptv/Accounts");
		foreach ($ReasonGetCustomerMetroTvService['data'][1] as &$item_Accout) {
			$MetroTvPacketsForAccount = $this->GetMetroTvPacketsForAccount($item_Accout['id']);
	       $item_Accout['MetroTvPacketsForAccount']=$MetroTvPacketsForAccount;
		}
		unset($item_Accout);
        return $ReasonGetCustomerMetroTvService;
    }

    public function DeleteSTB(string $id): array
    {
         return $ReasonGetCustomerMmsById=$this->apiInit->ApiDelete("/Iptv/Stbs/".$id);
    }

    public function AddSTB($param): array
    {
         return $ReasonGetCustomerMmsById=$this->apiInit->ApiPost("/Iptv/Stbs/", $param);
    }

    public function GetAccountMetroTvByAccountId($accountid)
    {
  		$ReasonGetAcceuntsUser = $this->apiInit->ApiGet("/Iptv/Accounts/" . $accountid);
		return $ReasonGetAcceuntsUser;
    }
    public function GetFullAccountsMetroTvByUserId($uid)
    {
		$ReasonGetAcceuntsUser = $this->apiInit->ApiGet("/Iptv/Accounts?userid=" . $uid);
		if (!isset($ReasonGetAcceuntsUser['accounts']) || !is_array($ReasonGetAcceuntsUser['accounts'])) {
		    $ReasonGetAcceuntsUser['accounts'] = [];
		}
		if (empty($ReasonGetAcceuntsUser['error'])) {
		    if (isset($ReasonGetAcceuntsUser['data'][1]) && is_array($ReasonGetAcceuntsUser['data'][1])) {
				$id = 0;
				foreach ($ReasonGetAcceuntsUser['data'][1] as $item_Accout) {
		    		$id++;
		    		$ReasonGetAcceuntsUser['accounts'][$id] = $this->apiInit->ApiGet("/Iptv/Accounts/" . $item_Accout['id']);
				}
	    	} else {
				// No accounts in response — set an empty list or keep existing accounts
				// Optionally add a diagnostic note
				$ReasonGetAcceuntsUser['notice'] = 'Brak kont w data[1]';
	    	}
		} else {
	    	$ReasonGetAcceuntsUser['success'] = false;
		}

		return $ReasonGetAcceuntsUser;
    }    

	public function GetFullAccountsMetroTvByLMSUserId($uid)
	{
		$metroport_userid=$this->DB->GetRow('SELECT metroport_user_id FROM metroport_customers WHERE lms_user_id = ?',array($uid));
		if (!empty($metroport_userid['metroport_user_id'])) {
			return $this->GetFullAccountsMetroTvByUserId($metroport_userid['metroport_user_id']);
		} else {
			return array();
		}

	}

	public function GetAccountsIdMetroTvByLMSUserId($id)
	{
		$accounts = $this->GetFullAccountsMetroTvByLMSUserId($id);
		$accounts_ids = array();
		if (isset($accounts['accounts']) && is_array($accounts['accounts'])) {
			foreach ($accounts['accounts'] as $account) {
				$accounts_ids[] = $account['data']['id'];
			}
		}
		return $accounts_ids;
	}

	/**
	 * Synchronizes the STB list into the metroport_metrotv_stb table.
	 * Uses SELECT, INSERT, and UPDATE via standard LMSDB methods.
	 * 
	 * @param array $stbs Array of STB decoders from the API
	 * @return array Stats: ['inserted' => n, 'updated' => m]
	 */
	public function SyncStbListToDb(array $stbs): array
	{
		$res = [
			'inserted' => 0,
			'updated' => 0,
		];

		if (empty($stbs)) {
			return $res;
		}

		foreach ($stbs as $stb) {
			$id = $stb['id'] ?? null;
			
			if (empty($id)) {
			continue; // Skip records without an ID
		}

		// Check if the record exists

			$fields = array(
				'id' => $id,
				'firmid' => $stb['firmid'] ?? 0,
				'iptv_program' => $stb['iptv_program'] ?? null,
				'iptv_portalid' => $stb['iptv_portalid'] ?? 0,
				'status' => $stb['status'] ?? 0,
				'userid' => $stb['userid'] ?? 0,
				'contractid' => $stb['contractid'] ?? 0,
				'actualtariffid' => $stb['actualtariffid'] ?? 0,
				'networkid' => $stb['networkid'] ?? 0,
				'ext_linked' => $stb['ext_linked'] ?? 0,
				'mac' => $stb['mac'] ?? null,
				'ipaddr' => $stb['ipaddr'] ?? null,
				'serialnumber' => $stb['serialnumber'] ?? null,
				'vcasid' => $stb['vcasid'] ?? null,
				'customer_sn' => $stb['customer_sn'] ?? null,
				'hdcp_ksv' => $stb['hdcp_ksv'] ?? null,
				'modelid' => $stb['modelid'] ?? 0,
				'devel' => $stb['devel'] ?? 0,
				'servicetype' => $stb['servicetype'] ?? null,
				'servicetype_force' => $stb['servicetype_force'] ?? 0,
				'must_reboot' => $stb['must_reboot'] ?? 0,
				'description' => $stb['description'] ?? null,
				'activedate' => $stb['activedate'] ?? 0,
				'error_log' => $stb['error_log'] ?? 0,
				'fwlog' => $stb['fwlog'] ?? 0,
				'boot_options' => $stb['boot_options'] ?? null,
				'creationdate' => $stb['creationdate'] ?? 0,
				'creatorid' => $stb['creatorid'] ?? 0,
				'moddate' => $stb['moddate'] ?? 0,
				'modid' => $stb['modid'] ?? 0,
				'nodeid' => $stb['nodeid'] ?? 0,
				'private' => $stb['private'] ?? 0,
				'active' => $stb['active'] ?? 0,
				'AddressIp' => $stb['AddressIp'] ?? null,
				'ModelName' => $stb['ModelName'] ?? null,
				'iptv_accountid' => $stb['iptv_accountid'] ?? 0,
				'order_id' => $stb['order_id'] ?? null,
				'firmname' => $stb['firmname'] ?? null,
				'UserName' => $stb['UserName'] ?? null,
				'usertype' => $stb['usertype'] ?? null,
				'StatusName' => $stb['StatusName'] ?? null,
				'channels' => $stb['channels'] ?? null,
				'iptv_portal' => $stb['iptv_portal'] ?? null,
			);

			if ($existing) {
				// UPDATE
				$this->DB->Execute('UPDATE metroport_metrotv_stb SET
					firmid = ?, iptv_program = ?, iptv_portalid = ?, status = ?, userid = ?,
					contractid = ?, actualtariffid = ?, networkid = ?, ext_linked = ?, mac = ?,
					ipaddr = ?, serialnumber = ?, vcasid = ?, customer_sn = ?, hdcp_ksv = ?,
					modelid = ?, devel = ?, servicetype = ?, servicetype_force = ?, must_reboot = ?,
					description = ?, activedate = ?, error_log = ?, fwlog = ?, boot_options = ?,
					creationdate = ?, creatorid = ?, moddate = ?, modid = ?, nodeid = ?,
					private = ?, active = ?, AddressIp = ?, ModelName = ?, iptv_accountid = ?,
					order_id = ?, firmname = ?, UserName = ?, usertype = ?, StatusName = ?,
					channels = ?, iptv_portal = ?
					WHERE id = ?',
					array(
						$fields['firmid'], $fields['iptv_program'], $fields['iptv_portalid'],
						$fields['status'], $fields['userid'], $fields['contractid'],
						$fields['actualtariffid'], $fields['networkid'], $fields['ext_linked'],
						$fields['mac'], $fields['ipaddr'], $fields['serialnumber'],
						$fields['vcasid'], $fields['customer_sn'], $fields['hdcp_ksv'],
						$fields['modelid'], $fields['devel'], $fields['servicetype'],
						$fields['servicetype_force'], $fields['must_reboot'], $fields['description'],
						$fields['activedate'], $fields['error_log'], $fields['fwlog'],
						$fields['boot_options'], $fields['creationdate'], $fields['creatorid'],
						$fields['moddate'], $fields['modid'], $fields['nodeid'],
						$fields['private'], $fields['active'], $fields['AddressIp'],
						$fields['ModelName'], $fields['iptv_accountid'], $fields['order_id'],
						$fields['firmname'], $fields['UserName'], $fields['usertype'],
						$fields['StatusName'], $fields['channels'], $fields['iptv_portal'],
						$id
					)
				);
				$res['updated']++;
			} else {
				// INSERT
				$this->DB->Execute('INSERT INTO metroport_metrotv_stb (
					id, firmid, iptv_program, iptv_portalid, status, userid, contractid, actualtariffid,
					networkid, ext_linked, mac, ipaddr, serialnumber, vcasid, customer_sn, hdcp_ksv,
					modelid, devel, servicetype, servicetype_force, must_reboot, description, activedate,
					error_log, fwlog, boot_options, creationdate, creatorid, moddate, modid, nodeid,
					private, active, AddressIp, ModelName, iptv_accountid, order_id, firmname,
					UserName, usertype, StatusName, channels, iptv_portal
				) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
					array(
						$fields['id'], $fields['firmid'], $fields['iptv_program'],
						$fields['iptv_portalid'], $fields['status'], $fields['userid'],
						$fields['contractid'], $fields['actualtariffid'], $fields['networkid'],
						$fields['ext_linked'], $fields['mac'], $fields['ipaddr'],
						$fields['serialnumber'], $fields['vcasid'], $fields['customer_sn'],
						$fields['hdcp_ksv'], $fields['modelid'], $fields['devel'],
						$fields['servicetype'], $fields['servicetype_force'], $fields['must_reboot'],
						$fields['description'], $fields['activedate'], $fields['error_log'],
						$fields['fwlog'], $fields['boot_options'], $fields['creationdate'],
						$fields['creatorid'], $fields['moddate'], $fields['modid'],
						$fields['nodeid'], $fields['private'], $fields['active'],
						$fields['AddressIp'], $fields['ModelName'], $fields['iptv_accountid'],
						$fields['order_id'], $fields['firmname'], $fields['UserName'],
						$fields['usertype'], $fields['StatusName'], $fields['channels'],
						$fields['iptv_portal']
					)
				);
				$res['inserted']++;
			}
		}

		return $res;
	}


}
