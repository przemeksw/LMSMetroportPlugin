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

class Fsecure
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

    /**
     * Fetches the F-Secure service list from the Metroport API
     */
    public function GetFsecureServicesList(): array
    {
	    // Fetch sess_ci token from ApiInit
        $token = $this->apiInit->getToken();

	    // Full URL to the F-Secure endpoint
        $url = $this->baseUrl . '/services/security/ext_services';

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

            $msg = "Błąd połączenia z API Metroport (F-Secure services): $status $reason";
            if (!empty($body)) {
                $msg .= "\nTreść odpowiedzi:\n$body";
            }

            throw new \Exception($msg);
        }
    }
    
    public function GetCustomerFsecureServicesByIdLMS(int $customer_id): array
    {
        $metroport_userid=$this->DB->GetRow('SELECT metroport_user_id FROM metroport_customers WHERE lms_user_id = ?',array($customer_id));
		if (!empty($metroport_userid['metroport_user_id'])) {
			return $this->GetCustomerFsecureServicesByIdMms($metroport_userid['metroport_user_id']);
		} else {
			return array();
		}
    }


    public function GetCustomerFsecureServicesByIdMms(int $customer_id): array
    {
        $FsercureServices_list = $this->GetFsecureServicesList();
        $ReasonGetCustomerFsecureService = $this->apiInit->ApiGet("/services/security/?userid=".$customer_id);
        $servicesMap = [];
        foreach ($FsercureServices_list as $service) {
            $servicesMap[$service['id']] = $service['name'];
        }
        $statusMap = [
            'reserved' => '<span class="badge badge-info">Zarezerwowane, bez aktywacji</span>',
            'new'      => '<span class="badge badge-primary">Oczekuje na potwierdzenie aktywacji</span>',
            'deleted'  => '<span class="badge badge-danger">Konto usunięte</span>',
            'active'   => '<span class="badge badge-success">Aktywne</span>',
	    'blocked'   => '<span class="badge badge-warning">Zablokowane</span>',
        ];
        foreach ($ReasonGetCustomerFsecureService['data'] as &$item) {
            $sid = $item['secur_serviceid'] ?? null;
            $item['secur_servicename'] = $servicesMap[$sid] ?? 'Nieznana usługa';

            $status = $item['status'] ?? '';
            $item['status_readable'] = $statusMap[$status] ?? $status;
        }
        unset($item); // clear reference to avoid side effects
        return $ReasonGetCustomerFsecureService;
    }
    
    public function GetAllCustomerFsecureServices(): array
    {
        $FsercureServices_list = $this->GetFsecureServicesList();
        $ReasonGetCustomerFsecureService = $this->apiInit->ApiGet("/services/security/");
        $servicesMap = [];
        foreach ($FsercureServices_list as $service) {
            $servicesMap[$service['id']] = $service['name'];
        }
        $statusMap = [
            'reserved' => '<span class="badge badge-info">Zarezerwowane, bez aktywacji</span>',
            'new'      => '<span class="badge badge-primary">Oczekuje na potwierdzenie aktywacji</span>',
            'deleted'  => '<span class="badge badge-danger">Konto usunięte</span>',
            'active'   => '<span class="badge badge-success">Aktywne</span>',
	        'blocked'   => '<span class="badge badge-warning">Zablokowane</span>',
        ];
        foreach ($ReasonGetCustomerFsecureService['data'] as &$item) {
            $sid = $item['secur_serviceid'] ?? null;
            $item['secur_servicename'] = $servicesMap[$sid] ?? 'Nieznana usługa';

            $status = $item['status'] ?? '';
            $item['status_readable'] = $statusMap[$status] ?? $status;
        }
        unset($item); // clear reference to avoid side effects
        return $ReasonGetCustomerFsecureService;
    }

    public function GetFsecureOperationList(): array
    {
         return $ReasonGetCustomerMmsById=$this->apiInit->ApiGet("/services/security/operations/");
    }

    public function DeleteCustomerFsecureService(int $fsercure_service_id): array
    {
         return $ReasonGetCustomerMmsById=$this->apiInit->ApiDelete("/services/security/".$fsercure_service_id);
    }

    public function SuspendCustomerFsecureService(int $fsercure_service_id): array
    {
         return $ReasonGetCustomerMmsById=$this->apiInit->ApiPut("/services/security/".$fsercure_service_id."/block");
    }

    public function UnSuspendCustomerFsecureService(int $fsercure_service_id): array
    {
         return $ReasonGetCustomerMmsById=$this->apiInit->ApiPut("/services/security/".$fsercure_service_id."/unblock");
    }
    
}
