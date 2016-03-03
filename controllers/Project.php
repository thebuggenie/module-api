<?php

namespace thebuggenie\modules\api\controllers;

use thebuggenie\core\framework,
    thebuggenie\core\entities,
    thebuggenie\core\entities\tables,
    thebuggenie\core\helpers;

/**
 * actions for the api module
 */
class Project extends helpers\ProjectActions
{

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
