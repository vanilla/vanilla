<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\Dashboard\Models\AggregateCountModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;

/**
 * API Controller for the `/dba` resource.
 */
class DbaApiController extends AbstractApiController
{
    /** @var LongRunner */
    private $longRunner;

    /** DbaApiController Constructor
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
    public function patch_counts(array $body = []): Data
    {
        /* @Note: Primary draft changes for the current Count functionality  Should revisit */
        $this->permission("Garden.Moderation.Manage");
        $in = Schema::parse([
            "tables:a" => [
                "items" => [
                    "type" => "string",
                    "enum" => ["discussion", "conversation", "category"],
                ],
            ],
        ]);

        $body = $in->validate($body);
        $tables = $body["tables"];
        $options = [];
        foreach ($tables as $table) {
            switch ($table) {
                case "discussion":
                    $options[] = [
                        "table" => "Discussion",
                        "columns" => [
                            "CountComments",
                            "FirstCommentID",
                            "LastCommentID",
                            "DateLastComment",
                            "LastCommentUserID",
                        ],
                        "processedColumns" => [],
                        "primaryField" => "DiscussionID",
                        "alias" => "CountDiscussions",
                    ];

                    break;
                case "conversation":
                    $options[] = [
                        "table" => "Conversation",
                        "columns" => [
                            "CountMessages",
                            "CountParticipants",
                            "FirstMessageID",
                            "LastMessageID",
                            "DateUpdated",
                            "UpdateUserID",
                        ],
                        "processedColumns" => [],
                        "primaryField" => "ConversationID",
                        "alias" => "CountConversations",
                    ];
                    break;
                case "category":
                    $catOptions = [
                        "table" => "Category",
                        "columns" => [
                            "CountChildCategories",
                            "CountDiscussions",
                            "CountComments",
                            "LastDiscussionID",
                            "LastCommentID",
                            "LastDateInserted",
                        ],
                        "processedColumns" => [],
                        "primaryField" => "CategoryID",
                        "alias" => "CountCategory",
                    ];
                    $options[] = $catOptions;
                    /* We have to break this category processing into 2 different sets as we need to have
                     CountComments processed entirely to process CountAllComments */
                    $catOptions["columns"] = ["CountAllComments", "ParentLastCommentID", "ParentLastDateInserted"];
                    $options[] = $catOptions;
                    break;
                default:
                    break;
            }
        }
        // Defer to the LongRunner for execution.
        $result = $this->longRunner->runApi(
            new LongRunnerAction(AggregateCountModel::class, "processAggregatesIterator", [1, $options])
        );

        return $result;
    }
}
