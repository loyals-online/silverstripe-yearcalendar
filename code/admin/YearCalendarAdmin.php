<?php

class YearCalendarAdmin extends ModelAdmin
{
    private static $managed_models = [
        'YearCalendarItem',
        'YearCalendarItemTag',
        'YearCalendarImport',
    ];

    private static $url_segment = 'yearcalendar';
    private static $menu_title  = 'Year Calendar';
    private static $menu_icon   = YEARCALENDAR_DIR . '/images/calendar-icon.png';

    public function getEditForm($id = null, $fields = null)
    {
        $form      = parent::getEditForm($id, $fields);
        /** @var GridField $listfield */
        $listfield = $form->Fields()
            ->fieldByName($this->modelClass);

        if ($this->modelClass == 'YearCalendarItemTag') {
            $listfield->getConfig()
                ->addComponent($sort = new GridFieldOrderableRows('SortOrder'));
        }

        $listfield->getConfig()
            ->removeComponentsByType('GridFieldPaginator')
            ->addComponent($pagination = new GridFieldPaginator(500));
        $pagination->setThrowExceptionOnBadDataType(false);

        return $form;
    }
}