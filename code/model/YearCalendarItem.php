<?php

class YearCalendarItem extends DataObject
{
    private static $singular_name = 'Year Calendar Item';
    private static $plural_name   = 'Year Calendar Items';

    private static $db = [
        'Title'       => 'Varchar',
        'From'        => 'SS_DateTime',
        'To'          => 'SS_DateTime',
        'WholeDay'    => 'Boolean',
        'NonFeatured' => 'Boolean',
    ];

    private static $defaults = [
        'NonFeatured' => 0,
    ];

    private static $many_many = [
        'Tags' => 'YearCalendarItemTag',
    ];

    public static $summary_fields = [
        'Title' => 'Title',
        'From'  => 'From',
        'To'    => 'To',
    ];

    /**
     * Dynamic field labels
     *
     * @param bool $includerelations
     *
     * @return array|string
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);

        $labels = array_merge($labels, [
            'Title'       => _t('Admin.YearCalendar.Title', 'Title'),
            'From'        => _t('Admin.YearCalendarFrom', 'From'),
            'To'          => _t('Admin.YearCalendar.To', 'To'),
            'WholeDay'    => _t('Admin.YearCalendar.WholeDay', 'WholeDay'),
            'Content'     => _t('Admin.YearCalendar.Content', 'Content'),
            'NonFeatured' => _t('Admin.YearCalendar.NonFeatured', 'Not featured'),
        ]);

        return $labels;
    }

    /**
     * Modify which fields are used
     *
     * @return \FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Tags',
            'From',
            'To'
        ]);

        $fields->insertAfter('Title', TagField::create(
            'Tags',
            _t('Admin.YearCalendar.Tags', 'Tags'),
            YearCalendarItemTag::get(),
            $this->Tags()
        )
            ->setCanCreate(true)
            ->setShouldLazyLoad(true));

        $fields->fieldByName('Root')
            ->setTemplate('TabSet_holder');

        $fromField = new DatetimeField("From", "From");
        $fromField->getDateField()->setConfig('showcalendar', true);

        $toField = new DatetimeField("To", "To");
        $toField->getDateField()->setConfig('showcalendar', true);

        $fields->insertAfter($toField, 'Tags');
        $fields->insertAfter($fromField, 'Tags');

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->WholeDay) {
            $this->From = $this->FromDateTime()
                ->setTimeToDayStart()
                ->format('Y-m-d H:i:s');
            $this->To   = $this->ToDateTime()
                ->setTimeToDayEnd()
                ->format('Y-m-d H:i:s');
        }
    }

    /**
     * Retrieve the From field as DateTimeHelper object
     *
     * @return \DateTimeHelper
     */
    public function FromDateTime()
    {
        return new DateTimeHelper($this->From);
    }

    /**
     * Retrieve the To field as DateTimeHelper object
     *
     * @return \DateTimeHelper
     */
    public function ToDateTime()
    {
        return new DateTimeHelper($this->To);
    }

    /**
     * From date for display purposes. Supports PHP's date() arguments
     *
     * @param string $format
     *
     * @return string
     */
    public function DisplayFromDate($format = 'd M')
    {
        $month = false;
        if (strpos($format, 'F')) {
            $format = str_replace('F', '[|]', $format);
            $month  = true;
        }
        $string = $this->FromDateTime()
            ->format($format);
        if ($month) {
            $string = str_replace('[|]', _t(
                sprintf(
                    'Site.Month.%1$s',
                    $this->FromDateTime()
                        ->format('F')
                ),
                $this->FromDateTime()
                    ->format('F')
            ), $string);
        }

        return $string;
    }

    /**
     * To date for display purposes. Supports PHP's date() arguments
     *
     * @param string $format
     *
     * @return string
     */
    public function DisplayToDate($format = 'd M')
    {
        $month = false;
        if (strpos($format, 'F')) {
            $format = str_replace('F', '[|]', $format);
            $month  = true;
        }
        $string = $this->ToDateTime()
            ->format($format);
        if ($month) {
            $string = str_replace('[|]', _t(
                sprintf(
                    'Site.Month.%1$s',
                    $this->ToDateTime()
                        ->format('F')
                ),
                $this->ToDateTime()
                    ->format('F')
            ), $string);
        }

        return $string;
    }

    /**
     * Retrieve the next X items by either To date or From date. Filtered by NOW.
     *
     * @param int  $items
     * @param bool $filterByEndDate
     *
     * @return \DataList|\SS_Limitable
     */
    public static function getNext($items = 3, $filterByEndDate = false)
    {
        $date     = (new DateTimeHelper())->getSqlDateTime();
        $articles = static::get()
            ->where([
                sprintf('"YearCalendarItem"."%1$s" >= ?', $filterByEndDate ? 'To' : 'From') => $date,
            ])
            ->exclude(['NonFeatured' => 1])
            ->sort($filterByEndDate ? 'To' : 'From', 'ASC')
            ->limit($items);

        return $articles;
    }

}