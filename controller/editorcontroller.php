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

namespace OCA\Gantt\Controller;

use OCP\App;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Controller;
use OCP\AutoloadNotAllowedException;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

use OC\Files\Filesystem;
use OC\Files\View;
use OC\User\NoUserException;

use OCA\Files\Helper;
use OCA\Files_Versions\Storage;

use OCA\Gantt\AppConfig;


class EditorController extends Controller
{

    private $userSession;
    private $root;
    private $urlGenerator;
    private $logger;
    private $config;


    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IRootFolder $root - root folder
     * @param IUserSession $userSession - current user session
     * @param IURLGenerator $urlGenerator - url generator service
     * @param ILogger $logger - logger
     * @param OCA\Gantt\AppConfig $config - app config
     */
    public function __construct($AppName,
                                IRequest $request,
                                IRootFolder $root,
                                IUserSession $userSession,
                                IURLGenerator $urlGenerator,
                                ILogger $logger,
                                AppConfig $config
                                )
    {
        parent::__construct($AppName, $request);

        $this->userSession = $userSession;
        $this->root = $root;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * This comment is very important, CSRF fails without it
     *
     * @param integer $fileId - file identifier
     *
     * @return TemplateResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index($fileId) {
        $ganttUrl = $this->config->GetGanttUrl();

        if (empty($ganttUrl))
        {
            $this->logger->error("ganttUrl is empty", array("app" => $this->appName));
            return ["error" => t("Gantt app not configured! Please contact admin.")];
        }

        $ganttUrlArray = explode("?",$ganttUrl);

        if (count($ganttUrlArray) > 1){
            $ganttUrl = $ganttUrlArray[0];
            $ganttUrlArgs = $ganttUrlArray[1];
        } else {
            $ganttUrlArgs = "";
        }

        list ($file, $error) = $this->getFile($fileId);

        if (isset($error))
        {
            $this->logger->error("Load: " . $fileId . " " . $error, array("app" => $this->appName));
            return ["error" => $error];
        }

        $uid = $this->userSession->getUser()->getUID();
        $baseFolder = $this->root->getUserFolder($uid);

        $params = [
            "ganttUrl" => $ganttUrl,
            "ganttUrlArgs" => $ganttUrlArgs,
            "ganttFilePath" => $baseFolder->getRelativePath($file->getPath())
        ];

        $response = new TemplateResponse($this->appName, "editor", $params);

        $csp = new ContentSecurityPolicy();
        $csp->allowInlineScript(true);

        if (isset($ganttUrl) && !empty($ganttUrl))
        {
            $csp->addAllowedScriptDomain($ganttUrl);
            $csp->addAllowedFrameDomain($ganttUrl);
            $csp->addAllowedFrameDomain("blob:");
            $csp->addAllowedChildSrcDomain($ganttUrl);
            $csp->addAllowedChildSrcDomain("blob:");
        }
        $response->setContentSecurityPolicy($csp);

        return $response;
    }

	/**
     * @NoAdminRequired
     */
    private function getFile($fileId)
    {
        if (empty($fileId))
        {
            return [null, t("FileId is empty")];
        }

        $files = $this->root->getById($fileId);
        if (empty($files))
        {
            return [null, t("File not found")];
        }
        $file = $files[0];

        if (!$file->isReadable())
        {
            return [null, t("You do not have enough permissions to view the file")];
        }
        return [$file, null];
    }

	/** 
	 * Return Gantt chart files formats (mimetypes) - RESTAPI
	 * 
	 * @NoAdminRequired
     */
	public function ganttformats() 
	{
		return $this->config->GetGanttFormats();
	}
}