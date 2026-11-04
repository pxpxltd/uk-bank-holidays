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

    /** @var null|callable */
    static protected $fetcher = null;

    /**
     * @param null|\DateTimeInterface $dateTime
     * @param null|string $division
     * @return null|\stdClass[]
     * @throws \Exception
     */
    static public function getHolidays(?\DateTimeInterface $dateTime = null, $division = null)
    {
        self::loadHolidayData();

        /** @var \stdClass $events */
        $events = self::filterByDivision($division);

        if (!$dateTime) {
            return $events;
        }

        /** @var string $dateKey */
        $dateKey = $dateTime->format('Y-m-d');

        return (isset($events[$dateKey])) ? $events[$dateKey] : [];
    }

    /**
     * @param \DateTimeInterface $dateTime
     * @param string    $division
     * @return bool
     */
    static public function isHoliday(\DateTimeInterface $dateTime, $division = null)
    {
        self::loadHolidayData();

        return sizeof(self::getHolidays($dateTime, $division)) > 0;
    }

    static public function getDivisions()
    {
        self::loadHolidayData();
        return self::$divisions;
    }

    /**
     * Test seam: replace the live fetch with a callable returning the raw JSON
     * string, and reset any loaded data. Pass null to restore the live fetch.
     *
     * @param null|callable $fetcher
     * @return void
     */
    static public function useFetcher(?callable $fetcher = null)
    {
        self::$fetcher = $fetcher;
        self::$hasBeenLoaded = false;
        self::$divisions = null;
        self::$events = null;
    }

    /**
     * Fetch the raw feed body, from the test seam if set, otherwise from gov.uk.
     *
     * @return string|false
     */
    static protected function fetchJson()
    {
        if (self::$fetcher !== null) {
            return call_user_func(self::$fetcher);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, self::FEED_URL);

        $json = curl_exec($ch);
        unset($ch);

        return $json;
    }

    static protected function loadHolidayData()
    {
        if (self::$hasBeenLoaded) {
            return;
        }

        /** @var string $url */
        $url = self::FEED_URL;

        $json = self::fetchJson();

        if (!is_string($json) || $json === '') {
            throw new \Exception("Unable to load holiday data from $url");
        }

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
    static protected function filterByDivision($division)
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
