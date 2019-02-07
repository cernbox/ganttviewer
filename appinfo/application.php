<?php

/**
 * CERN IT-CDA-AD 01/02/2019
 * Piotr Jan Seweryn
 * CERNBox integration application for Gantt Chart Viewer
 * This software is covered by Affero General Public License version 3: ../license.txt
 *
 * This software was based on the work available at https://github.com/pawelrojek/nextcloud-drawio
 *
 **/

namespace OCA\Gantt\AppInfo;

use OCP\AppFramework\App;
use OCP\Util;

use OCA\Gantt\AppConfig;
use OCA\Gantt\Controller\EditorController;

class Application extends App {

    public $appConfig;

    public function __construct(array $urlParams = [])
    {
        $appName = "gantt";

        parent::__construct($appName, $urlParams);

        $this->appConfig = new AppConfig($appName);

        if (!empty($this->appConfig->GetGanttUrl()) && array_key_exists("REQUEST_URI", \OC::$server->getRequest()->server))
        {
            $url = \OC::$server->getRequest()->server["REQUEST_URI"];

            if (isset($url)) {
                if (preg_match("%/apps/files(/.*)?%", $url)) {
                    Util::addScript($appName, "main");
                    Util::addStyle($appName, "main");
                    Util::addScript($appName, "editor");
                    Util::addStyle($appName, "editor");
                }
            }
        }

        $container = $this->getContainer();

        $container->registerService("RootStorage", function($c)
        {
            return $c->query("ServerContainer")->getRootFolder();
        });

        $container->registerService("UserSession", function($c)
        {
            return $c->query("ServerContainer")->getUserSession();
        });

        $container->registerService("Logger", function($c)
        {
            return $c->query("ServerContainer")->getLogger();
        });

        $container->registerService("EditorController", function($c)
        {
            return new EditorController(
                $c->query("AppName"),
                $c->query("Request"),
                $c->query("RootStorage"),
                $c->query("UserSession"),
                $c->query("ServerContainer")->getURLGenerator(),
                $c->query("Logger"),
                $this->appConfig
            );
        });
    }
}
