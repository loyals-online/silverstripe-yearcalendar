<?php

/**
 * Adds a Purge button to the top of a {@link GridField}.
 *
 * @package    forms
 * @subpackage fields-gridfield
 */
class GridFieldPurgeYearCalendarButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
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
            'purge',
            _t('YearCalendarAdmin.PURGE', 'Purge all events'),
            'purge',
            null
        );
        $button->setAttribute('data-icon', 'delete');
        $button->addExtraClass('no-ajax');

        return array(
            $this->targetFragment => '<p class="grid-purge-button">' . $button->Field() . '</p>',
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
        return array('purge');
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
        if ($actionName == 'purge') {
            foreach(YearCalendarItem::get() as $item) {
                $item->delete();
            }
        }
        Controller::curr()->redirectBack();
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
            'purge' => 'handleAction',
        );
    }
}
