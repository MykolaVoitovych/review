<?php

namespace App\Services\EventRepeater;

use App\Models\Event;

class EveryTwoWeek extends SimpleInterval
{
    protected const INTERVAL = 1209600;

    public function __construct(Event $event)
    {
        parent::__construct($event, self::INTERVAL);
    }
}
