<?php

class YearCalendarItem extends DataObject
{

    /**
     * @inheritdoc
     */
    public function canView($member = null)
    {
        return Permission::check('CMS_ACCESS_YearCalendarAdmin', 'any', $member);
    }

    /**
     * @inheritdoc
     */
    public function canEdit($member = null)
    {
        return Permission::check('CMS_ACCESS_YearCalendarAdmin', 'any', $member);
    }

    /**
     * @inheritdoc
     */
    public function canDelete($member = null)
    {
        return Permission::check('CMS_ACCESS_YearCalendarAdmin', 'any', $member);
    }

    /**
     * @inheritdoc
     */
    public function canCreate($member = null)
    {
        return Permission::check('CMS_ACCESS_YearCalendarAdmin', 'any', $member);
    }

    /**
     * @inheritdoc
     */
    private static $singular_name = 'Year Calendar Item';

    /**
     * @inheritdoc
     */
    private static $plural_name = 'Year Calendar Items';

    /**
     * @inheritdoc
     */
    private static $db = [
        'Title'          => 'Varchar(255)',
        'From'           => 'SS_DateTime',
        'To'             => 'SS_DateTime',
        'WholeDay'       => 'Boolean',
        'NonFeatured'    => 'Boolean',
        'ExcludeWeekend' => 'Boolean',
    ];

    /**
     * @inheritdoc
     */
    private static $defaults = [
        'NonFeatured' => 0,
    ];

    /**
     * @inheritdoc
     */
    private static $many_many = [
        'Tags' => 'YearCalendarItemTag',
    ];

    /**
     * @inheritdoc
     */
    public static $summary_fields = [
        'Title' => 'Title',
        'From'  => 'From',
        'To'    => 'To',
    ];

    /**
     * Modify which fields are used
     *
     * @return \FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Title',
            'Tags',
            'From',
            'To',
            'WholeDay',
            'NonFeatured',
            'ExcludeWeekend',
        ]);

        $fields->addFieldsToTab('Root.Main', [
                    $this->getTranslatableTabSet()
        ]);

        $fields->addFieldToTab('Root.Main', TagField::create(
            'Tags',
            _t('YearCalendar.Tags', 'Tags'),
            YearCalendarItemTag::get(),
            $this->Tags()
        )
            ->setCanCreate(true)
            ->setShouldLazyLoad(true));

        $fields->fieldByName('Root')
            ->setTemplate('TabSet_holder');

        $fromField = new DatetimeField('From', _t('YearCalendarItem.db_From', 'From'));
        $fromField->getDateField()
            ->setConfig('showcalendar', true);

        $toField = new DatetimeField('To', _t('YearCalendarItem.db_To', 'To'));
        $toField->getDateField()
            ->setConfig('showcalendar', true);

        $fields->insertAfter($toField, 'Tags');
        $fields->insertAfter($fromField, 'Tags');

        $fields->addFieldsToTab(
            'Root.Main',
            [
                FieldGroup::create(
                    CheckboxField::create('WholeDay', _t('YearCalendarItem.db_WholeDay', 'Whole Day')),
                    CheckboxField::create('NonFeatured', _t('YearCalendarItem.db_NonFeatured', 'Not featured')),
                    CheckboxField::create('ExcludeWeekend', _t('YearCalendarItem.db_ExcludeWeekend', 'Exclude weekend')))
                    ->setTitle(_t('YearCalendarItem.Options', 'Options')),
            ],
            'Tags'
        );

        $this->extend('modifyCMSFields', $fields);

        return $fields;
    }

    /**
     * Retrieve the validator for this model
     *
     * @return \RequiredFields
     *
     * @note This is an undocumented feature
     */
    public function getCMSValidator()
    {
        $requiredFields = [];

        $this->extend('updateCMSValidatorFields', $requiredFields);

        return new RequiredFields($requiredFields);
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
        if (strpos($format, 'F') !== false) {
            $format = str_replace('F', '[|]', $format);
            $month  = true;
        }
        $string = $this->FromDateTime()
            ->format($format);
        if ($month) {
            $string = str_replace('[|]', _t(
                sprintf(
                    'YearCalendar.Month.%1$s',
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
        if (strpos($format, 'F') !== false) {
            $format = str_replace('F', '[|]', $format);
            $month  = true;
        }
        $string = $this->ToDateTime()
            ->format($format);
        if ($month) {
            $string = str_replace('[|]', _t(
                sprintf(
                    'YearCalendar.Month.%1$s',
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

    /**
     * Retrieve the Tags for this Item for display purposes
     *
     * @return \DataList
     */
    public function DisplayTags()
    {
        $tags = YearCalendarItemTag::get()
            ->leftJoin("YearCalendarItem_Tags", "YearCalendarItemTag.ID = YearCalendarItem_Tags.YearCalendarItemTagID")
            ->where(["YearCalendarItem_Tags.YearCalendarItemID = ?" => [$this->ID]]);

        $this->extend('updateDisplayTags', $tags);

        return $tags;
    }
}