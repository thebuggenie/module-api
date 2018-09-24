<?php

namespace thebuggenie\modules\api\controllers;

use thebuggenie\core\framework,
    thebuggenie\core\entities,
    thebuggenie\core\entities\tables;

/**
 * Main actions for the api module
 *
 * @Routes(name_prefix="api_", url_prefix="/api/v1")
 */
class Main extends ApiController
{

    /**
     * Return generic information about this installation
     *
     * @Route(name="info", url="/info")
     *
     * @param framework\Request $request
     */
    public function runInfo(framework\Request $request)
    {
        $information = [
            'api_version' => $this->getApiVersion(),
            'version' => framework\Settings::getVersion(),
            'version_long' => framework\Settings::getVersion(true, true),
            'site_name' => framework\Settings::getSiteHeaderName(),
            'host' => framework\Settings::getURLhost(),
            'urls' => [
                'site' => (framework\Settings::getHeaderLink() == '') ? framework\Context::getWebroot() : framework\Settings::getHeaderLink(),
                'logo' => framework\Settings::getHeaderIconURL(),
                'icon' => framework\Settings::getFaviconURL()
            ],
            'online' => !framework\Settings::isMaintenanceModeEnabled()
        ];
        if (framework\Settings::hasMaintenanceMessage() && !$information['online']) {
            $information['maintenance_message'] = framework\Settings::getMaintenanceMessage();
        }

        $this->information = $information;
    }

}
