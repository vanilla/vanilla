<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Digest\DigestEmail;
use Vanilla\Forum\Digest\DigestModel;
use Vanilla\Forum\Digest\ScheduleWeeklyDigestJob;
use Vanilla\Web\Controller;

/**
 * /api/v2/digest
 */
class DigestApiController extends Controller
{
    private DigestModel $digestModel;

    private ScheduleWeeklyDigestJob $scheduleWeeklyDigestJob;

    private ConfigurationInterface $config;

    /**
     * Constructor
     *
     * @param DigestModel $digestModel
     * @param ScheduleWeeklyDigestJob $scheduleWeeklyDigestJob
     * @param ConfigurationInterface $config
     */
    public function __construct(
        DigestModel $digestModel,
        ScheduleWeeklyDigestJob $scheduleWeeklyDigestJob,
        ConfigurationInterface $config
    ) {
        $this->digestModel = $digestModel;
        $this->scheduleWeeklyDigestJob = $scheduleWeeklyDigestJob;
        $this->config = $config;
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
        $isDigestEnabled =
            FeatureFlagHelper::featureEnabled(DigestEmail::FEATURE_FLAG) &&
            $this->config->get("Garden.Digest.Enabled", false);
        if ($isDigestEnabled) {
            $data["upcoming"] = $this->getFutureScheduledDates($dayOfWeek ?? null);
        }
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
