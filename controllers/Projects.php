<?php

namespace thebuggenie\modules\api\controllers;

use thebuggenie\core\framework,
    thebuggenie\core\entities,
    thebuggenie\core\entities\tables;

/** @noinspection PhpInconsistentReturnPointsInspection */

/**
 * Main actions for the api module
 *
 * @property entities\Project[] $projects
 *
 * @Routes(name_prefix="api_projects_", url_prefix="/api/v1/projects")
 */
class Projects extends ProjectNamespacedController
{

    /**
     * List all projects
     *
     * @Route(name="list", url="/")
     * @param framework\Request $request
     */
    public function runProjects(framework\Request $request)
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

    /**
     * Show details about one project
     *
     * @Route(name="get", url="/:project_id")
     *
     * @param framework\Request $request
     */
    public function runProject(framework\Request $request) {
    }

    /**
     * Show details about one project
     *
     * @Route(name="issues_list", url="/:project_id/issues")
     *
     * @param framework\Request $request
     */
    public function runListIssues(framework\Request $request)
    {
        $filters = array('project_id' => array('v' => $this->selected_project->getID(), 'o' => '='));
        $filter_state = $request->getParameter('state', 'open');
        $filter_issuetype = $request->getParameter('issuetype', 'all');
        $filter_assigned_to = $request->getParameter('assigned_to', 'all');
        $filter_relation = $request->getParameter('relation');

        if (mb_strtolower($filter_state) != 'all')
        {
            $filters['state'] = array('o' => '=', 'v' => '');
            if (mb_strtolower($filter_state) == 'open')
                $filters['state']['v'] = entities\Issue::STATE_OPEN;
            elseif (mb_strtolower($filter_state) == 'closed')
                $filters['state']['v'] = entities\Issue::STATE_CLOSED;
        }

        if (mb_strtolower($filter_issuetype) != 'all')
        {
            $issuetype = entities\Issuetype::getByKeyish($filter_issuetype);
            if ($issuetype instanceof entities\Issuetype)
            {
                $filters['issuetype'] = array('o' => '=', 'v' => $issuetype->getID());
            }
        }

        if (mb_strtolower($filter_assigned_to) != 'all')
        {
            $user_id = 0;
            switch (mb_strtolower($filter_assigned_to))
            {
                case 'me':
                    $user_id = framework\Context::getUser()->getID();
                    break;
                case 'none':
                    $user_id = 0;
                    break;
                default:
                    try
                    {
                        $user = entities\User::findUser(mb_strtolower($filter_assigned_to));
                        if ($user instanceof entities\User)
                            $user_id = $user->getID();
                    }
                    catch (\Exception $e)
                    {

                    }
                    break;
            }

            $filters['assignee_user'] = array('o' => '=', 'v' => $user_id);
        }

        if (is_numeric($filter_relation) && in_array((string) $filter_relation, array('4', '3', '2', '1', '0')))
        {
            $filters['relation'] = array('o' => '=', 'v' => $filter_relation);
        }

        foreach ($filters as $key => $options) {
            $filters[$key] = \thebuggenie\core\entities\SearchFilter::createFilter($key, $options);
        }

        list ($this->issues, $this->count) = entities\Issue::findIssues($filters, 50);
    }

}
