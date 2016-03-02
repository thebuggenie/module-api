<?php

namespace thebuggenie\modules\api\controllers;

use thebuggenie\core\framework,
    thebuggenie\core\entities,
    thebuggenie\core\entities\tables;

/**
 * actions for the api module
 */
class Main extends framework\Action
{

	protected static $_ver_api_mj = 1;
	protected static $_ver_api_mn = 0;
	protected static $_ver_api_rev = 0;

	/**
	 * Decides whether to render details in single-entity endpoints.
	 * (Set to false if the parameter "nodetail" is present in the request)
	 * 
	 * @var boolean $render_detail
	 */
	protected $render_detail = true;
	
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
            
            if ($project_key = $request['project_key'])
                $this->selected_project = entities\Project::getByKey($project_key);
            elseif ($project_id = (int) $request['project_id'])
                $this->selected_project = entities\Project::getB2DBTable()->selectByID($project_id);

            if ($this->selected_project instanceof entities\Project)
                framework\Context::setCurrentProject($this->selected_project);
            
            $this->render_detail = !isset($request['nodetail']);
        }
        catch (\Exception $e)
        {
            $this->getResponse()->setHttpStatus(500);
            return $this->renderJSON(array('error' => 'An exception occurred: ' . $e));
        }
    }

    /**
     * Authenticate an application using a one-time application password.
     * Creates a token to be used for subsequent requests.
     * 
     * @param framework\Request $request
     */
    public function runAuthenticate(framework\Request $request)
    {
        $username = trim($request['username']);
        $password = trim($request['password']);
        if ($username)
        {
            $user = tables\Users::getTable()->getByUsername($username);
            if ($password && $user instanceof entities\User)
            {
                foreach ($user->getApplicationPasswords() as $app_password)
                {
                    if (!$app_password->isUsed())
                    {
                        if ($app_password->getHashPassword() == entities\User::hashPassword($password, $user->getSalt()))
                        {
                            $app_password->useOnce();
                            $app_password->save();
                            return $this->renderJSON(array('token' => $app_password->getHashPassword()));
                        }
                    }
                }
            }
        }

        $this->getResponse()->setHttpStatus(400);
        return $this->renderJSON(array('error' => 'Incorrect username or application password'));
    }

    public function runStatus(framework\Request $request)
    {
        $status_info = array(
            'api_version' => $this->getApiVersion(),
            'tgb_version' => framework\Settings::getVersion(),
            'tgb_version_long' => framework\Settings::getVersion(true, true),
            'tbg_name' => framework\Settings::getSiteHeaderName(),
            'tbg_url_host' => framework\Settings::getURLhost(),
            'tbg_url' => (framework\Settings::getHeaderLink() == '') ? framework\Context::getWebroot() : framework\Settings::getHeaderLink(),
            'tbg_logo_url' => framework\Settings::getHeaderIconURL(),
            'tbg_icon_url' => framework\Settings::getFaviconURL(),
            'online' => (! (bool)framework\Settings::isMaintenanceModeEnabled() )
            );
        if(framework\Settings::hasMaintenanceMessage()) {
            $status_info['maintenance_msg'] = framework\Settings::getMaintenanceMessage();
        }

        $this->status_info = $status_info;
    }
    
    public function runMe(framework\Request $request) {
        $this->users = array(framework\Context::getUser()->toJSON($this->render_detail));
    }

    public function runListProjects(framework\Request $request)
    {
        $projects = framework\Context::getUser()->getAssociatedProjects();

        $return_array = array();
        foreach ($projects as $project)
        {
            if ($project->isDeleted()) continue;
            $return_array[] = $project->toJSON(false);
        }

        $this->projects = $return_array;
    }
    
    public function runProject(framework\Request $request) {
        // Project is already selected in preExecute, so we just display it
        if($this->selected_project instanceof entities\Project) {
            $this->projects = array($this->selected_project->toJSON($this->render_detail));
        } else {
            $this->getResponse()->setHttpStatus(404);
            return $this->renderJSON(array('error' => 'Project not found'));
        }
    }

    public function runListIssuefields(framework\Request $request)
    {
        try
        {
            $issuetype = entities\Issuetype::getByKeyish($request['issuetype']);

            if ($issuetype instanceof entities\common\Identifiable)
            {
                $issuefields = $this->selected_project->getVisibleFieldsArray($issuetype->getID());
            }
            else
            {
                $issuefields = array();
            }
        }
        catch (\Exception $e)
        {
            $this->getResponse()->setHttpStatus(400);
            return $this->renderJSON(array('error' => 'An exception occurred: ' . $e));
        }

        $this->issuefields = array_keys($issuefields);
    }

    public function runListIssuetypes(framework\Request $request)
    {
        $issuetypes = entities\Issuetype::getAll();

        $return_array = array();
        foreach ($issuetypes as $issuetype)
        {
            $return_array[] = $issuetype->toJSON(true);
        }

        $this->issuetypes = $return_array;
    }

    public function runListFieldvalues(framework\Request $request)
    {
        $field_key = $request['field_key'];
        $return_array = array('description' => null, 'type' => null, 'choices' => null);
        if ($field_key == 'title' || in_array($field_key, entities\DatatypeBase::getAvailableFields(true)) || $field_key == 'activitytype')
        {
            switch ($field_key)
            {
                case 'title':
                    $return_array['description'] = framework\Context::getI18n()->__('Single line text input without formatting');
                    $return_array['type'] = 'single_line_input';
                    break;
                case 'description':
                case 'reproduction_steps':
                    $return_array['description'] = framework\Context::getI18n()->__('Text input with wiki formatting capabilities');
                    $return_array['type'] = 'wiki_input';
                    break;
                case 'status':
                case 'resolution':
                case 'reproducability':
                case 'priority':
                case 'severity':
                case 'category':
                    $return_array['description'] = framework\Context::getI18n()->__('Choose one of the available values');
                    $return_array['type'] = 'choice';

                    $classname = "\\thebuggenie\\core\\entities\\" . ucfirst($field_key);
                    $choices = $classname::getAll();
                    foreach ($choices as $choice_key => $choice)
                    {
                        $return_array['choices'][] = $choice->toJSON(true);
                    }
                    break;
                case 'activitytype':
                    $return_array['description'] = framework\Context::getI18n()->__('Choose one of the available values');
                    $return_array['type'] = 'choice';

                    $classname = "\\thebuggenie\\core\\entities\\ActivityType";
                    $choices = $classname::getAll();
                    foreach ($choices as $choice_key => $choice)
                    {
                        $return_array['choices'][] = $choice->toJSON(true);
                    }
                    break;
                case 'percent_complete':
                    $return_array['description'] = framework\Context::getI18n()->__('Value of percentage completed');
                    $return_array['type'] = 'choice';
                    $return_array['choices'][] = "1-100%"; //TODO: That does not seem useful...
                    break;
                case 'owner':
                case 'assignee':
                    $return_array['description'] = framework\Context::getI18n()->__('Select an existing user or <none>');
                    $return_array['type'] = 'select_user';
                    break;
                case 'estimated_time':
                case 'spent_time':
                    $return_array['description'] = framework\Context::getI18n()->__('Enter time, such as points, hours, minutes, etc or <none>');
                    $return_array['type'] = 'time';
                    break;
                case 'milestone':
                    $return_array['description'] = framework\Context::getI18n()->__('Select from available project milestones');
                    $return_array['type'] = 'choice';
                    if ($this->selected_project instanceof entities\Project)
                    {
                        $milestones = $this->selected_project->getAvailableMilestones();
                        foreach ($milestones as $milestone)
                        {
                            $return_array['choices'][] = $milestone->toJSON(false);// array('key' => $milestone->getID(), 'name' => $milestone->getName());
                        }
                    }
                    break;
            }
        }
        else
        {

        }

        $this->field_info = $return_array;
    }

    public function runIssueEditTimeSpent(framework\Request $request)
    {
        try
        {
            $entry_id = $request['entry_id'];
            $spenttime = ($entry_id) ? tables\IssueSpentTimes::getTable()->selectById($entry_id) : new entities\IssueSpentTime();

            if ($issue_id = $request['issue_id'])
            {
                $issue = entities\Issue::getB2DBTable()->selectById($issue_id);
            }
            else
            {
                throw new \Exception('no issue');
            }

            framework\Context::loadLibrary('common');
            $spenttime->editOrAdd($issue, $this->getUser(), array_only_with_default($request->getParameters(), array_merge(array('timespent_manual', 'timespent_specified_type', 'timespent_specified_value', 'timespent_activitytype', 'timespent_comment', 'edited_at'), \thebuggenie\core\entities\common\Timeable::getUnitsWithPoints())));
        }
        catch (\Exception $e)
        {
            $this->getResponse()->setHttpStatus(400);
            return $this->renderJSON(array('edited' => 'error', 'error' => $e->getMessage()));
        }

        $this->return_data = array('edited' => 'ok');
    }

}
