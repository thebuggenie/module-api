<?php

namespace thebuggenie\modules\api\controllers;

use thebuggenie\core\framework,
    thebuggenie\core\entities,
    thebuggenie\core\entities\tables;

/**
 * Base controller class for actions in the api module
 */
class ApiController extends framework\Action
{

	protected static $_ver_api_mj = 1;
	protected static $_ver_api_mn = 0;
	protected static $_ver_api_rev = 0;

	public function getApiVersion($with_revision = true)
	{
		$retvar = self::$_ver_api_mj . '.' . self::$_ver_api_mn;
		if ($with_revision) $retvar .= (is_numeric(self::$_ver_api_rev)) ? '.' . self::$_ver_api_rev : self::$_ver_api_rev;
		return $retvar;
	}
	
	public function getApiMajorVer()
	{
		return self::$_ver_api_mj;
	}
	
	public function getApiMinorVer()
	{
		return self::$_ver_api_mn;
	}
	
	public function getApiRevision()
	{
		return self::$_ver_api_rev;
	}
	
    public function getAuthenticationMethodForAction($action)
    {
        switch ($action)
        {
            case 'authenticate':
                return framework\Action::AUTHENTICATION_METHOD_DUMMY;
                break;
            default:
                return framework\Action::AUTHENTICATION_METHOD_APPLICATION_PASSWORD;
                break;
        }
    }

    /**
     * The currently selected project in actions where there is one
     *
     * @access protected
     * @property entities\Project $selected_project
     */
    public function preExecute(framework\Request $request, $action)
    {
        try
        {
            // Default to JSON if nothing is specified.
            $newFormat = $request->getParameter('format', 'json');
            $this->getResponse()->setTemplate(mb_strtolower($action) . '.' . $newFormat . '.php');
            $this->getResponse()->setupResponseContentType($newFormat);

            if ($this->getRouting()->getCurrentRouteName() != 'api_authenticate' && framework\Context::getUser()->isGuest()) {
                $this->getResponse()->setHttpStatus(401);
                return $this->renderJSON(array('error' => "Invalid credentials"));
            }
        }
        catch (\Exception $e)
        {
            $this->getResponse()->setHttpStatus(500);
            return $this->renderJSON(array('error' => 'An exception occurred: ' . $e));
        }
    }

}
