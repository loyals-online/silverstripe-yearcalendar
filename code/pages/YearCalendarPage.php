<?php

/**
 * Class YearCalendarPage
 *
 */
class YearCalendarPage extends Page
{
    protected $month;

    protected $year;

    private static $many_many = [
        'Tags' => 'YearCalendarItemTag',
    ];

    protected static $frontendLoaded = false;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Tags',
        ]);

        $fields->insertBefore(
            'Content',
            TagField::create(
                'Tags',
                _t('YearCalendar.Tags', 'Tags'),
                YearCalendarItemTag::get(),
                $this->Tags()
            )
                ->setCanCreate(false)
                ->setShouldLazyLoad(true)
        );

        return $fields;
    }

    public function YearCalendarItems()
    {
        $start = new DateTimeHelper(sprintf('%1$s-%2$s-01', $this->year, $this->month));
        $start->setTimeToDayStart();
        $end = clone $start;
        $end->modify('last day of this month');
        $end->setTimeToDayEnd();

        $items = YearCalendarItem::get()
            ->whereAny(
                [
                    '"YearCalendarItem"."From" < ? AND "YearCalendarItem"."To" > ?'   => [$start->getSqlDateTime(), $start->getSqlDateTime()],
                    '"YearCalendarItem"."To" > ? AND "YearCalendarItem"."From" < ?'   => [$end->getSqlDateTime(), $end->getSqlDateTime()],
                    '"YearCalendarItem"."To" >= ? AND "YearCalendarItem"."From" <= ?' => [$start->getSqlDateTime(), $end->getSqlDateTime()],
                ]
            );

        // if we have Tags, filter items by them
        if ($this->Tags()
            ->Count()
        ) {
            $items = $items->filter([
                'Tags.ID' => $this->Tags()
                    ->map('ID', 'ID')
                    ->toArray(),
            ]);
        }

        $calendar = Calendar::create($start, $end, $items);

        if (!static::$frontendLoaded) {
            Requirements::customScript(sprintf("
           var year = %1\$s;
           var month = %2\$s;
       ",
                $calendar->Year,
                $calendar->Month->Numeric));
            static::$frontendLoaded = true;
        }

        return $calendar;

    }

    public function Holidays()
    {
        $start = new DateTimeHelper(sprintf('%1$s-01-01', $this->year));
        $start->setTimeToDayStart();
        $end = new DateTimeHelper(sprintf('%1$s-12-31', $this->year));
        $end->setTimeToDayEnd();

        $items = YearCalendarItem::get()
            ->leftJoin('YearCalendarItem_Tags', 'YearCalendarItem.ID = YearCalendarItem_Tags.YearCalendarItemID')
            ->leftJoin('YearCalendarItemTag', 'YearCalendarItem_Tags.YearCalendarItemTagID = YearCalendarItemTag.ID')
            ->filter(['YearCalendarItemTag.URLSegment' => 'vakantie'])
            ->whereAny([
                '"YearCalendarItem"."To" >= ? AND "YearCalendarItem"."To" <= ?' => [$start->getSqlDateTime(), $end->getSqlDateTime()],
                '"YearCalendarItem"."From" >= ?'                                => [$start->getSqlDateTime()],
            ]);

        return $items;
    }

    public function setDate($month, $year)
    {
        $this->month = $month;
        $this->year  = $year;
    }
}

/**
 * Class CalendarPage_Controller
 *
 */
class YearCalendarPage_Controller extends Page_Controller
{
    private static $allowed_actions = [
        'ical',
    ];

    public function init()
    {
        parent::init();

        $month = $this->getRequest()
            ->getVar('month') ?: date('m');
        $year  = $this->getRequest()
            ->getVar('year') ?: date('Y');
        $this->data()
            ->setDate($month, $year);
    }

    public function ical()
    {
        $now = new DateTimeHelper();

        $agenda = YearCalendarItem::get()
            ->filterAny([
                'From:GreaterThanOrEqual' => $now->getSqlDateTime(),
                'To:GreaterThanOrEqual'   => $now->getSqlDateTime(),
            ]);

        // if we have Tags, filter items by them
        if ($this->Tags()
            ->Count()
        ) {
            $agenda = $agenda->filter([
                'Tags.ID' => $this->Tags()
                    ->map('ID', 'ID')
                    ->toArray(),
            ]);
        }

        $ical = new Sabre\VObject\Component\VCalendar([
            'PRODID'       => 'JaarKalender',
            'X-WR-CALNAME' => SiteConfig::current_site_config()->Title . ' Jaarkalender',
        ]);

        /** @var Agenda $item */
        foreach ($agenda as $item) {

            $event = $ical->createComponent('VEVENT');
            $ical->add($event);

            $event->SUMMARY = $item->Title;
            $event->UID     = sprintf('%1$s-%2$s@%3$s', singleton('SiteTree')->generateURLSegment($item->Title), $item->ID, $_SERVER['SERVER_NAME']);
            $event->DTSTAMP = $item->FromDateTime()
                ->setTimezone(new DateTimeZone("UTC"))
                ->format('Ymd\THis\Z');
            $event->DTSTART = $item->FromDateTime()
                ->setTimezone(new DateTimeZone("UTC"))
                ->format('Ymd\THis\Z');
            $event->DTEND   = $item->ToDateTime()
                ->setTimezone(new DateTimeZone("UTC"))
                ->format('Ymd\THis\Z');
        }

        return SS_HTTPRequest::send_file($ical->serialize(), 'calendar.ics', 'text/calendar; charset=utf-8');
    }
}