<?php

class MetroportNotificationHandler
{
    

    public function getSupportedActions(array $hook_data)
    {
        $hook_data['supported_actions']['metrotv-locks'] = array(
            'params' => ACTION_PARAM_NONE,
        );
          $hook_data['supported_actions']['metrofsecure-locks'] = array(
            'params' => ACTION_PARAM_NONE,
        );
        return $hook_data;
    }
    private function toggleCustomerSuspendBillingTV($customerid, $suspend)
    {
        $this->metrotv_suspend_billing = trim(ConfigHelper::getConfig('metroport.metrotv_suspend_billing', ''));
        $args = array(
            'metrotv_suspend' => $suspend,
            SYSLOG::RES_CUST => $customerid,
        );
        
        if ($this->metrotv_suspend_billing==1)
        {
            LMSDB::getInstance()->Execute('UPDATE metroport_customers SET metrotv_suspend_billing = ? WHERE lms_user_id = ?', array_values($args));
        }

        LMSDB::getInstance()->Execute('UPDATE metroport_customers SET metrotv_suspend = ? WHERE lms_user_id = ?', array_values($args));
        if ($SYSLOG = SYSLOG::getInstance()) {
            $SYSLOG->NewTransaction('lms-notify.php');
            $SYSLOG->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_UPDATE, $args);
       }
    }

    private function toggleCustomerSuspendBillingFsecure($customerid, $suspend)
    {
        $this->fsecure_suspend_billing = trim(ConfigHelper::getConfig('metroport.fsecure_suspend_billing', ''));
        $args = array(
            'metro_fsecure_suspend' => $suspend,
            SYSLOG::RES_CUST => $customerid,
        );
        
        if ($this->fsecure_suspend_billing==1)
        {
            LMSDB::getInstance()->Execute('UPDATE metroport_customers SET metro_fsecure_suspend_billing = ? WHERE lms_user_id = ?', array_values($args));
        }

        LMSDB::getInstance()->Execute('UPDATE metroport_customers SET metro_fsecure_suspend = ? WHERE lms_user_id = ?', array_values($args));
        if ($SYSLOG = SYSLOG::getInstance()) {
            $SYSLOG->NewTransaction('lms-notify.php');
            $SYSLOG->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_UPDATE, $args);
       }
    }

    public function notificationBlock(array $hook_data)
    {
        $customers = $hook_data['customers'];
        $actions = $hook_data['actions'];
        $quiet = $hook_data['quiet'];
        $debug = $hook_data['debug'];

        if (empty($customers) || empty($actions)) {
            return $hook_data;
        }
        
        if (!isset($actions['metrotv-locks']) && !isset($actions['metrofsecure-locks'])) {
            return $hook_data;
        }

        // Inicjalizacja API - w kontekście CLI nie ma zalogowanego użytkownika
        try {
            $apiInit = new \LMSMetroportPlugin\ApiInit();
            $apiInit->getToken(); // Pobranie tokena
        } catch (\Exception $e) {
            error_log('MetroportNotificationHandler: Błąd inicjalizacji API: ' . $e->getMessage());
            return $hook_data;
        }
        if (isset($actions['metrotv-locks']))
        {  
            $MetroTV = new \LMSMetroportPlugin\MetroTV($apiInit);
            foreach ($customers as $customerid) {
                try {
                    $accounts = $MetroTV->GetAccountsIdMetroTvByLMSUserId($customerid);
                    if (!empty($accounts)) {
                        foreach ($accounts as $accountId) {                      
                            $data = [
                                'accountid' => $accountId
                            ];

                            $ReasonSuspendMetroTV=$apiInit->ApiPut("/Iptv/Accounts/".$accountId."/blockAccount",$data);
                            $this->toggleCustomerSuspendBillingTV($customerid, 1);
                            echo "METROTV BLOKADA - Klient LMS: $customerid, Konto MetroTV: $accountId, Odpowiedź MetroportAPI: " . json_encode($ReasonSuspendMetroTV)."\n";
                        }
                    }
                } catch (\Exception $e) {
                    error_log("MetroportNotificationHandler: Błąd dla klienta $customerid: " . $e->getMessage());
                }
            }
        }
        if (isset($actions['metrofsecure-locks']))
        {  
            $fsecure = new \LMSMetroportPlugin\Fsecure($apiInit);
            foreach ($customers as $customerid) {
                try {
                    $accounts = $fsecure->GetCustomerFsecureServicesByIdLMS($customerid);
                    if (!empty($accounts['data'])) {
                        foreach ($accounts['data'] as $account) {
                            $accountId = $account['id'];
                            $ReasonSuspendMetroFsecure=$fsecure->SuspendCustomerFsecureService($accountId);
                            
                            if (!empty($ReasonSuspendMetroFsecure['success']) && $ReasonSuspendMetroFsecure['success'] == 1) {
                                $this->toggleCustomerSuspendBillingFsecure($customerid, 1);
                                echo "METRO-FSECURE BLOKADA - Klient LMS: $customerid, Konto MetroFsecure: $accountId, Odpowiedź MetroportAPI: " . json_encode($ReasonSuspendMetroFsecure)."\n";
                            } else {
                                error_log("MetroportNotificationHandler: Błąd blokady F-Secure dla klienta $customerid: " . json_encode($ReasonSuspendMetroFsecure));
                            }

                        }
                    }
                
                }
                    catch (\Exception $e) {
                    error_log("MetroportNotificationHandler: Błąd dla klienta $customerid: " . $e->getMessage());
                }
            }
        }
        return $hook_data;
    }

    public function notificationUnblock(array $hook_data)
    {
        $customers = $hook_data['customers'];
        $actions = $hook_data['actions'];
        $quiet = $hook_data['quiet'];
        $debug = $hook_data['debug'];
        
        if (empty($customers) || empty($actions)) {
            return $hook_data;
        }
        
        if (!isset($actions['metrotv-locks']) && !isset($actions['metrofsecure-locks'])) {
            return $hook_data;
        }

        // Inicjalizacja API - w kontekście CLI nie ma zalogowanego użytkownika
        try {
            $apiInit = new \LMSMetroportPlugin\ApiInit();
            $apiInit->getToken(); // Pobranie tokena
        } catch (\Exception $e) {
            error_log('MetroportNotificationHandler: Błąd inicjalizacji API: ' . $e->getMessage());
            return $hook_data;
        }

        if (isset($actions['metrotv-locks']))
        {  
            $MetroTV = new \LMSMetroportPlugin\MetroTV($apiInit);
            
            foreach ($customers as $customerid) {
                try {
                    $accounts = $MetroTV->GetAccountsIdMetroTvByLMSUserId($customerid);
                    if (!empty($accounts)) {
                        foreach ($accounts as $accountId) {                      
                            $data = [
                                'accountid' => $accountId
                            ];

                            $ReasonSuspendMetroTV=$apiInit->ApiPut("/Iptv/Accounts/".$accountId."/unblockAccount",$data);
                            $this->toggleCustomerSuspendBillingTV($customerid, 0);
                            echo "METROTV ODBLOKOWANO - Klient LMS: $customerid, Konto MetroTV: $accountId, Odpowiedź MetroportAPI: " . json_encode($ReasonSuspendMetroTV)."\n";
                        }
                    }
                } catch (\Exception $e) {
                    error_log("MetroportNotificationHandler: Błąd dla klienta $customerid: " . $e->getMessage());
                }
            }
        }

        if (isset($actions['metrofsecure-locks']))
        {  
            $fsecure = new \LMSMetroportPlugin\Fsecure($apiInit);
            foreach ($customers as $customerid) {
                try {
                    $accounts = $fsecure->GetCustomerFsecureServicesByIdLMS($customerid);
                    if (!empty($accounts['data'])) {
                        foreach ($accounts['data'] as $account) {
                            $accountId = $account['id'];
                            $ReasonSuspendMetroFsecure=$fsecure->UnSuspendCustomerFsecureService($accountId);
                            
                            if (!empty($ReasonSuspendMetroFsecure['success']) && $ReasonSuspendMetroFsecure['success'] == 1) {
                                $this->toggleCustomerSuspendBillingFsecure($customerid,0);
                                echo "METRO-FSECURE ODBLOKOWANO - Klient LMS: $customerid, Konto MetroFsecure: $accountId, Odpowiedź MetroportAPI: " . json_encode($ReasonSuspendMetroFsecure)."\n";
                            } else {
                                error_log("MetroportNotificationHandler: Błąd blokady F-Secure dla klienta $customerid: " . json_encode($ReasonSuspendMetroFsecure));
                            }

                        }
                    }
                
                }
                    catch (\Exception $e) {
                    error_log("MetroportNotificationHandler: Błąd dla klienta $customerid: " . $e->getMessage());
                }
            }
        }
        return $hook_data;
    }
}