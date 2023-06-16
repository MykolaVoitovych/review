<?php

namespace App\Services\EventRepeater;

use App\Models\Event;
use Carbon\Carbon;

abstract class SimpleInterval implements Repeater
{
    protected Event $event;

    protected int $interval;

    public function __construct(Event $event, int $interval)
    {
        $this->event = $event;
        $this->interval = $interval;
    }

    public function setRepeat(Carbon $startAt, Carbon $endAt = null): Event
    {
        $this->event->setMeta('repeat_interval', $this->interval);

        if ($endAt) {
            $this->event->setMeta('repeat_end', $endAt);
            $this->event->repeat_end = $endAt;
        }

        return $this->event;
    }

    public function getRepeatEvents(Carbon $startAt = null, Carbon $endAt = null): array
    {
        $metaEnd = $this->event->getMeta('repeat_end') ? Carbon::parse($this->event->getMeta('repeat_end')) : null;

        if (!$startAt) {
            $startAt = now();
        }
        $startAt = $this->getFirstDate($startAt);

        if (!is_null($endAt) && ($metaEnd && $endAt->gt($metaEnd))) {
            $endAt = $metaEnd;
        }

        return $this->repeater($startAt, $endAt);
    }

    protected function repeater(Carbon $startAt, Carbon $endAt): array
    {
        $repeatEvents = [];
        $interval = $this->event->getMeta('repeat_interval');

        for ($date = $startAt; $date->lte($endAt); $date->addSeconds($interval)) {
            array_push($repeatEvents, [
                'start_time' => $this->getDateTime($date, $this->event->start_time_obj)->format('Y-m-d H:i:s'),
                'end_time' => $this->getDateTime($date, $this->event->end_time_obj)->format('Y-m-d H:i:s'),
            ]);
        }

        return $repeatEvents;
    }

    public function setNextRepeat(): array
    {
        $interval = $this->event->getMeta('repeat_interval');
        $metaEnd = $this->event->getMeta('repeat_end');

        $newStartTime = $this->event->start_time_obj->addSeconds($interval);
        $newEndTime = $this->event->end_time_obj->addSeconds($interval);

        if (!$metaEnd || $newEndTime->lte($metaEnd)) {
            $this->event->start_time = $newStartTime;
            $this->event->end_time = $newEndTime;
            $this->event->save();
        }

        return [
            'start_time' => $newStartTime,
            'end_time' => $newEndTime
        ];
    }

    public function getDateTime(Carbon $baseDate, Carbon $baseTime): Carbon
    {
        return $baseDate->setHour($baseTime->hour)
            ->setMinute($baseTime->minute);
    }

    public function getFirstDate($startAt)
    {
        $firstDate = $this->event->start_time_obj;
        $interval = $this->event->getMeta('repeat_interval');
        if ($startAt->gt($firstDate)) {
            while ($startAt->gt($firstDate)) {
                $firstDate->addSeconds($interval);
            }
        }

        return $firstDate;
    }
}
