<?php

namespace thebuggenie\modules\api\controllers;

use thebuggenie\core\framework,
    thebuggenie\core\entities;

/**
 * Project-namespaced base-controller for project-namespaced actions for the api module
 *
 * @property entities\Project $selected_project
 */
class ProjectNamespacedController extends ApiController
{

    /**
     * The currently selected project in actions where there is one
     *
     * @param framework\Request $request
     * @param $action
     */
    public function preExecute(framework\Request $request, $action)
    {
        parent::preExecute($request, $action);

        try
        {
            if ($project_id = (int) $request['project_id']) {
                $this->selected_project = entities\Project::getB2DBTable()->selectByID($project_id);

                if ($this->selected_project instanceof entities\Project) {
                    framework\Context::setCurrentProject($this->selected_project);
                }
            }

        }
        catch (\Exception $e)
        {
            $this->getResponse()->setHttpStatus(500);
            return $this->renderJSON(array('error' => 'An exception occurred: ' . $e));
        }

        if ($this->getRouting()->getCurrentRouteName() !== 'api_projects_list' && !$this->selected_project instanceof entities\Project) {
            $this->getResponse()->setHttpStatus(404);
            return $this->renderJSON(array('error' => 'Project not found'));
        }
    }

}
