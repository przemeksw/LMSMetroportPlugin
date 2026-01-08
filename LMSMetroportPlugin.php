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


require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/ApiInit.class.php';
require_once __DIR__ . '/lib/GlobalMetroport.class.php';
require_once __DIR__ . '/lib/fsecure.class.php';

use LMSMetroportPlugin\ApiInit;
use LMSMetroportPlugin\GlobalMetroport;
use LMSMetroportPlugin\MetroTV;

class LMSMetroportPlugin extends LMSPlugin
{
    const PLUGIN_DIRECTORY_NAME = 'LMSMetroportPlugin';
    const PLUGIN_DB_VERSION = '2025122900';
    const PLUGIN_NAME = 'LMSMetroportPlugin';
    const PLUGIN_ALIAS = 'LMSMetroportPlugin';
    const PLUGIN_DESCRIPTION = 'Obsługa Metroport';
    const PLUGIN_DOC_URL = 'https://viphost.it';
    const PLUGIN_AUTHOR = 'Przemysław Świderski &lt;biuro@viphost.it&gt';
    const PLUGIN_SOFTWARE_VERSION = '1.0.0';

    private static ?ApiInit $apiInit = null;
    private static $metroport = null;
    private static $metrotv = null; 
    private static $globalmetro = null; 
    private static $fsecure = null; 


    public function __construct()
    {
        parent::__construct();
	$LMS = \LMS::getInstance();
        // inicjalizacja API
	if ($LMS->AUTH->islogged)
	{
	    try {

		self::$apiInit = new ApiInit();
		// pobranie tokena
		$token = self::$apiInit->getToken();
	    } catch (\Exception $e) {
		// w razie błędu logujemy, ale nie przerywamy działania LMS
		//print $e->getMessage();
		error_log('Błąd inicjalizacji API Metroport: ' . $e->getMessage());
	    }
	}
    }

    /**
     * Zwraca instancję ApiInit
     */
    public static function getApiInitInstance(): ?ApiInit
    {
        return self::$apiInit;
    }

    /**
     * Zwraca instancję klasy Metroport (przyszłe metody do obsługi API)
     */
    public static function getMetroportInstance()
    {
        if (empty(self::$metroport)) {
            // self::$metroport = new METROPORT(); // zakomentowane, jeśli klasa METROPORT nie istnieje jeszcze
        }
        return self::$metroport;
    }
    public static function getFsecureInstance($apiInit)
    {
        if (empty(self::$fsecure)) {
            self::$fsecure = new \LMSMetroportPlugin\Fsecure($apiInit);
        }
        return self::$fsecure;
    }

    public static function getMetroTvInstance($apiInit)
    {
        self::$metrotv = new MetroTv($apiInit); // zakomentowane, jeśli klasa METROPORT nie istnieje jeszcze
        return self::$metrotv;
    }

    public static function getMetroGlobalInstance($apiInit)
    {
        self::$globalmetro = new GlobalMetroport($apiInit); // zakomentowane, jeśli klasa METROPORT nie istnieje jeszcze
        return self::$globalmetro;
    }

    public function registerHandlers()
    {
        $this->handlers = [
            'smarty_initialized' => [
                'class' => 'MetroportInitHandler',
                'method' => 'smartyInit',
            ],
            'lms_initialized' => [
                'class' => 'MetroportInitHandler',
                'method' => 'lmsInit',
            ],
            'access_table_initialized' => [
		        'class' => 'MetroportInitHandler',
                'method' => 'accessTableInit'
            ],
            'get_supported_actions' => array(
                'class' => 'MetroportNotificationHandler',
                'method' => 'getSupportedActions',
            ),
            'notification_blocks' => array(
                'class' => 'MetroportNotificationHandler',
                'method' => 'notificationBlock',
            ),        
            'notification_unblocks' => array(
                'class' => 'MetroportNotificationHandler',
                'method' => 'notificationUnblock',
            ),
            'modules_dir_initialized' => [
                'class' => 'MetroportInitHandler',
                'method' => 'modulesDirInit',
            ],
            'customerinfo_before_display' => [
                'class' => 'MetroportCustomerHandler',
                'method' => 'customerinfo_BeforeDisplay',
            ],
            'customeredit_validation_before_submit' => [
                'class' => 'MetroportCustomerHandler',
                'method' => 'customeredit_validation_before_submit',
            ],
            'menu_initialized' => [
                'class' => 'MetroportInitHandler',
                'method' => 'menuInit',
            ],

        ];
    }
}
