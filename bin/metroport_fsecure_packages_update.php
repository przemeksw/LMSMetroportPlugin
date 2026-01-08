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
use LMSMetroportPlugin\Fsecure;
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$parameters = array(
    'config-file:' => 'C:',
    'quiet' => 'q',
    'help' => 'h',
    'version' => 'v',
);

$long_to_shorts = array();
foreach ($parameters as $long => $short) {
    $long = str_replace(':', '', $long);
    if (isset($short)) {
        $short = str_replace(':', '', $short);
    }
    $long_to_shorts[$long] = $short;
}

$options = getopt(
    implode(
        '',
        array_filter(
            array_values($parameters),
            function ($value) {
                return isset($value);
            }
        )
    ),
    array_keys($parameters)
);

foreach (array_flip(array_filter($long_to_shorts, function ($value) {
    return isset($value);
})) as $short => $long) {
    if (array_key_exists($short, $options)) {
        $options[$long] = $options[$short];
        unset($options[$short]);
    }
}
if (array_key_exists('version', $options)) {
    print <<<EOF
metroport_metrotv_packages_update.php
(C) 2001-2024 LMS Developers

EOF;
    exit(0);
}

if (array_key_exists('help', $options)) {
    print <<<EOF
metroport_metrotv_packages_update.php
(C) 2001-2024 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-q, --quiet                     suppress any output, except errors

EOF;
    exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
    print <<<EOF
metroport_metrotv_packages_update.php
(C) 2001-2024 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options)) {
    $CONFIG_FILE = $options['config-file'];
} else {
    $CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';
} 

if (!$quiet) {
    echo "Using file " . $CONFIG_FILE . " as config." . PHP_EOL;
}
    
if (!is_readable($CONFIG_FILE)) {
    die("Unable to read configuration file [" . $CONFIG_FILE . "]!" . PHP_EOL);
}
    
define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);
    
// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More information at https://getcomposer.org/" . PHP_EOL);
}

// Init database

$DB = null;
$apiInit=null;
$MetroportGlobal = null;
$fsecure=null;
try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't work without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

$AUTH = null;
$SYSLOG = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$apiInit = new apiInit();



try {

    // Pobieramy instancję Fsecure z pluginu
    $fsecure = LMSMetroportPlugin::getFsecureInstance($apiInit);
    if (!$fsecure) {
        throw new \Exception('Nie udało się pobrać fsecure.');
    }
    
    // Pobieramy listę usług F-Secure
    $services = $fsecure->GetFsecureServicesList();

    // Spójny komunikat SUCCESS
    $result = ApiInit::Success($services, 'Lista pakietów F-Secure pobrana pomyślnie.');

} catch (\Exception $e) {
    $result = ApiInit::Error('Błąd pobierania listy pakietów F-Secure: ' . $e->getMessage());
    error_log("[MetroportGlobalModule] " . $e->getMessage());
}

// Synchronizacja pakietów F-Secure
if (!empty($result['data'])) {
    foreach ($result['data'] as $service) {
        $package_id = $service['id'];
        
        // Sprawdzenie czy pakiet już istnieje
        $existing = $DB->GetRow('SELECT id FROM metroport_fsecure_packages WHERE id = ?', array($package_id));
        
        if (!empty($existing)) {
            // UPDATE istniejącego wpisu
            $DB->Execute('
                UPDATE metroport_fsecure_packages SET
                    ext_id = ?,
                    name = ?,
                    description = ?,
                    license_size = ?
                WHERE id = ?
            ', array(
                $service['ext_id'],
                $service['name'],
                $service['description'],
                $service['license_size'],
                $package_id
            ));
            
            if (!$quiet) {
                echo "UPDATE - Pakiet F-Secure ID: $package_id (" . $service['name'] . ")" . PHP_EOL;
            }
        } else {
            // INSERT nowego wpisu
            $DB->Execute('
                INSERT INTO metroport_fsecure_packages (
                    id, ext_id, name, description, license_size
                ) VALUES (?, ?, ?, ?, ?)
            ', array(
                $service['id'],
                $service['ext_id'],
                $service['name'],
                $service['description'],
                $service['license_size']
            ));
            
            if (!$quiet) {
                echo "INSERT - Pakiet F-Secure ID: $package_id (" . $service['name'] . ")" . PHP_EOL;
            }
        }
    }
    
    if (!$quiet) {
        echo "Synchronizacja pakietów F-Secure zakończona." . PHP_EOL;
    }
} else {
    if (!$quiet) {
        echo "Brak pakietów F-Secure do synchronizacji." . PHP_EOL;
    }
}


