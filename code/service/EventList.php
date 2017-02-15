<?php

class EventList
{
    /**
     * Constants response keys
     */
    const RESPONSE_YEAR = 'Year';
    const RESPONSE_MONTHS = 'Months';
    const RESPONSE_MONTH = 'Month';
    const RESPONSE_EVENTS = 'Events';

    /**
     * Events
     *
     * @var DataList
     */
    protected $events;

    public static function create(DataList $items)
    {
        $list = new static();

        $list->setEvents($items);

        return $list->generate();
    }

    public function setEvents(DataList $events)
    {
        $this->events = $events;
    }

    public function getEvents()
    {
        return $this->events;
    }

    public function generate()
    {
        $list = [];
        /** @var YearCalendarItem $event */
        foreach ($this->events as $event) {
            $year = $event->FromDateTime()
                ->format('Y');
            if (!isset($list[$year])) {
                $list[$year] = [];
            }
            $month = $event->FromDateTime()
                ->format('F');
            if (!isset($list[$year][$month])) {
                $list[$year][$month] = ArrayList::create();
            }
            $list[$year][$month]->push($event);
        }

        if (!$list) {
            return false;
        }

        foreach ($list as $y => $months) {
            $year = ArrayData::create([
                static::RESPONSE_YEAR   => $y,
                static::RESPONSE_MONTHS => ArrayList::create(),
            ]);
            foreach ($months as $m => $items) {
                $group = ArrayData::create([
                    static::RESPONSE_MONTH  => _t(sprintf(
                        'YearCalendar.Month.%1$s',
                        $m
                    ), $m),
                    static::RESPONSE_EVENTS => $items,
                ]);
                $year->{static::RESPONSE_MONTHS}->push($group);
            }
        }

        return $year;
    }
}