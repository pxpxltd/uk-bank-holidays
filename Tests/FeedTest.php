<?php

namespace SixBySix\UkBankHolidays\Tests;

use PHPUnit\Framework\TestCase;
use SixBySix\UkBankHolidays\Feed;

class FeedTest extends TestCase
{
    /**
     * @test
     */
    public function getDivisions()
    {
        $divisions = Feed::getDivisions();
        $this->assertInternalType('array', $divisions);
        $this->assertArrayHasKey('scotland', $divisions);
        $this->assertArrayHasKey('england-and-wales', $divisions);
        $this->assertArrayHasKey('northern-ireland', $divisions);
        $this->assertTrue(sizeof($divisions) == 3);
    }

    /**
     * @test
     */
    public function getHolidays()
    {
        $date = new \DateTime('26th December');

        $holidays = Feed::getHolidays($date);

        $this->assertSameSize(Feed::getDivisions(), $holidays);
    }

    /**
     * @test
     */
    public function isHoliday()
    {
        $date = new \DateTime('26th December');

        $this->assertTrue(Feed::isHoliday($date));
    }

    /**
     * @test
     */
    public function isHolidayScotland()
    {
        $date = new \DateTime('26th December');

        $this->assertTrue(Feed::isHoliday($date, 'scotland'));
    }
}