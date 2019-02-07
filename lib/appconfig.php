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

namespace OCA\Gantt;

use OCP\IConfig;
use OCP\ILogger;


class AppConfig {

    private $predefGanttUrl = "https://gantt-viewer.web.cern.ch";

    private $appName;
    private $config;
    private $logger;

    private $_ganttUrl = "GanttUrl";

    /* Additional data about accepted formats */
    private $_formats = [
			[ "mime" => "application/xml", "type" => "text" ],
			[ "mime" => "application/mpp", "type" => "project" ],
			[ "mime" => "application/vnd.ms-project", "type" => "project" ],
			[ "mime" => "application/msproj", "type" => "project" ],
			[ "mime" => "application/msproject", "type" => "project" ],
			[ "mime" => "application/x-msproject", "type" => "project" ],
			[ "mime" => "application/x-ms-project", "type" => "project" ],
			[ "mime" => "application/x-dos_ms_project", "type" => "project" ],
			[ "mime" => "application/mpp", "type" => "project" ],
			[ "mime" => "zz-application/zz-winassoc-mpp", "type" => "project" ],
			[ "mime" => "application/octet-stream", "type" => "project" ],
			[ "mime" => "application/json", "type" => "text"]
		];

    public function __construct($AppName)
    {
        $this->appName = $AppName;
        $this->config = \OC::$server->getConfig();
        $this->logger = \OC::$server->getLogger();
    }

    public function GetGanttUrl()
    {
        $val = $this->config->getAppValue($this->appName, $this->_ganttUrl);
        if (empty($val)) $val = $this->predefGanttUrl;
        return $val;
    }
	
	public function GetGanttFormats()
	{
        return $this->_formats;
	}
}