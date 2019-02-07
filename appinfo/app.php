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

use OCP\App;

$app = new Application();

$domains = \OC::$server->getConfig()->getSystemValue("gantt.domains", ["https://gantt-viewer.web.cern.ch", "https://svcdhtmlx.cern.ch"]);
$policy = new \OCP\AppFramework\Http\EmptyContentSecurityPolicy();
foreach($domains as $domain) {
       $policy->addAllowedScriptDomain($domain);
       $policy->addAllowedFrameDomain($domain);
       $policy->addAllowedConnectDomain($domain);
}
\OC::$server->getContentSecurityPolicyManager()->addDefaultPolicy($policy);
