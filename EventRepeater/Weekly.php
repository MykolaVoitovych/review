<?php

namespace App\Services\EventRepeater;

use App\Models\Event;

class Weekly extends SimpleInterval
{
    protected const INTERVAL = 604800;

    public function __construct(Event $event)
    {
        parent::__construct($event, self::INTERVAL);
    }
}
