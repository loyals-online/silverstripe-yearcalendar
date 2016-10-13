<?php

/**
 * Adds an "Export list" button to the bottom of a {@link GridField}.
 *
 * @package    forms
 * @subpackage fields-gridfield
 */
class GridFieldXLSXExportButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{

    /**
     * Fragment to write the button to
     *
     * @var string
     */
    protected $targetFragment;

    /**
     * Class constructor
     *
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment = "before")
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the export button in a <p> tag below the field
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $button = new GridField_FormAction(
            $gridField,
            'export',
            _t('Calendar.ExportXLSX', 'Export to Excel'),
            'export',
            null
        );
        $button->setAttribute('data-icon', 'download-csv');
        $button->addExtraClass('no-ajax');

        return array(
            $this->targetFragment => '<p class="grid-csv-button">' . $button->Field() . '</p>',
        );
    }

    /**
     * export is an action button
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getActions($gridField)
    {
        return array('export');
    }

    /**
     * Handle the action
     *
     * @param \GridField $gridField
     * @param            $actionName
     * @param            $arguments
     * @param            $data
     *
     * @return \SS_HTTPResponse
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'export') {
            return $this->handleExport($gridField);
        }
    }

    /**
     * it is also a URL
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getURLHandlers($gridField)
    {
        return array(
            'export' => 'handleExport',
        );
    }

    /**
     * Handle the export, for both the action button and the URL
     *
     * @param GridField $gridField
     * @param null      $request
     *
     * @return SS_HTTPRequest
     */
    public function handleExport($gridField, $request = null)
    {
        ob_start();
        YearCalendarExport::create()
            ->generate();
        $output = ob_get_clean();

        return SS_HTTPRequest::send_file($output, 'calendar.xlsx', 'application/vnd.ms-excel');
    }
}
