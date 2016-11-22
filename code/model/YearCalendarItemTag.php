<?php

class YearCalendarItemTag extends DataObject
{
    private static $db = [
        'Title'      => 'Varchar',
        'URLSegment' => 'Varchar',
        'Color'      => 'SS_Color',
        'SortOrder'  => 'Int',
    ];

    private static $defaults = [
        'Color' => '666666100',
    ];

    public static $summary_fields = [
        'Title' => 'Title',
    ];

    private static $default_sort = "SortOrder";

    /**
     * Do stuff before writing to database
     *
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $URLSegment = $this->getLocalizedFieldnames('URLSegment');
        $Title      = $this->getLocalizedFieldnames('Title');

        foreach($URLSegment as $index => $fieldName) {
            if (!$this->$fieldName && $this->$Title[$index]) {
                $this->$fieldName = $this->generateURLSegment($this->$Title[$index]);
            } else {
                if ($this->isChanged($fieldName, 2)) {
                    // Do a strict check on change level, to avoid double encoding caused by
                    // bogus changes through forceChange()
                    $filter           = URLSegmentFilter::create();
                    $this->$fieldName = $filter->filter($this->$fieldName);
                    // If after sanitising there is no URLSegment, give it a reasonable default
                    if (!$this->$fieldName) {
                        $this->$fieldName = "tag-$this->ID";
                    }
                }
            }
        }
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
            'Title',
            'URLSegment',
            'SortOrder',
        ]);

        $fields->addFieldsToTab('Root.Translations', $this->getTranslatableTabSet());

        $fields->removeByName($this->getLocalizedFieldnames('URLSegment'));

        return $fields;
    }

    /**
     * Generate a URL segment based on the title provided.
     *
     * If {@link Extension}s wish to alter URL segment generation, they can do so by defining
     * updateURLSegment(&$url, $title).  $url will be passed by reference and should be modified. $title will contain
     * the title that was originally used as the source of this generated URL. This lets extensions either start from
     * scratch, or incrementally modify the generated URL.
     *
     * @param string $title Page title
     *
     * @return string Generated url segment
     */
    public function generateURLSegment($title)
    {
        $filter = URLSegmentFilter::create();
        $t      = $filter->filter($title);

        // Fallback to generic page name if path is empty (= no valid, convertable characters)
        if (!$t || $t == '-' || $t == '-1') {
            $t = "tag-$this->ID";
        }

        // Hook for extensions
        $this->extend('updateURLSegment', $t, $title);

        return $t;
    }

    /**
     * Return RGBA value based on save Color. Used in Templates.
     *
     * @return null|string
     */
    public function RgbaColorString()
    {
        if ($this->Color) {
            $field = new SS_Color();
            $field->setValue($this->Color);

            return sprintf('rgba(%1$s, %2$s)', implode(', ', $field->RGB()), str_replace(',', '.', $field->Alpha()));
        }

        return null;
    }

    /**
     * Return HEX value based on save Color. Used in Templates.
     *
     * @return null|string
     */
    public function HexColorString()
    {
        if ($this->Color) {
            $field = new SS_Color();
            $field->setValue($this->Color);

            return sprintf('#%1$s', $field->Hex());
        }

        return null;
    }

    /**
     * Return CSS string based on save Color. Used in Templates.
     *
     * @return null|string
     */
    public function CssColorString()
    {
        if ($this->Color) {
            return sprintf(
                'background-color: %1$s; background-color: %2$s',
                $this->HexColorString(),
                $this->RgbaColorString()
            );
        }

        return null;
    }

    protected function getLocalizedFieldnames($field)
    {
        $fieldnames = [];
        foreach (SiteLocaleConfig::inst()
                     ->getAllowedLocales() as $locale) {
            array_push($fieldnames, TranslatableDataObject::localized_field($field, $locale));
        }

        return $fieldnames;
    }
}