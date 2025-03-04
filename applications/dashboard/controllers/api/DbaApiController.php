<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\Dashboard\Models\AggregateCountModel;
use Vanilla\Dashboard\Models\AggregateCountOption;
use Vanilla\Dashboard\Models\UserMentionsModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerMultiAction;

/**
 * API Controller for the `/dba` resource.
 */
class DbaApiController extends AbstractApiController
{
    /** @var LongRunner */
    private $longRunner;

    /**
     * DbaApiController Constructor
     *
     * @param LongRunner $longRunner
     */
    public function __construct(LongRunner $longRunner)
    {
        $this->longRunner = $longRunner;
    }

    /**
     * Recalculate counts for provided tables.
     * @param array $body
     * @return Data
     */
    public function put_recalculateAggregates(array $body = []): Data
    {
        $this->permission("moderation.manage");

        /** @var array<string, AggregateCountOption> $options */
        $options = [
            "discussion" => new AggregateCountOption("Discussion", DiscussionModel::class, "DiscussionID", [
                "CountComments",
                "FirstCommentID",
                "LastCommentID",
                "DateLastComment",
                "LastCommentUserID",
                "Hot",
            ]),
            "comment" => new AggregateCountOption("Comment", CommentModel::class, "CommentID", [
                "depth",
                "countChildComments",
                "scoreChildComments",
            ]),
            "conversation" => new AggregateCountOption("Conversation", ConversationModel::class, "ConversationID", [
                "CountMessages",
                "CountParticipants",
                "FirstMessageID",
                "LastMessageID",
                "DateUpdated",
                "UpdateUserID",
            ]),
            "category" => new AggregateCountOption("Category", CategoryModel::class, "CategoryID", [
                "CountChildCategories",
                "CountDiscussions",
                "CountComments",
                "CountFollowers",

                // These ones can only be done after the previous ones are complete.
                "CountAll",
                "LastPost",
            ]),
        ];

        $allowedAggregates = ["user-mentions"];
        foreach ($options as $key => $option) {
            $allowedAggregates[] = "{$key}.*";
            foreach ($option->getAggregates() as $aggregate) {
                $allowedAggregates[] = "{$key}.$aggregate";
            }
        }

        $in = Schema::parse([
            "batchSize:i?",
            "aggregates:a" => [
                "items" => [
                    "type" => "string",
                    "enum" => $allowedAggregates,
                ],
            ],
        ]);

        $body = $in->validate($body);

        $actions = [];
        if (in_array("user-mentions", $body["aggregates"])) {
            $actions[] = new LongRunnerAction(UserMentionsModel::class, "indexUserMentions", [
                [\DiscussionModel::class, CommentModel::class],
            ]);
        }

        foreach ($options as $key => $option) {
            $aggregates = $option->getAggregates();
            $finalAggregates = [];
            $specialActions = [];
            $secondaryAggregates = [];
            foreach ($aggregates as $aggregate) {
                $possibleMatches = ["{$key}.$aggregate", "{$key}.*"];
                if (count(array_intersect($possibleMatches, $body["aggregates"]))) {
                    if ($aggregate === "CountAll") {
                        $option = clone $option;
                        $option->setAggregates($secondaryAggregates);
                        $specialActions[] = new LongRunnerAction(CategoryModel::class, "recalculateAllCountAlls", []);
                    } elseif ($aggregate === "LastPost") {
                        $option = clone $option;
                        $option->setAggregates(["LastPost"]);
                        $actions[] = new LongRunnerAction(AggregateCountModel::class, "processAggregateOption", [
                            $option,
                            $body["batchSize"] ?? null,
                        ]);
                    } elseif ($aggregate === "countChildComments" || $aggregate === "scoreChildComments") {
                        // We need to calculate the depth of all comments first.
                        $option = clone $option;
                        $option->setAggregates(["depth"]);
                        $actions[] = new LongRunnerAction(AggregateCountModel::class, "processAggregateOption", [
                            $option,
                            $body["batchSize"] ?? null,
                        ]);

                        // Then we can calculate the other fields.
                        $option = clone $option;
                        $option->setAggregates([$aggregate]);
                        $actions[] = new LongRunnerAction(AggregateCountModel::class, "processAggregateOption", [
                            $option,
                            $body["batchSize"] ?? null,
                        ]);
                    } else {
                        $finalAggregates[] = $aggregate;
                    }
                }
            }
            if (count($finalAggregates) > 0) {
                $option->setAggregates($finalAggregates);
                $actions[] = new LongRunnerAction(AggregateCountModel::class, "processAggregateOption", [
                    $option,
                    $body["batchSize"] ?? null,
                ]);
            }

            foreach ($specialActions as $specialAction) {
                $actions[] = $specialAction;
            }
        }

        // Defer to the LongRunner for execution.
        $action = count($actions) > 1 ? new LongRunnerMultiAction($actions) : $actions[0];
        $result = $this->longRunner->runApi($action);
        return $result;
    }
}
