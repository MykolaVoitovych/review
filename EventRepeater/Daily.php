<?php

namespace App\Services\EventRepeater;

use App\Models\Event;

class Daily extends SimpleInterval
{
    protected const INTERVAL = 86400;

    public function __construct(Event $event)
    {
        parent::__construct($event, self::INTERVAL);
    }
}
