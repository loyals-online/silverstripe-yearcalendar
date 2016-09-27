<?php

/**
 * Generator for (monthly) calendars
 *
 * Class Calendar
 */
class Calendar
{
    /**
     * Constants response keys
     */
    const RESPONSE_DATE           = 'Date';
    const RESPONSE_EVENTS         = 'Events';
    const RESPONSE_MONTH          = 'Month';
    const RESPONSE_MONTH_TEXT     = 'Text';
    const RESPONSE_MONTH_NUMERIC  = 'Numeric';
    const RESPONSE_YEAR           = 'Year';
    const RESPONSE_DAYS           = 'Days';
    const RESPONSE_NEXT_MONTH     = 'NextMonth';
    const RESPONSE_PREVIOUS_MONTH = 'PreviousMonth';

    /**
     * Start date for this calendar
     *
     * @var DateTimeHelper
     */
    protected $start;

    /**
     * End date for this calendar
     *
     * @var DateTimeHelper
     */
    protected $end;

    /**
     * The days in this calendar
     *
     * @var ArrayData
     */
    protected $days;

    /**
     * Holder of (this month's) Agenda items
     *
     * @var DataList
     */
    protected $agenda;

    /**
     * Holds the representation of an empty day
     *
     * @var ArrayData
     */
    protected $emptyDay;

    /**
     * Counter for the number of events
     *
     * @var int
     */
    protected $events = 0;

    /**
     * Create a fully functional calendar. Can immediately be used in templates.
     *
     * @param \DateTimeHelper $startDateTime
     * @param \DateTimeHelper $endDateTime
     * @param \DataList       $agenda
     *
     * @return ArrayData
     */
    public static function create(DateTimeHelper $startDateTime, DateTimeHelper $endDateTime, DataList $agenda)
    {
        $calendar = new static();

        $calendar->setStart($startDateTime);
        $calendar->setEnd($endDateTime);
        $calendar->setAgenda($agenda);

        $calendar->generate();

        $nextMonth = clone $startDateTime;
        $nextMonth->modify('last day of next month');

        $prevMonth = clone $endDateTime;
        $prevMonth->modify('first day of previous month');

        return ArrayData::create([
            static::RESPONSE_MONTH          => ArrayData::create([
                static::RESPONSE_MONTH_TEXT    => _t(
                    sprintf(
                        'YearCalendar.Month.%1$s',
                        $calendar->getStart()
                            ->format('F')
                    ),
                    $calendar->getStart()
                        ->format('F')
                ),
                static::RESPONSE_MONTH_NUMERIC => $calendar->getStart()
                    ->format('n'),
            ]),
            static::RESPONSE_YEAR           => $calendar->getStart()
                ->format('Y'),
            static::RESPONSE_DAYS           => $calendar->days,
            static::RESPONSE_EVENTS         => $calendar->events,
            static::RESPONSE_NEXT_MONTH     => _t(
                sprintf(
                    'YearCalendar.Month.%1$s',
                    $nextMonth->format('F')
                ),
                $nextMonth->format('F')
            ),
            static::RESPONSE_PREVIOUS_MONTH => _t(
                sprintf(
                    'YearCalendar.Month.%1$s',
                    $prevMonth->format('F')
                ),
                $prevMonth->format('F')
            ),
        ]);
    }

    /**
     * Calendar constructor.
     *
     */
    public function __construct()
    {
        $this->days = ArrayList::create();
    }

    /**
     * Set the start date for this calendar
     *
     * @param \DateTimeHelper $startDateTime
     */
    public function setStart(DateTimeHelper $startDateTime)
    {
        $this->start = $startDateTime;
    }

    /**
     * Retrieve the start date for this calendar
     *
     * @return \DateTimeHelper
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set the end date for this calendar
     *
     * @param \DateTimeHelper $endDateTime
     */
    public function setEnd(DateTimeHelper $endDateTime)
    {
        $this->end = $endDateTime;
    }

    /**
     * Set the Agenda items for this calendar
     *
     * @param \DataList $agenda
     */
    public function setAgenda(DataList $agenda)
    {
        $this->agenda = $agenda;
    }

    /**
     * Generate the calendar
     *
     */
    public function generate()
    {
        $this->generateStartDuds();

        $this->generateDays();

        $this->generateEndDuds();
    }

    /**
     * Generate the duds needed before the actual calendar starts
     *
     */
    protected function generateStartDuds()
    {
        $firstDay = (int) $this->start->format('w');
        for ($i = 0; $i < $firstDay; ++$i) {
            $this->days->push($this->getEmptyDay());
        }
    }

    /**
     * Generate the actual calendar days
     *
     */
    protected function generateDays()
    {
        /** @var DateTimeHelper $day */
        $day = clone $this->start;
        do {
            $dayEntry = ArrayData::create([
                static::RESPONSE_DATE   => $day->format('d'),
                static::RESPONSE_EVENTS => $this->findEventsForDay($day),
            ]);
            $this->days->push($dayEntry);
            $day->modify('+1 day');
        } while ($day <= $this->end);
    }

    /**
     * Generate the duds needed after the actual calendar ends
     *
     */
    protected function generateEndDuds()
    {
        $lastDay = (int) $this->end->format('w');
        for ($i = $lastDay + 1; $i < 7; ++$i) {
            $this->days->push($this->getEmptyDay());
        }
    }

    /**
     * Find the Agenda items for a given day
     *
     * @param \DateTimeHelper $day
     *
     * @return static
     */
    protected function findEventsForDay(DateTimeHelper $day)
    {
        $return = ArrayList::create();
        /** @var Agenda $item */
        foreach ($this->agenda as $item) {
            if (!$item->WholeDay) {
                if ($day->isDateEqualTo($item->FromDateTime())) {
                    ++$this->events;
                    $return->push($item);
                }
            } elseif ($day->isBetween($item->FromDateTime(), $item->ToDateTime())) {
                ++$this->events;
                $return->push($item);
            }
        }

        return $return;
    }

    /**
     * Retrieve the empty day representation
     *
     * @return \ArrayData|static
     */
    protected function getEmptyDay()
    {
        if (!$this->emptyDay) {
            $this->emptyDay = ArrayData::create([
                static::RESPONSE_DATE   => null,
                static::RESPONSE_EVENTS => ArrayList::create(),
            ]);
        }

        return $this->emptyDay;
    }
}