<?php

    if ($count > 0)
    {
        foreach ($issues as $issue)
        {
            if (! $issue->hasAccess()) continue;

            $return_issues[] = $issue->toJSON(true);
        }
    }

    echo json_encode(array('count' => $count, 'issues' => $return_issues));
