<?php

namespace App\Services\EventRepeater;

use App\Models\Event;
use Carbon\Carbon;

class Monthly implements Repeater
{
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function setRepeat(Carbon $startAt, Carbon $endAt = null): Event
    {
        $this->event->setMeta('repeat_week_in_month', $startAt->weekOfMonth);
        $this->event->setMeta('repeat_weekday', $startAt->dayOfWeek);

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
        $weekOfMonth = $this->event->getMeta('repeat_week_in_month') - 1;
        $dayOfWeek = $this->event->getMeta('repeat_weekday');

        for ($date = $startAt; $date->lte($endAt); $date->addMonth()) {
            $repeatDate = (clone $date)->startOfMonth()->addWeeks($weekOfMonth)->weekday($dayOfWeek);
            //check if repeatDate in current month
            if ($repeatDate->lt((clone $date)->startOfMonth())) {
                $repeatDate->addWeek();
            }
            if ($repeatDate->gt((clone $date)->endOfMonth())) {
                $repeatDate->subWeek();
            }

            array_push($repeatEvents, [
                'start_time' => $this->getDateTime($repeatDate, $this->event->start_time_obj)
                    ->format('Y-m-d H:i:s'),
                'end_time' => $this->getDateTime($repeatDate, $this->event->end_time_obj)
                    ->format('Y-m-d H:i:s'),
            ]);
        }

        return $repeatEvents;
    }

    public function setNextRepeat(): array
    {
        $metaEnd = $this->event->getMeta('repeat_end');
        $weekOfMonth = $this->event->getMeta('repeat_week_in_month') - 1;
        $dayOfWeek = $this->event->getMeta('repeat_weekday');

        $newStartTime = $this->nextDate($this->event->start_time_obj, $weekOfMonth, $dayOfWeek);
        $newEndTime = $this->nextDate($this->event->end_time_obj, $weekOfMonth, $dayOfWeek);

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

    protected function nextDate(Carbon $date, $weekOfMonth, $dayOfWeek)
    {
        $newDate = $date->startOfMonth()
            ->addMonth()
            ->addWeeks($weekOfMonth)
            ->weekday($dayOfWeek);

        if ($newDate->lt($date->startOfMonth())) {
            $newDate->addWeek();
        }

        return $newDate;
    }

    public function getDateTime(Carbon $baseDate, Carbon $baseTime)
    {
        return $baseDate
            ->setHour($baseTime->hour)
            ->setMinute($baseTime->minute);
    }

    protected function weekOfMonth($date): int
    {
        //Get the first day of the month.
        $firstOfMonth = strtotime(date("Y-m-01", $date));
        //Apply above formula.
        return $this->weekOfYear($date) - $this->weekOfYear($firstOfMonth) + 1;
    }

    protected function weekOfYear(int $date): int
    {
        $weekOfYear = intval(date("W", $date));
        if (date('n', $date) == "1" && $weekOfYear > 51) {
            // It's the last week of the previos year.
            return 0;
        }
        else if (date('n', $date) == "12" && $weekOfYear == 1) {
            // It's the first week of the next year.
            return 53;
        }
        else {
            // It's a "normal" week.
            return $weekOfYear;
        }
    }


    public function getFirstDate($startAt)
    {
        $firstDate = $this->event->start_time_obj;

        $weekOfMonth = $this->event->getMeta('repeat_week_in_month') - 1;
        $dayOfWeek = $this->event->getMeta('repeat_weekday');

        if ($startAt->gt($firstDate)) {
            while ($startAt->gt($firstDate)) {
                $date = $firstDate->addMonth();
                $firstDate->startOfMonth()->addWeeks($weekOfMonth)->weekday($dayOfWeek);
                //check if repeatDate in current month
                if ($firstDate->lt((clone $date)->startOfMonth())) {
                    $firstDate->addWeek();
                }
                if ($firstDate->gt((clone $date)->endOfMonth())) {
                    $firstDate->subWeek();
                }
            }
        }

        return $firstDate;
    }
}
