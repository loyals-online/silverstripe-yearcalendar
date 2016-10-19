<?php

class IcalController extends Controller
{
    public function index()
    {
        $now = new DateTimeHelper();

        $agenda = Agenda::get()
            ->filterAny([
                'From:GreaterThanOrEqual' => $now->getSqlDateTime(),
                'To:GreaterThanOrEqual'   => $now->getSqlDateTime(),
            ]);

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