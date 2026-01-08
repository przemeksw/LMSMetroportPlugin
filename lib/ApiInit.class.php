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
use ConfigHelper;

class ApiInit
{
    
    private Client $client;
    private $DB;
    private ?string $token = null;       
    private ?int $tokenExpiresAt = null; 
    private string $loginUrl;
    private string $username;
    private string $password;
    private bool $forceRefreshToken = false;

    public function __construct(bool $forceRefreshToken = false)
    {
        $this->DB = \LMSDB::getInstance();
        $this->LMS = \LMS::getInstance();
        $this->forceRefreshToken = $forceRefreshToken;
        $this->baseUrl = trim(\ConfigHelper::getConfig('metroport.serwer_url', ''));

        // Downloading the plugin configuration from the database
        $baseUrl = trim(\ConfigHelper::getConfig('metroport.serwer_url', ''));
        $this->loginUrl = rtrim($baseUrl, '/') . '/Admins/Auth/login';
        $this->username = trim(ConfigHelper::getConfig('metroport.api_login', ''));
        $this->password = trim(ConfigHelper::getConfig('metroport.api_pass', ''));
	    $this->automatically_update_customer_data = trim(ConfigHelper::getConfig('metroport.automatically_update_customer_data', ''));
        $this->token_expiration_time = trim(ConfigHelper::getConfig('metroport.api_token_expiration_time', '12000'));
        if (empty($this->username) || empty($this->password)) {
            throw new \Exception('Brak loginu lub hasła dla API Metroport.');
        }
	
        // If we don't force a new token, we try to load it from the database
        if (!$this->forceRefreshToken) {
            $row = $this->DB->GetRow("SELECT value, updated FROM metroport_settings WHERE name=?", ['token']);
            if ($row) {
                $this->token = $row['value'];
                $this->tokenExpiresAt = strtotime($row['updated']) + $this->token_expiration_time - 30; // 20 minutes minus 30s
            }
        }

        $this->client = new Client([
            'timeout'     => 10,
            'http_errors' => true,
            'verify'      => true,
            'headers'     => [
                'Accept'     => 'application/json',
                'User-Agent' => 'LMSMetroportPlugin/1.0',
            ],
        ]);
    }

    /**
     * Gets API token (incl. dev token = sess_ci)
    */

    public function custom_http_build_query(array $params): string
    {
	$parts = [];
	foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
    	continue; // skip empty values
        }

	    $encoded = '';
	    $chars = mb_str_split($value);

	    foreach ($chars as $char) {
		$ord = mb_ord($char, 'UTF-8');
		if ($ord < 128) {
		    $encoded .= rawurlencode($char);
		} else {
		    $encoded .= sprintf('%%u%04X', $ord);
		}
	    }

	    $parts[] = $key . '=' . $encoded;
	}

	return implode('&', $parts);
    }

    function MetroportHttpStatusMessage(int $code): string
    {
    $messages = [
        // connection/transport status codes
	    100 => 'Continue – Kontynuuj',
	    101 => 'Switching Protocols – Zmiana protokołu',
	    110 => 'Connection Timed Out – Przekroczono czas połączenia',
	    111 => 'Connection refused – Serwer odrzucił połączenie',
        // success codes
	    200 => 'OK – Zawartość żądanego dokumentu',
	    201 => 'Created – Utworzono dokument na serwerze',
	    202 => 'Accepted – Zapytanie przyjęte do obsłużenia',
	    203 => 'Non-Authoritative Information – Informacja nieautorytatywna',
	    204 => 'No content – Brak zawartości',
	    205 => 'Reset Content – Przywróć zawartość',
	    206 => 'Partial Content – Część zawartości',
        // client errors
	    400 => 'Bad Request – Nieprawidłowe zapytanie',
	    401 => 'Unauthorized – Nieautoryzowany dostęp',
	    402 => 'Payment Required – Wymagana opłata',
	    403 => 'Forbidden – Zabroniony',
	    404 => 'Not Found – Nie znaleziono zasobu',
	    405 => 'Method Not Allowed – Niedozwolona metoda',
	    406 => 'Not Acceptable – Niedozwolone',
	    407 => 'Proxy Authentication Required – Wymagane uwierzytelnienie do proxy',
	    408 => 'Request Timeout – Koniec czasu oczekiwania',
	    409 => 'Conflict – Konflikt',
	    410 => 'Gone – Zniknął',
	    411 => 'Length Required – Wymagana długość',
	    412 => 'Precondition Failed – Warunek wstępny nie spełniony',
	    413 => 'Request Entity Too Large – Encja zbyt długa',
	    414 => 'Request-URI Too Long – Adres URI zbyt długi',
	    415 => 'Unsupported Media Type – Nieobsługiwany typ żądania',
	    416 => 'Requested Range Not Satisfiable – Zakres nie do obsłużenia',
	    417 => 'Expectation Failed – Oczekiwana wartość nie do zwrócenia',
	    422 => 'Unprocessable entity – Nie można przetworzyć zapytania',
        // server errors
	    500 => 'Internal Server Error – Wewnętrzny błąd serwera',
	    501 => 'Not Implemented – Nie zaimplementowano',
	    502 => 'Bad Gateway – Błąd bramy',
	    503 => 'Service Unavailable – Usługa niedostępna',
	    504 => 'Gateway Timeout – Przekroczony czas bramy',
	    505 => 'HTTP Version Not Supported – Nieobsługiwana wersja HTTP',
	    507 => 'Insufficient Storage – Brak miejsca',
	    508 => 'Loop Detected – Wykryto nieskończoną pętlę',
	    509 => 'Bandwidth Limit Exceeded – Przekroczony limit transferu',
	    510 => 'Not Extended – Brak wymaganego rozszerzenia',
	    511 => 'Network Authentication Required – Wymagane uwierzytelnienie sieciowe',
	];

	return $messages[$code] ?? "Nieznany kod odpowiedzi: $code";
    }

    public function getToken(): string
    {
	// if we have the token in memory and it is valid, we return it
        if (!$this->forceRefreshToken && $this->token && $this->tokenExpiresAt && $this->tokenExpiresAt > time()) {
            return $this->token;
        }

        try {    
            $response = $this->client->post($this->loginUrl, [
                'body'    => json_encode([
                    'login'    => $this->username,
                    'password' => $this->password,
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                    'User-Agent'   => 'LMSMetroportPlugin/1.0',
                ],
            ]);

            // get the sess_ci cookie from the Set-Cookie headers
            $cookies = $response->getHeader('Set-Cookie');
            $sess_ci = null;
            foreach ($cookies as $cookie) {
                if (preg_match('/sess_ci=([^;]+)/', $cookie, $matches)) {
                    $sess_ci = $matches[1];
                    break;
                }
            }
	    
            if (!$sess_ci) {
                throw new \Exception('Nie udało się pobrać ciasteczka sess_ci z API.');
            }

            $this->token = $sess_ci;
            $this->tokenExpiresAt = time() + $this->token_expiration_time - 30;

            // saving the token to the database only if we do not force a test download
            if (!$this->forceRefreshToken) {
                $this->DB->Execute(
                    "INSERT INTO metroport_settings(name,value,updated) VALUES(?, ?, CURRENT_TIMESTAMP)
                     ON CONFLICT(name) DO UPDATE SET value=?, updated=CURRENT_TIMESTAMP",
                    ['token', $this->token, $this->token]
                );
            }
            return $this->token;
        } catch (RequestException $e) {

            $body   = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'brak statusu';
            $reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

            $msg = "Błąd połączenia z API Metroport: $status $reason ".MetroportHttpStatusMessage($status);
            if (!empty($body)) {
                $msg .= "\nTreść odpowiedzi:\n$body";
            }
            throw new \Exception($msg);
        } catch (\Exception $e) {
            throw new \Exception("Błąd pobierania tokena Metroport: " . $e->getMessage());
        }
    }

    public function getLoginUrl(): string
    {
        return $this->loginUrl;
    }

    public function ApiSendPost($param,$method)
    {
	$token = $this->apiInit->getToken();
    }
    
    public function ApiGet(string $url): array
    {
        $token = $this->getToken();
        $url = $this->baseUrl . $url; 
        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Cookie'     => 'sess_ci=' . $token,
                    'Accept'     => 'application/json',
                    'User-Agent' => 'LMSMetroportPlugin/1.0',
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            if ($data === null) {
                return [
                    'success' => false,
                    'error'   => 'Nie udało się zdekodować odpowiedzi JSON z API ($url).',
                ];
            }

            return [
                'success' => true,
                'data'    => $data,
            ];
        } catch (RequestException $e) {
            $body   = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'brak statusu';
            $reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

            return [
                'success' => false,
                'error'   => "Błąd połączenia z API Metroport: $status $reason",
                'body'    => $body,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
    
    public function ApiPost(string $url, array $params = []): array
    {
        $token = $this->getToken();
        $url = $this->baseUrl . $url;

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Cookie'     => 'sess_ci=' . $token,
                    'Accept'     => 'application/json',
                    'User-Agent' => 'LMSMetroportPlugin/1.0',
                ],
                'json' => $params, // send parameters as JSON
            ]);

            $data = json_decode((string) $response->getBody(), true);
            if ($data === null) {
                return [
                    'success' => false,
                    'error'   => 'Nie udało się zdekodować odpowiedzi JSON z API.',
                ];
            }

            return [
                'success' => true,
                'data'    => $data,
            ];

        } catch (RequestException $e) {
            $body   = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'brak statusu';
            $reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

            return [
                'success' => false,
                'error'   => "Błąd połączenia z API Metroport (Client list): $status $reason",
                'body'    => $body,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function ApiPut(string $url, array $params = []): array
    {
        $token = $this->getToken();
        $url = $this->baseUrl . $url;
        try {
            $response = $this->client->request('PUT', $url, [
                'headers' => [
                    'Cookie'     => 'sess_ci=' . $token,
                    'Accept'     => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'User-Agent' => 'LMSMetroportPlugin/1.0',
                ],
                'form_params' => $params,
            ]);

            $data = json_decode((string) $response->getBody(), true);

            if ($data === null) {
                return [
                    'success' => false,
                    'error'   => 'Nie udało się zdekodować odpowiedzi JSON z API.',
                ];
            }

            return [
                'success' => true,
                'data'    => $data,
            ];

        } catch (RequestException $e) {
            $body   = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'brak statusu';
            $reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

            return [
                'success' => false,
                'error'   => "Błąd połączenia z API Metroport (Client list): $status $reason",
                'body'    => $body,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function ApiDelete(string $url, array $params = []): array
    {
        $token = $this->getToken();
        $url = $this->baseUrl . $url;
	
        try {
            $response = $this->client->request('DELETE', $url, [
                'headers' => [
                    'Cookie'       => 'sess_ci=' . $token,
                    'Accept'       => 'application/json',
                    'User-Agent'   => 'LMSMetroportPlugin/1.0',
                ],
		        'form_params' => $params
            ]);

            $data = json_decode((string) $response->getBody(), true);
	    
            if ($data === null) {
                return [
                    'success' => false,
                    'error'   => 'Nie udało się zdekodować odpowiedzi JSON z API.',
                ];
            }

            return [
                'success' => true,
                'data'    => $data,
            ];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $body   = $e->getResponse() ? (string)$e->getResponse()->getBody() : '';
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'brak statusu';
            $reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

            return [
                'success' => false,
                'error'   => "Błąd połączenia z API Metroport (DELETE): $status $reason",
                'body'    => $body,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Static methods for creating consistent messages
     */
    public static function Success(array $services = [], string $message = 'Operacja zakończona sukcesem'): array
    {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $services
        ];
    }

    public static function Error(string $message): array
    {
        return [
            'status' => 'error',
            'message' => $message,
            'data' => []
        ];
    }
}
