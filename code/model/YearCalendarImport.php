<?php

class YearCalendarImport extends DataObject
{
    private static $singular_name = 'Year Calender Import';
    private static $plural_name = 'Year Calender Imports';

    const HEADERCELL = 'Titel';

    private static $summary_fields = [
        'Name',
        'LastEdited',
    ];

    private static $has_one = [
        'Import' => 'File',
    ];

    /**
     * @inheritdoc
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Name',
            'Import',
        ]);

        $fields->addFieldsToTab(
            'Root.Main',
            UploadField::create(
                "Import", _t("YearCalendar.Import.Label", "Import")
            )
                ->setFolderName('imports')
        );

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if ($this->ImportID) {
            $this->processImport($this->Import()
                ->getFullPath());
        }
    }

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
            'Name'       => _t('YearCalendar.Import.Name', 'Name'),
            'LastEdited' => _t('YearCalendar.Import.LastEdited', 'Last Edited'),
        ]);

        return $labels;
    }

    /**
     * Retrieve the file name for this import
     *
     * @return string
     */
    public function Name()
    {
        if ($this->ImportID) {
            /** @var File $Import */
            $Import = $this->Import();
            $filePath = $Import->getFilename();
            $filePath = explode(DIRECTORY_SEPARATOR, $filePath);

            return array_pop($filePath);
        }
    }

    /**
     * Processed a file for import
     *
     * @param $filepath
     */
    protected function processImport($filepath)
    {
        $Excel = null;
        // load the file into an Excel processor
        $ext = $this->getExtensionForFilePath($filepath);
        switch ($ext) {
            case 'csv':
                $Reader = new PHPExcel\Reader\CSV();
                $Reader->setDelimiter(';');
                $Excel = $Reader->load($filepath);
                break;
            case 'xls':
            case 'xlsx':
                $Excel = PHPExcel\IOFactory::load($filepath);
                break;
        }

        if ($Excel) {
            $Sheets = $Excel->getAllSheets();
            foreach ($Sheets as $sheet) {
                $this->ProductFactory($sheet);
            }
        }
    }

    /**
     * Retrieve the extension for the given path
     *
     * @param string $filepath
     *
     * @return string
     */
    protected function getExtensionForFilePath($filepath)
    {
        return substr($filepath, strrpos($filepath, '.') + 1);
    }

    /**
     *
     *
     * @param \PHPExcel\Worksheet $sheet
     *
     * @return SimpleProduct
     */
    protected function ProductFactory(\PHPExcel\Worksheet $sheet)
    {
        $product = null;
        $rows = $sheet->getHighestRow();

        for ($i = 1; $i <= $rows; $i++) {
            $Title = $sheet->getCell(sprintf('A%1$d', $i))
                ->getValue();
            if ($Title == self::HEADERCELL) {
                // header row
                continue;
            }
            if (!$Title) {
                // empty title is not allowed, silently ignore it
                continue;
            }

            $yearcalendaritem = new YearCalendarItem();

            $From = $this->createDateTimeFromCells(
                $sheet->getCell(sprintf('B%1$d', $i)),
                $sheet->getCell(sprintf('C%1$d', $i))
            );

            $To = $this->createDateTimeFromCells(
                $sheet->getCell(sprintf('D%1$d', $i)),
                $sheet->getCell(sprintf('E%1$d', $i))
            );

            $WholeDay = $this->createBoolFromCell(
                $sheet->getCell(sprintf('F%1$d', $i))
                    ->getValue(),
                'Ja',
                'Nee'
            );

            if ($WholeDay) {
                $From->setTimeToDayStart();
                $To->setTimeToDayEnd();
            }

            $Content = $this->getProperContent($sheet->getCell(sprintf('G%1$d', $i))
                ->getValue());

            $Tags = $this->getTagsByCell($sheet->getCell(sprintf('H%1$d', $i))
                ->getValue());

            $yearcalendaritem->Title = $Title;
            $yearcalendaritem->From = $From->format('Y-m-d H:i:s');
            $yearcalendaritem->To = $To->format('Y-m-d H:i:s');
            $yearcalendaritem->WholeDay = $WholeDay;
            $yearcalendaritem->Content = $Content;

            if ($Tags->count()) {
                $yearcalendaritem->Tags()
                    ->addMany($Tags);
            }

            $yearcalendaritem->write();
        }
    }

    /**
     * Create a DateTime object from two strings representing date and time
     *
     * @param PHPExcel\Cell $date dd-mm-[yy]yy
     * @param PHPExcel\Cell $time hh:ii[:ss]
     *
     * @return \DateTimeHelper
     */
    protected function createDateTimeFromCells(PHPExcel\Cell $dateCell, PHPExcel\Cell $timeCell)
    {
        $date = date('d/m/Y', PHPExcel\Shared\Date::ExcelToPHP($dateCell->getValue()));

        @list ($day, $month, $year) = explode('/', $date);
        if (strlen($year) < 4) {
            $year = sprintf('20%1$d', $year);
        }

        $time = PHPExcel\Style\NumberFormat::toFormattedString($timeCell->getCalculatedValue(), 'hh:mm:ss');
        // falback for empty time fields
        if (!$time) {
            $time = '00:00:00';
        }
        $dateTime = new DateTimeHelper(sprintf('%1$d-%2$d-%3$d %4$s', $year, $month, $day, $time));
        return $dateTime;
    }

    /**
     * Create a boolean value from any given parameter by comparing them to given values for true and false
     *
     * @param mixed $value
     * @param bool  $true
     * @param bool  $false
     *
     * @return bool|null
     */
    protected function createBoolFromCell($value, $true = true, $false = false)
    {
        if ($value == $true) {
            return true;
        }
        if ($value == $false) {
            return false;
        }

        return null;
    }

    /**
     * Retrieve proper content from given string
     *
     * @param string $content
     *
     * @return string
     */
    protected function getProperContent($content)
    {
        return sprintf('<p>%1$s</p>', nl2br(strip_tags($content)));
    }

    /**
     * Return a list of AgendaTags by cell value
     *
     * @param string $tags
     *
     * @return \ArrayList
     */
    protected function getTagsByCell($tags)
    {
        $tags = explode(',', $tags);
        foreach ($tags as $idx => &$tag) {
            // if tag consists of whitespace, consider it empty
            $tag = trim($tag);
            if (!$tag) {
                unset($tags[$idx]);
                unset($tag);
                continue;
            }
            $Tag = YearCalendarItemTag::get()
                ->filter([
                    'Title' => $tag,
                ])
                ->first();

            if (!$Tag) {
                $Tag = new YearCalendarItemTag();
                $Tag->Title = $tag;
                $Tag->write();
            }

            $tag = $Tag;
            unset($tag);
        }

        return new ArrayList($tags);
    }

    /**
     * Prevent CMS users from creating more than one entry.
     *
     * @see SiteTree::canCreate()
     * @return Boolean
     */
    function canCreate($member = null)
    {
        return !(bool)self::get()
            ->count();
    }
}