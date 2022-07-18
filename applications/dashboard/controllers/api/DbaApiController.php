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
                "LastDiscussionID",
                "LastCommentID",
                "LastDateInserted",

                // These ones can only be done after the previous ones are complete.
                "CountAllComments",
                "ParentLastCommentID",
                "ParentLastDateInserted",
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
            $secondaryAggregates = [];
            foreach ($aggregates as $aggregate) {
                $possibleMatches = ["{$key}.$aggregate", "{$key}.*"];
                if (count(array_intersect($possibleMatches, $body["aggregates"]))) {
                    if (
                        in_array($aggregate, [
                            // These ones can only be done after the previous ones are complete.
                            "CountAllComments",
                            "ParentLastCommentID",
                            "ParentLastDateInserted",
                        ])
                    ) {
                        $secondaryAggregates[] = $aggregate;
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

            if (count($secondaryAggregates) > 0) {
                $option = clone $option;
                $option->setAggregates($secondaryAggregates);
                $actions[] = new LongRunnerAction(AggregateCountModel::class, "processAggregateOption", [
                    $option,
                    $body["batchSize"] ?? null,
                ]);
            }
        }

        // Defer to the LongRunner for execution.
        $action = count($actions) > 1 ? new LongRunnerMultiAction($actions) : $actions[0];
        $result = $this->longRunner->runApi($action);
        return $result;
    }
}
