<?php

class YearCalendarAdmin extends ModelAdmin
{
    /**
     * @inheritdoc
     */
    private static $managed_models = [
        'YearCalendarItem',
        'YearCalendarItemTag',
        'YearCalendarImport',
    ];

    /**
     * @inheritdoc
     */
    private static $url_segment = 'yearcalendar';

    /**
     * @inheritdoc
     */
    private static $menu_title  = 'Year Calendar';

    /**
     * @inheritdoc
     */
    private static $menu_icon   = 'yearcalendar/images/calendar-icon.png';

    /**
     * @inheritdoc
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form      = parent::getEditForm($id, $fields);
        /** @var GridField $listfield */
        $listfield = $form->Fields()
            ->fieldByName($this->modelClass);

        if ($this->modelClass == 'YearCalendarItem') {
            /** @var GridFieldConfig $config */
            $config = $listfield->getConfig();
            $config->removeComponentsByType('GridFieldExportButton');
            $config->addComponent(new GridFieldXLSXExportButton());
            $config->addComponent(new GridFieldFilterHeader());
            $config->getComponentByType('GridFieldPaginator')->setItemsPerPage(Config::inst()->get('YearCalendarAdmin', 'itemsPerPage'));
        }

        if ($this->modelClass == 'YearCalendarItemTag') {
            $listfield->getConfig()
                ->addComponent($sort = new GridFieldOrderableRows('SortOrder'));
        }

        $listfield->getConfig()
            ->removeComponentsByType('GridFieldPaginator')
            ->addComponent($pagination = new GridFieldPaginator(500));
        $pagination->setThrowExceptionOnBadDataType(false);

        $this->extend('modifyEditForm', $form);

        return $form;
    }
}