<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Forum\Digest\DigestModel;
use Vanilla\Forum\Digest\ScheduleWeeklyDigestJob;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Web\Controller;

/**
 * /api/v2/digest
 */
class DigestApiController extends Controller
{
    /**
     * Constructor
     *
     * @param DigestModel $digestModel
     * @param ScheduleWeeklyDigestJob $scheduleWeeklyDigestJob
     * @param ConfigurationInterface $config
     * @param LongRunner $longRunner
     */
    public function __construct(
        private DigestModel $digestModel,
        private ScheduleWeeklyDigestJob $scheduleWeeklyDigestJob,
        private ConfigurationInterface $config,
        private LongRunner $longRunner
    ) {
    }

    /**
     * Get digest delivery dates
     */
    public function get_deliveryDates(array $query): Data
    {
        $this->permission("Garden.Settings.Manage");
        $dayOfWeek = $query["dayOfWeek"] ?? null;
        if (!empty($dayOfWeek)) {
            $in = $this->schema(
                Schema::parse([
                    ":i" => [
                        "maximum" => 7,
                    ],
                ])
            );
            $in->validate($dayOfWeek);
        }
        $data = [
            "sent" => [],
            "scheduled" => [],
            "upcoming" => [],
        ];
        $now = CurrentTimeStamp::getDateTime()->setTimezone(new \DateTimeZone("UTC"));
        $recentScheduledDates = $this->digestModel->getWeeklyDigestHistory();
        if (!empty($recentScheduledDates)) {
            if ($recentScheduledDates[0]["dateScheduled"]->getTimeStamp() > $now->getTimestamp()) {
                $data["scheduled"] = $recentScheduledDates[0];
                if (count($recentScheduledDates) > 1) {
                    array_shift($recentScheduledDates);
                    $data["sent"] = array_reverse($recentScheduledDates);
                }
            } else {
                $data["sent"] = array_reverse($recentScheduledDates);
            }
        }
        $data["upcoming"] = $this->getFutureScheduledDates($dayOfWeek ?? null);
        $out = $this->schema($this->deliveryDateSchema(), "out");
        $result = $out->validate($data);
        return new Data($result);
    }

    /**
     * Get the future digest generation date
     *
     * @param ?int $dayOfWeek
     * @return array
     */
    private function getFutureScheduledDates(?int $dayOfWeek = null): array
    {
        $upcomingSchedules = [];
        $nextScheduledDay = "";
        $maxIterations = 5;
        // Re fetch the config as it might have changed  since the last time we fetched it.
        $this->scheduleWeeklyDigestJob->initializeConfig();
        for ($i = 0; $i < $maxIterations; $i++) {
            $nextScheduledDay =
                $i == 0
                    ? $this->scheduleWeeklyDigestJob->getNextScheduledDate($dayOfWeek)
                    : $nextScheduledDay->modify("+1 week");
            $upcomingSchedules[] = $nextScheduledDay->format("Y-m-d H:i:s");
        }
        return $upcomingSchedules;
    }

    /**
     * Autosubscribe users to the digest who have logged in after a certain date.
     *
     * @param array $body
     * @return Data
     */
    public function post_backfillOptin(array $body): Data
    {
        $this->permission("site.manage");

        $in = $this->schema(["dateLastActive:dt"]);

        $in->addValidator("dateLastActive", function ($dateLastActive, $field) {
            $now = \Vanilla\CurrentTimeStamp::getDateTime();
            $fiveYearsAgo = $now->modify("-5 years");
            if ($dateLastActive < $fiveYearsAgo) {
                $field->addError("The dateLastActive must be within the last 5 years.");
                return Invalid::value();
            }
            return $dateLastActive;
        });

        $body = $in->validate($body, $in);

        $dateLastActive =
            $body["dateLastActive"] instanceof \DateTimeImmutable
                ? $body["dateLastActive"]->format("Y-m-d")
                : $body["dateLastActive"];

        $result = $this->longRunner->runApi(
            new LongRunnerAction(DigestModel::class, "backfillOptInIterator", [$dateLastActive])
        );

        return new Data($result);
    }

    /**
     * schema for  digest scheduled dates.
     *
     * @return Schema
     */
    private function deliveryDateSchema(): Schema
    {
        return Schema::parse([
            "sent:a" => [
                "type" => "array",
                "items" => $this->sentDigestSchema(),
            ],
            "scheduled:o" => ["dateScheduled:dt?", "totalSubscribers:i|n?"],
            "upcoming:a" => "dt",
        ]);
    }

    /**
     * Sent Digest Schema
     * @return Schema
     */
    private function sentDigestSchema(): Schema
    {
        return Schema::parse(["dateScheduled:dt", "totalSubscribers:i|n"]);
    }
}
