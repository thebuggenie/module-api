<?php

    /** @var \thebuggenie\core\entities\Issuetype[] $issuetypes */

    $json = [];
    foreach ($issuetypes as $issuetype) {
        $json[$issuetype->getID()] = $issuetype->toJSON();
    }

    echo json_encode($json);