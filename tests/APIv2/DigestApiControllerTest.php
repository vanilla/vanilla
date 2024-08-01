<?php

/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Forum\Digest\DigestModel;
use Vanilla\Forum\Digest\ScheduleWeeklyDigestJob;

class DigestApiControllerTest extends AbstractAPIv2Test
{
    protected DigestModel $digestModel;

    protected ConfigurationInterface $config;

    protected ScheduleWeeklyDigestJob $scheduleWeeklyDigestJob;

    public function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = "/digest";
        $this->digestModel = $this->container()->get(DigestModel::class);
        $this->config = $this->container()->get(ConfigurationInterface::class);
        $this->scheduleWeeklyDigestJob = $this->container()->get(ScheduleWeeklyDigestJob::class);
        $this->resetTable("digest");
    }

    /**
     * Test Generate Digest dates
     *
     * @return void
     */
    public function testGetDigestDates(): void
    {
        //Without digest enabled
        $response = $this->api()->get("{$this->baseUrl}/delivery-dates");
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $expected = [
            "sent" => [],
            "scheduled" => [],
            "upcoming" => [],
        ];

        $this->assertEquals($expected, $body);

        //With digest configs enabled
        $this->runWithConfig(
            [
                "Garden.Digest.Enabled" => true,
                "Garden.Digest.DayOfWeek" => 1.0,
            ],
            function () {
                $currentDate = CurrentTimeStamp::mockTime("2021-05-05T09:05:30+05:00");
                $nextScheduledDate = $currentDate
                    ->modify("next monday")
                    ->setTime(13, 0)
                    ->setTimezone(new \DateTimeZone("UTC"));
                $upcomingDates = [];
                $upcomingDates[] = $nextScheduledDate->format(DATE_ATOM);
                for ($i = 1; $i <= 4; $i++) {
                    $nextScheduledDate = $nextScheduledDate->modify("+1 week");
                    $upcomingDates[] = $nextScheduledDate->format(DATE_ATOM);
                }
                $expectedData = [
                    "sent" => [],
                    "scheduled" => [],
                    "upcoming" => $upcomingDates,
                ];

                $response = $this->api()->get("{$this->baseUrl}/delivery-dates");
                $this->assertEquals(200, $response->getStatusCode());
                $body = $response->getBody();
                $this->assertEquals($expectedData, $body);

                //make some entries to the digest table
                $date = CurrentTimeStamp::getDateTime();
                $dateScheduled = $date->setISODate($date->format("Y") - 1, 50);
                $scheduleDates = [];
                for ($i = 0; $i < 3; $i++) {
                    $dateScheduled = $dateScheduled->modify("+1 week");
                    $this->digestModel->insert([
                        "digestType" => DigestModel::DIGEST_TYPE_WEEKLY,
                        "dateScheduled" => $dateScheduled,
                        "totalSubscribers" => $i + 10,
                    ]);
                    $scheduleDates[] = [
                        "dateScheduled" => $dateScheduled->format(DATE_ATOM),
                        "totalSubscribers" => $i + 10,
                    ];
                }
                $nextScheduledDate = $this->scheduleWeeklyDigestJob->getNextScheduledDate();
                $this->digestModel->insert([
                    "digestType" => DigestModel::DIGEST_TYPE_WEEKLY,
                    "dateScheduled" => $nextScheduledDate,
                    "totalSubscribers" => 100,
                ]);
                $nextSchedule = [
                    "dateScheduled" => $nextScheduledDate->format(DATE_ATOM),
                    "totalSubscribers" => 100,
                ];
                $result = $this->api()
                    ->get("{$this->baseUrl}/delivery-dates")
                    ->getBody();
                $this->assertEquals($scheduleDates, $result["sent"]);
                $this->assertEquals($nextSchedule, $result["scheduled"]);
            }
        );
    }

    /**
     * Test generate Digest dates with one scheduled email
     */
    public function testGenerateDigestDatesWithEmailDigestScheduled()
    {
        $nextScheduleDate = $this->scheduleWeeklyDigestJob->getNextScheduledDate();
        $this->digestModel->insert([
            "digestType" => DigestModel::DIGEST_TYPE_WEEKLY,
            "dateScheduled" => $nextScheduleDate,
            "totalSubscribers" => 50,
        ]);
        $result = $this->api()
            ->get("{$this->baseUrl}/delivery-dates")
            ->getBody();

        $expected = [
            "sent" => [],
            "scheduled" => [
                "dateScheduled" => $nextScheduleDate->format(DATE_ATOM),
                "totalSubscribers" => 50,
            ],
            "upcoming" => [],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test Digest generation dates with changing configuration for weekday schedule of digests
     */
    public function testGenerateDigestDatesWithDeliveryDateConfigChanges(): void
    {
        $currentTimeZone = new \DateTimeZone("America/New_York");
        $utcTimeZone = new \DateTimeZone("UTC");
        $nextScheduleDate = new \DateTimeImmutable("now", $currentTimeZone);
        $nextScheduleDate = $nextScheduleDate->setTime(9, 0)->setTimezone($utcTimeZone);

        $dayOfWeek = date("w", $nextScheduleDate->getTimestamp());
        $this->runWithConfig(
            [
                "Garden.Digest.Enabled" => true,
                "Garden.Digest.DayOfWeek" => $dayOfWeek,
            ],
            function () use ($dayOfWeek, $nextScheduleDate, $currentTimeZone, $utcTimeZone) {
                //Covert back for original timezone for calculations
                CurrentTimeStamp::mockTime($nextScheduleDate);
                //This will be scheduled today
                $this->digestModel->insert([
                    "digestType" => DigestModel::DIGEST_TYPE_WEEKLY,
                    "dateScheduled" => $nextScheduleDate,
                    "totalSubscribers" => 50,
                ]);
                $nextScheduleDate = $nextScheduleDate->setTimezone($currentTimeZone);
                $nextScheduledWeekDay = $dayOfWeek == 0 ? 6 : $dayOfWeek - 1;
                $this->config->saveToConfig("Garden.Digest.DayOfWeek", $nextScheduledWeekDay);
                //Since we have one scheduled for this week the expected date will be on the next week of the scheduled week day
                $upcomingScheduledDate = $nextScheduleDate
                    ->modify("+1 week")
                    ->modify("-1 day")
                    ->setTimeZone($utcTimeZone);
                $result = $this->api()
                    ->get("{$this->baseUrl}/delivery-dates")
                    ->getBody();
                $this->assertEquals($upcomingScheduledDate->format(DATE_ATOM), $result["upcoming"][0]);
                $nextScheduledWeekDay = $dayOfWeek == 6 ? 0 : $dayOfWeek + 1;
                $this->config->saveToConfig("Garden.Digest.DayOfWeek", $nextScheduledWeekDay);
                $upcomingScheduledDate = $nextScheduleDate
                    ->modify("+1 week")
                    ->modify("+1 day")
                    ->setTimeZone($utcTimeZone);
                $result = $this->api()
                    ->get("{$this->baseUrl}/delivery-dates")
                    ->getBody();
                $this->assertEquals($upcomingScheduledDate->format(DATE_ATOM), $result["upcoming"][0]);
            }
        );
    }

    /**
     * Test that if we pass a week day to the api endpoint it will override the config
     *
     * @return void
     * @throws \Exception
     */
    public function testDigestDateWithBody()
    {
        $this->runWithConfig(
            [
                "Garden.Digest.Enabled" => true,
                "Garden.Digest.DayOfWeek" => 1,
            ],
            function () {
                $defaultScheduleDate = new \DateTimeImmutable("2023-04-04", new \DateTimeZone("America/New_York"));
                CurrentTimeStamp::mockTime("2023-04-01");
                $defaultScheduleDate = $defaultScheduleDate->setTime(9, 0);
                $nextExpectedScheduledDate = new \DateTimeImmutable("2023-04-04", new \DateTimeZone("UTC"));
                if (
                    intval($nextExpectedScheduledDate->format("w") == 2) &&
                    $nextExpectedScheduledDate->format("H") < 13
                ) {
                    $nextExpectedScheduledDate = $defaultScheduleDate->setTimezone(new \DateTimeZone("UTC"));
                } else {
                    $nextExpectedScheduledDate = $defaultScheduleDate->modify("next tuesday")->setTime(9, 0);
                    $nextExpectedScheduledDate = $nextExpectedScheduledDate->setTimezone(new \DateTimeZone("UTC"));
                }

                $result = $this->api()
                    ->get("{$this->baseUrl}/delivery-dates", ["dayOfWeek" => 2])
                    ->getBody();
                $this->assertEquals($nextExpectedScheduledDate->format(DATE_ATOM), $result["upcoming"][0]);
            }
        );
    }

    /**
     * Test validation on input
     *
     * @return void
     */
    public function testValidationOnDeliveryDates()
    {
        $this->runWithConfig(
            [
                "Garden.Digest.Enabled" => true,
                "Garden.Digest.DayOfWeek" => 1,
            ],
            function () {
                $this->expectExceptionMessage("value is greater than 7.");
                $this->expectException(ClientException::class);
                $result = $this->api()
                    ->get("{$this->baseUrl}/delivery-dates", ["dayOfWeek" => 9])
                    ->getBody();
            }
        );
    }
}
