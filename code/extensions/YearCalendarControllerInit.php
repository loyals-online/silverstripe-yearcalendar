<?php

class YearCalendarControllerInit extends Extension
{
    public function getRequiredJavascript()
    {
        return [
            YEARCALENDAR_DIR . '/javascript/yearcalendar.js',
        ];
    }
}