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
 
class MetroportInitHandler
{
    public function lmsInit(LMS $hook_data)
    {
        $db = $hook_data->getDb();
        $auth = $hook_data->getAuth();
        return $hook_data;
    }

    public function smartyInit(Smarty $hook_data)
    {
        $template_dirs = $hook_data->getTemplateDir();
        $plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSMetroportPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'templates';
        array_unshift($template_dirs, $plugin_templates);
        $hook_data->setTemplateDir($template_dirs);
        return $hook_data;
    }

    public function modulesDirInit(array $hook_data = array())
    {
        $plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSMetroportPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'modules';
	    array_unshift($hook_data, $plugin_modules);
        return $hook_data;
    }
    
    public function customerinfo_BeforeDisplay(array $hook_data)
    {
        return $hook_data;
    }

    public function nodeedit_after_submit(array $hook_data)
    {
        return $hook_data;
    }

    public function menuInit(array $hook_data = array())
    {
        $menu_genieacs = array(
            'Metroport' => array(
                'name' => 'Metroport',
                'css' => 'fa-solid fa-m',
                'tip' => 'Obsługa usług Metroport',
                'accesskey' =>'t',
                'prio' => 12,
                'submenu' => array(
                    array(
                        'name' => trans('Lista klientów'),
                        'link' => '?m=metroportclientlist',
                        'tip' => trans('Pobiera listę klientów z systemu MMS'),
                        'prio' => 10,
                    ),

                    'metroport-menu-break-4' => array(
                        'name' => '------------',
                        'prio' => 110,
                    ),
                    array(
                        'name' => trans('Lista pakietów F-SECURE'),
                        'link' => '?m=metroportfsecureservicelist',
                        'tip' => trans('Pobiera listę pakietów F-SECURE z systemu MMS które można wydać abonentom.'),
                        'prio' => 10,
                    ),
		            array(
                        'name' => trans('Lista klientów z F-SECURE'),
                        'link' => '?m=metroportfsecureclientslist',
                        'tip' => trans('Pobiera listę klientów z usługą F-SECURE z systemu MMS.'),
                        'prio' => 10,
                    ),
		            array(
                        'name' => trans('Lista operacji F-SECURE'),
                        'link' => '?m=metroportfsecureoperationlist',
                        'tip' => trans('Pobiera listę operacji związanhych z usługą F-SECURE z systemu MMS.'),
                        'prio' => 10,
                    ),
                    'metroport-menu-break-5' => array(
                        'name' => '------------',
                        'prio' => 111,
                    ),
		            array(
                        'name' => trans('Lista pakietów MetroTV'),
                        'link' => '?m=metroportmetrotvservicelist',
                        'tip' => trans('Pobiera listę pakietów MetroTV z systemu MMS które można wydać abonentom.'),
                        'prio' => 10,
                    ),
		            array(
                        'name' => trans('Lista podsieci MetroTV'),
                        'link' => '?m=metroportmetrotvnetworklist',
                        'tip' => trans('Pobiera listę podsieci MetroTV z systemu MMS.'),
                        'prio' => 10,
                    ),
		            array(
                        'name' => trans('Lista modeli STB MetroTV'),
                        'link' => '?m=metroportmetrotvstbmodellist',
                        'tip' => trans('Pobiera listę wspieranych modeli STB MetroTV z systemu MMS.'),
                        'prio' => 10,
                    ),
		            array(
                        'name' => trans('Lista STB MetroTV'),
                        'link' => '?m=metroportmetrotvstblist',
                        'tip' => trans('Pobiera listę STB MetroTV wydanych/w magazynie/skasowanych/zarezerowanych  z systemu MMS.'),
                        'prio' => 10,
                    ),
		            array(
                        'name' => trans('Lista kont z MetroTV'),
                        'link' => '?m=metroportmetrotvclientslist',
                        'tip' => trans('Pobiera listę klientów z usługą MetroTV z systemu MMS.'),
                        'prio' => 10,
                    ),		    'metroport-menu-break-6' => array(
                        'name' => '------------',
                        'prio' => 112,
                    ),
                    array(
                        'name' => trans('Test połączenia'),
                        'link' => '?m=metroportconnectiontest',
                        'tip' => trans('Test połączenia z API METROPORT'),
                        'prio' => 10,
                    ),
                ),
            ),
        );
        $menu_keys = array_keys($hook_data);
        $i = array_search('VoIP', $menu_keys);
        return array_slice($hook_data, 0, $i, true) + $menu_genieacs + array_slice($hook_data, $i, null, true);
    }

    public function accessTableInit()
    {
        $access = AccessRights::getInstance();
            // --- Odczyt listy klientów ---
        $permission = new Permission(
                'metroport_readonly',
                trans('METROPORT - MMS - Dostęp do listy klientów i usług'),
                '^(metroport(clientlist|customer_ajax|fsecureservicecustomer))$',
                null,
                array('Metroport' => Permission::MENU_ALL)
        );
        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        // --- konkretne uprawnienia (dla ról ograniczonych) ---
        $map = [
	        'metroportconnectiontest'		    => 'MMS - Wykonywanie testu połączenia do MMS',  
	        'metroportcustomerupdate_ajax'	    => 'MMS - Dodawanie/Edycja danych konta klienta w MMS',
            'metroportfsecureoperationlist'	    => 'F-SECURE - Wyświetlanie listy operacji',
            'metroportfsecureservicelist'       => 'F-SECURE - Wyświetlanie listy usług',
            'metroportfsecureclientslist'       => 'F-SECURE - Lista wydanych usług F-SECURE',
            'metroportfsecureservicesaction'	=> 'F-SECURE - Wykonywanie zadań edycja/zawieszanie/odwieszanie',
          ];

        foreach ($map as $m => $label) {
            $permission = new Permission(
                "{$m}",
                trans('METROPORT - ' . $label),
                '^' . $m . '$',
                null,
                array('Metroport' => Permission::MENU_ALL)
            );

            $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);
        }
    }    
}