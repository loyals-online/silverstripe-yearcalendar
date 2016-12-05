<?php

/**
 * Class YearCalendarPage
 *
 */
class YearCalendarPage extends Page
{
    /**
     * Current month
     *
     * @var string
     */
    protected $month;

    /**
     * Current year
     *
     * @var string
     */
    protected $year;

    /**
     * Current archive
     *
     * @var string
     */
    protected $archive;

    /**
     * @inheritdoc
     */
    private static $db = [
        'Template' => 'Enum("YearCalendar, EventList", "YearCalendar")',
    ];

    /**
     * @inheritdoc
     */
    private static $many_many = [
        'Tags' => 'YearCalendarItemTag',
    ];

    /**
     * Flag: are the front-end scripts loaded
     *
     * @var bool
     */
    protected static $frontendLoaded = false;

    /**
     * @inheritdoc
     */
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

        $fields->insertAfter(
            'Tags',
            DropdownField::create(
                'Template',
                _t('YearCalendarPage.db_Template', 'Template'),
                $this->dbObject('Template')
                    ->enumValues(),
                $this->Template
            )
        );

        $this->extend('modifyCMSFields', $fields);

        return $fields;
    }

    /**
     * Retrieve all YearCalendarItems as a calendar
     *
     * @return \ArrayData
     */
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

    /**
     * Retrieve all YearCalendarItems in a list
     *
     * @return mixed
     */
    public function YearCalendarList()
    {

        if ($this->archive) {
            $start = new DateTimeHelper(sprintf('%1$s-01-01', $this->archive));
            $end   = new DateTimeHelper(sprintf('%1$s-12-31', $this->archive));
            $start->setTimeToDayStart();
            $end->setTimeToDayEnd();
            $where = [
                '"YearCalendarItem"."From" < ? AND "YearCalendarItem"."To" > ?'   => [$start->getSqlDateTime(), $start->getSqlDateTime()],
                '"YearCalendarItem"."To" > ? AND "YearCalendarItem"."From" < ?'   => [$end->getSqlDateTime(), $end->getSqlDateTime()],
                '"YearCalendarItem"."To" >= ? AND "YearCalendarItem"."From" <= ?' => [$start->getSqlDateTime(), $end->getSqlDateTime()],
            ];
        } else {
            $start = new DateTimeHelper();
            $start->setTimeToDayStart();
            $where = [
                '"YearCalendarItem"."To" >= ?' => [$start->getSqlDateTime()],
            ];
        }

        $items = YearCalendarItem::get()
            ->whereAny($where);

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

        return EventList::create($items->Sort('From', 'asc'));
    }

    /**
     * Retrieve everything marked as a holiday
     *
     * @return mixed
     */
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

    /**
     * Set the current date
     *
     * @param string $month
     * @param string $year
     */
    public function setDate($month, $year)
    {
        $this->month = $month;
        $this->year  = $year;
    }

    /**
     * Set the current archive
     *
     * @param string $archive
     */
    public function setArchive($archive)
    {
        $this->archive = $archive;
    }

    /**
     * Retrieve the current archive
     *
     * @return string
     */
    public function getArchive()
    {
        return $this->archive;
    }

}

/**
 * Class CalendarPage_Controller
 *
 */
class YearCalendarPage_Controller extends Page_Controller
{
    /**
     * @inheritdoc
     */
    private static $allowed_actions = [
        'ical',
        'year',
    ];

    /**
     * @inheritdoc
     */
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

    /**
     * index action
     *
     * @return mixed
     */
    public function index()
    {
        return $this->renderWith($this->templates());
    }

    /**
     * year action
     *
     * @return mixed
     */
    public function year()
    {
        $this->data()
            ->setArchive(
                $this->getRequest()
                    ->param('ID')
            );

        return $this->renderWith($this->templates('EventList'));
    }

    /**
     * ical action
     *
     * @return mixed
     */
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
                    ->map('ID', 'ID'),
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

    /**
     * Retrieve tags belonging to this Page
     *
     * @return \DataList
     */
    public function Tags()
    {
        /** @var DataList $tags */
        $tags = $this->data()
            ->Tags()
            ->Count() ? $this->data()
            ->Tags() : YearCalendarItemTag::get();

        /** @var YearCalendarItemTag $object */
        $tags = $tags->filterByCallback(function ($object) {
            return $object->Items()
                ->Count();
        });

        $this->extend('updateTags', $tags);

        return $tags;
    }

    /**
     * Create a dropdown field
     *
     * @return DropdownField
     */
    public function ArchiveDropdown()
    {
        Requirements::customScript(sprintf("
            var link = '%1\$s'
        ", $this->Link()));

        return DropdownField::create(
            'Year',
            null,
            $this->Years(),
            $this->data()
                ->getArchive()
        )
            ->setEmptyString('Upcoming')
            ->addExtraClass('archive');
    }

    /**
     * Collect the years for which we have items
     *
     * @return array
     */
    protected function Years()
    {
        $value = [];
        foreach (
            (new SQLSelect())->setFrom('YearCalendarItem as yci')
                ->setSelect('DISTINCT(DATE_FORMAT(yci.From, \'%Y\')) as Year')
                ->execute()
            as $row
        ) {
            array_push($value, $row['Year']);
        }
        sort($value);
        $value = array_combine($value, $value);

        return $value;
    }

    /**
     * Retrieve the templates we're using to render
     *
     * @return array
     */
    protected function templates($template = null)
    {
        if (is_null($template)) {
            $template = $this->data()->Template;
        }

        return [
            sprintf('%1$sPage', $template),
            'Page',
        ];
    }
}