<?php

namespace SixBySix\UkBankHolidays;

/**
 * Class Feed
 * @package SixBySix\UkBankHolidays
 */
class Feed
{
    const FEED_URL = 'https://www.gov.uk/bank-holidays.json';

    /** @var bool */
    static protected $hasBeenLoaded = false;

    /** @var \stdClass[] */
    static protected $divisions;

    /** @var \stdClass[] */
    static protected $events;

    /**
     * @param \DateTime $dateTime
     * @param null|string $division
     * @return null|\stdClass[]
     * @throws \Exception
     */
    static public function getHolidays(\DateTime $dateTime, $division = null)
    {
        self::loadHolidayData();

        /** @var \stdClass $events */
        $events = self::filterByDivision($division);

        /** @var string $dateKey */
        $dateKey = $dateTime->format('Y-m-d');

        return (isset($events[$dateKey])) ? $events[$dateKey] : null;
    }

    /**
     * @param \DateTime $dateTime
     * @param string    $division
     * @return bool
     */
    static public function isHoliday(\DateTime $dateTime, $division = null)
    {
        self::loadHolidayData();

        return sizeof(self::getHolidays($dateTime, $division)) > 0;
    }

    static public function getDivisions()
    {
        self::loadHolidayData();
        return self::$divisions;
    }

    static protected function loadHolidayData()
    {
        if (self::$hasBeenLoaded) {
            return;
        }

        /** @var string $url */
        $url = self::FEED_URL;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, self::FEED_URL);

        /** @var string $json */
        $json = curl_exec($ch);
        curl_close($ch);

        /** @var \stdClass $data */
        $data = json_decode($json);

        if (!$data) {
            throw new \Exception("Unable to load holiday data from $url");
        }

        self::$divisions = array();

        /**
         * @var $division string
         * @var $divisionData \stdClass
         */
        foreach ($data as $division => $divisionData) {
            self::$divisions[$division] = array();

            /**
             * @var $event \stdClass
             */
            foreach ($divisionData->events as $event) {
                /** @var string $date */
                $date = "{$event->date}";

                if (!isset(self::$events[$date])) {
                    self::$events[$date] = array();
                }

                self::$events[$date][] = $event;
                self::$divisions[$division][$date][] = $event;
            }
        }

        self::$hasBeenLoaded = true;
    }

    /**
     * @param $division
     * @return \stdClass|\stdClass[]
     * @throws \Exception
     */
    protected function filterByDivision($division)
    {
        if (is_null($division)){
            return self::$events;
        }

        if (!isset(self::$divisions["$division"])) {
            throw new \Exception("Invalid division specified '{$division}'");
        }

        return self::$divisions["$division"];
    }
}