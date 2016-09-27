<?php

/**
 * Helper wrapper for DateTime objects
 *
 * Class DateTimeHelper
 */
class DateTimeHelper extends DateTime
{
    /**
     * Sets the time of this DateTimeHelper object to the start of the day 00:00:00
     *
     */
    public function setTimeToDayStart()
    {
        $this->setTime(0, 0, 0);

        return $this;
    }

    /**
     * Sets the time of this DateTimeHelper object to the end of the day 23:59:59
     */
    public function setTimeToDayEnd()
    {
        $this->setTime(23, 59, 59);

        return $this;
    }

    /**
     * Retrieve a string representation of this object as used by SQL
     *
     * @return string
     */
    public function getSqlDateTime()
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * magic __toString
     *
     * @return string
     */
    public function __tostring()
    {
        return $this->getSqlDateTime();
    }

    /**
     * Check if this object is between the given DateTime objects
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return bool
     */
    public function isBetween(DateTime $start, DateTime $end)
    {
        return $this->format('YmdHis') >= $start->format('YmdHis') && $this->format('YmdHis') <= $end->format('YmdHis');
    }

    /**
     * Cherck if this object's date is equal to the given object's date
     *
     * @param \DateTime $date
     *
     * @return bool
     */
    public function isDateEqualTo(DateTime $date)
    {
        return $this->format('Ymd') == $date->format('Ymd');
    }

    /**
     * Retrieve a string representation of this object
     *
     * @return string
     */
    public function getDate()
    {
        return $this->format('Ymd');
    }
}