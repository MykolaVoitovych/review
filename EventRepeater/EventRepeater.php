<?php

namespace App\Services\EventRepeater;

use App\Models\Event;
use App\Models\EventMeta;
use App\Services\EventRepeater\EveryTwoWeek;
use App\Services\EventRepeater\Monthly;
use App\Services\EventRepeater\Weekly;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait EventRepeater
{
    /*
     * Relationsips for repeated events
     */
    public function eventMeta(): HasMany
    {
        return $this->hasMany(EventMeta::class);
    }

    /*
     *  Get repeated events
     */
    public function getEvents(\DateTime $startAt = null, \DateTime $endAt)
    {
        if (is_null($startAt)) {
            $startAt = now();
        }

        $events = $this->getRepeatedEvents($startAt, $endAt);

        return $events;
    }

    public function getRepeat(Carbon $startAt = null, Carbon $endAt = null)
    {
        if ($this->getMeta('repeat_end')) {
            return [];
        }

        if (is_null($startAt)) {
            $startAt = now();
        }

        if (is_null($endAt)) {
            $endAt = now()->addYear();
        }

        return $this->getEventRepeats($startAt, $endAt);
    }

    /*
     * Set repeated events
     */
    public function setRepeat(Carbon $startAt, Carbon $endAt = null)
    {
        $repeater = $this->repeaterFactory();

        $repeater->setRepeat($startAt, $endAt);
    }

    public function setNextRepeat(): array
    {
        return $this->repeaterFactory()->setNextRepeat();
    }

    public function getEventRepeats(Carbon $startAt = null, Carbon $endAt = null)
    {
        if ($repeater = $this->repeaterFactory()) {
            return $repeater->getRepeatEvents($startAt, $endAt);
        }

        return [];
    }

    public function disableRepeat()
    {
        $this->eventMeta()->delete();
        $this->repeat_type = '';
        $this->save();

        return $this;
    }

    public function updateRepeat($newRepeatType, Carbon $startAt, Carbon $endAt = null): Model
    {
        if ($this->repeat_type !== $newRepeatType) {
            $this->repeat_type = $newRepeatType;
            $this->save();
            if (!$newRepeatType) {
                $this->disableRepeat();
            } else {
                $this->setRepeat($startAt, $endAt);
            }
        }

        if ($endAt && Carbon::parse($this->getMeta('repeat_end'))->ne($endAt)) {
            $this->setMeta('repeat_end', $endAt);
        }

        return $this;
    }

    /*
     * Event Meta
     */
    public function setMeta(string $key, $value)
    {
        $this->eventMeta()
            ->updateOrCreate(['meta_key' => $key], ['meta_value' => $value]);

        return $this;
    }

    public function getMeta(string $key)
    {
        return optional($this->eventMeta()->firstWhere('meta_key', $key))->meta_value;
    }

    public function createRepeatEvents()
    {
        $repeatEnd = Carbon::parse($this->getMeta('repeat_end'));
        $repeats = $this->getEventRepeats($this->start_time_obj, $repeatEnd);
        array_shift($repeats);
        foreach ($repeats as $repeat) {
            $data = $this->toArray();
            $startAt = Carbon::parse($repeat['start_time']);
            $event = Event::create($repeat + \Arr::except($data, ['repeat_end', 'id']));
            $event->setRepeat($startAt, $repeatEnd);
        }
    }

    public function removeRepeatEvents()
    {
        $repeatEnd = Carbon::parse($this->getMeta('repeat_end'));
        $repeats = $this->getEventRepeats($this->start_time_obj, $repeatEnd);
        foreach ($repeats as $repeat) {
            Event::where('start_time', $repeat['start_time'])
                ->where('end_time', $repeat['end_time'])
                ->where('repeat_type', $this->repeat_type)
                ->where('timezone', $this->timezone)
                ->delete();
        }
    }
}
