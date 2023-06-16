<?php

namespace App\Services\EventRepeater;

use App\Models\Event;
use Carbon\Carbon;

interface Repeater
{
    public function setRepeat(Carbon $startAt, Carbon $endAt = null): Event;

    public function getRepeatEvents(Carbon $startAt = null, Carbon $endAt = null): array;

    public function setNextRepeat(): array;
}
