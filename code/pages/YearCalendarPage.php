<?php

/**
 * Class YearCalendarPage
 *
 */
class YearCalendarPage extends Page
{
    protected $month;

    protected $year;

    public function YearCalendarItems()
    {
        $start = new DateTimeHelper(sprintf('%1$s-%2$s-01', $this->year, $this->month));
        $start->setTimeToDayStart();
        $end = clone $start;
        $end->modify('last day of this month');
        $end->setTimeToDayEnd();

        $agenda = YearCalendarItem::get()
            ->whereAny(
                [
                    '"YearCalendarItem"."To" >= ? AND "YearCalendarItem"."To" <= ?'     => [$start->getSqlDateTime(), $end->getSqlDateTime()],
                    '"YearCalendarItem"."From" >= ? AND "YearCalendarItem"."From" <= ?' => [$start->getSqlDateTime(), $end->getSqlDateTime()],
                ]
            );

        $calendar = Calendar::create($start, $end, $agenda);

        Requirements::customScript(sprintf("
           var year = %1\$s;
           var month = %2\$s;
       ",
            $calendar->Year,
            $calendar->Month->Numeric));

        return $calendar;

    }

    public function Holidays()
    {
        $start = new DateTimeHelper(sprintf('%1$s-01-01', $this->year));
        $start->setTimeToDayStart();
        $end = new DateTimeHelper(sprintf('%1$s-12-31', $this->year));
        $end->setTimeToDayEnd();

        $agendaTag = YearCalendarItemTag::get()
            ->filter(['URLSegment' => 'vakantie'])
            ->first();

        $agenda = YearCalendarItem::get()
            ->leftJoin('YearCalendarItem_Tags', 'YearCalendarItem.ID = YearCalendarItem_Tags.YearCalendarItemID')
            ->leftJoin('YearCalendarItemTag', 'YearCalendarItem_Tags.YearCalendarItemTagID = YearCalendarItemTag.ID')
            ->filter(['YearCalendarItemTag.URLSegment' => 'vakantie'])
            ->whereAny([
                '"YearCalendarItem"."To" >= ? AND "YearCalendarItem"."To" <= ?' => [$start->getSqlDateTime(), $end->getSqlDateTime()],
                '"YearCalendarItem"."From" >= ?'                                => [$start->getSqlDateTime()],
            ]);

        return $agenda;
    }

    public function setDate($month, $year)
    {
        $this->month = $month;
        $this->year  = $year;
    }

    public function getTags()
    {
        return YearCalendarItemTag::get();
    }
}

/**
 * Class CalendarPage_Controller
 *
 */
class YearCalendarPage_Controller extends Page_Controller
{
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
}