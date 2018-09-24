<?php

    /** @var \thebuggenie\core\entities\Issue[] $issues */

    if ($count > 0)
    {
        foreach ($issues as $issue)
        {
            $return_issues[] = array('id' => $issue->getID(),
                'title' => $issue->getRawTitle(),
                'state' => $issue->getState(),
                'issue_no' => $issue->getFormattedIssueNo(true),
                'posted_by' => ($issue->getPostedBy() instanceof \thebuggenie\core\entities\common\Identifiable) ? $issue->getPostedBy()->getUsername() : __('Unknown'),
                'assigned_to' => ($issue->getAssignee() instanceof \thebuggenie\core\entities\common\Identifiable) ? $issue->getAssignee()->getName() : __('Noone'),
                'created_at' => $issue->getPosted(),
                'last_updated' => $issue->getLastUpdatedTime(),
                'status' => ($issue->getStatus() instanceof \thebuggenie\core\entities\Status) ? $issue->getStatus()->getName() : __('Unknown')
            );
        }
    }

    echo json_encode(array('count' => $count, 'issues' => $return_issues));
