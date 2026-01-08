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

class GlobalMetroport
{
    private ApiInit $apiInit;
    private Client $client;
    private string $baseUrl;
    private $DB;	
    public function __construct(ApiInit $apiInit)
    {
        $this->apiInit = $apiInit;
	    $this->DB = \LMSDB::getInstance();
        $this->LMS = \LMS::getInstance();;
        $this->client = new Client([
            'timeout'     => 10,
            'http_errors' => true,
            'verify'      => true,
            'headers'     => [
                'Accept'     => 'application/json',
                'User-Agent' => 'LMSMetroportPlugin/1.0',
            ],
        ]);

        // Get base URL from configuration
        $baseUrl = trim(\ConfigHelper::getConfig('metroport.serwer_url', ''));
        if (empty($baseUrl)) {
            throw new \Exception('Brak konfiguracji serwera Metroport.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
    }

    function TimestampToDate($ts, $tz = 'Europe/Warsaw') {
	if (!$ts) {
	    return '-';
	}

	$date = new \DateTime("@$ts"); // globalna klasa DateTime
	$date->setTimezone(new \DateTimeZone($tz));

	return $date->format('Y-m-d H:i');
    }

    public function GetClientsList(): array
    {
        // Fetch sess_ci token from ApiInit
        $token = $this->apiInit->getToken();

        // Full URL to the F-Secure endpoint
        $url = $this->baseUrl . '/Users/users';

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

            $msg = "Błąd połączenia z API Metroport (Client list): $status $reason";
            if (!empty($body)) {
                $msg .= "\nTreść odpowiedzi:\n$body";
            }

            throw new \Exception($msg);
        }
    }
    
    public function CheckMetroportCustomerExistByPesel($pesel)
    {
        return $ReasonCheckClientInMMS=$this->apiInit->ApiGet("/Users/users?pesel=".$pesel);
    }
    
    public function CheckMetroportCustomerExistByNip($nip)
    {
        return $ReasonCheckClientInMMS=$this->apiInit->ApiGet("/Users/users?nip=".$nip);
    }
    
    public function GetCustomerMmsByID($id)
    {
        return $ReasonGetCustomerMmsById=$this->apiInit->ApiGet("/Users/users/".$id);
    }

    public function GetCustomerLmsIdByIdMms($idmms)
    {
        return $this->DB->GetOne('SELECT lms_user_id FROM metroport_customers WHERE metroport_user_id=?', array($idmms));
    }
    
    public function CheckMetroportCustomerExistInLMS($customerId)
    {
	    return $this->DB->GetRow('SELECT metroport_user_id FROM metroport_customers WHERE lms_user_id = ?',array($customerId));
    }

    public function formatNip($nip) {
        // Remove everything except digits
        $nip = preg_replace('/\D/', '', $nip);

        // If NIP does not have 10 digits, return empty
        if (strlen($nip) != 10) return '';

        // Wstaw kreski: 3-3-2-2
        return substr($nip,0,3) . '-' . substr($nip,3,3) . '-' . substr($nip,6,2) . '-' . substr($nip,8,2);
    }
}
