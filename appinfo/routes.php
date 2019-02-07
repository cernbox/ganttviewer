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
 
return [
    "routes" => [
       ["name" => "editor#index", "url" => "/{fileId}", "verb" => "GET"],
       ["name" => "editor#ganttformats", "url" => "/ajax/ganttformats", "verb" => "GET"],
    ]
];
