<?php

class YearCalendarControllerInit extends Extension
{
    /**
     * Retrieve the required javascripts
     *
     * @return array
     */
    public function getRequiredJavascript()
    {
        return [
            YEARCALENDAR_DIR . '/javascript/yearcalendar.js',
        ];
    }
}