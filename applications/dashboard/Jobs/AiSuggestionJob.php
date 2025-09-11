<?php

use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Schema\RangeExpression;

/**
 * AI suggestion cron job.
 */
class AiSuggestionJob extends \Vanilla\Scheduler\Job\LocalApiJob implements \Psr\Log\LoggerAwareInterface
{
    use \Psr\Log\LoggerAwareTrait;

    /**
     * D.I.
     *
     * @param LongRunner $longRunner
     * @param AiSuggestionSourceService $aiSuggestionSourceService
     * @param DiscussionModel $discussionModel
     */
    public function __construct(
        private LongRunner $longRunner,
        private AiSuggestionSourceService $aiSuggestionSourceService,
        private DiscussionModel $discussionModel
    ) {
    }

    /**
     * @inheritdoc
     */
    public function setMessage(array $message)
    {
        return;
    }

    /**
     * @inheritdoc
     */
    public function run(): \Vanilla\Scheduler\Job\JobExecutionStatus
    {
        $delayConfig = \Vanilla\Dashboard\Models\AiSuggestionSourceService::aiSuggestionConfigs()["delay"] ?? [
            "length" => 0,
            "unit" => "hour",
        ];
        $currentDate = CurrentTimeStamp::getDateTime();

        $upperTimeThreshold = $currentDate->sub(
            DateInterval::createFromDateString(
                $delayConfig["length"] .
                    " " .
                    plural($delayConfig["length"], $delayConfig["unit"], $delayConfig["unit"] . "s")
            )
        );

        // We'll look back 1 hour in case any previous discussions were missed.
        $lowerTimeThreshold = $upperTimeThreshold->sub(DateInterval::createFromDateString("1 hour"));

        $timeRange = new RangeExpression(">=", $lowerTimeThreshold, "<=", $upperTimeThreshold);

        $discussions = $this->discussionModel->getWhere(["DateInserted" => $timeRange, "statusID" => 1])->resultArray();
        $discussions = array_filter($discussions, function ($discussion) {
            $alreadyProcessed = $discussion["Attributes"]["aiSuggestions"] ?? false;
            return $alreadyProcessed === false;
        });

        foreach ($discussions as $discussion) {
            $action = new LongRunnerAction(AiSuggestionSourceService::class, "generateSuggestions", [
                $discussion["DiscussionID"],
                true,
            ]);
            $trackingID = $this->longRunner->runDeferred($action)->getTrackingID();
            $this->discussionModel->saveToSerializedColumn(
                "Attributes",
                $discussion["DiscussionID"],
                "aiSuggestions",
                $trackingID
            );
        }

        return \Vanilla\Scheduler\Job\JobExecutionStatus::complete();
    }

    /**
     * Get the cron expression to run this job.
     *
     * @return string
     */
    public static function getCronExpression(): string
    {
        // Run every fifteen minutes
        return "*/15 * * * *";
    }
}
