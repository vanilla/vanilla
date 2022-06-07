<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\controller\api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Models\UserMentionsModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;

/**
 * Controller for the `/user-mentions` endpoints.
 */
class UserMentionsApiController extends AbstractApiController
{
    /** @var UserMentionsModel */
    private $userMentionModel;

    /** @var LongRunner */
    private $longRunner;

    /**
     * UserMentionsApiController constructor.
     *
     * @param UserMentionsModel $userMentionsModel
     * @param LongRunner $longRunner
     */
    public function __construct(UserMentionsModel $userMentionsModel, LongRunner $longRunner)
    {
        $this->longRunner = $longRunner;
        $this->userMentionModel = $userMentionsModel;
    }

    /**
     * Fetch the user mentions for a specific ID.
     *
     * [GET] `/user-mentions/users/{userID}`
     *
     * @param int $userID
     * @param array $query
     *
     * @return Data
     */
    public function get_users(int $userID, array $query): Data
    {
        $this->permission("site.manage");

        $in = Schema::parse([
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "description" => "Desired number of items per page.",
                "default" => 30,
                "minimum" => 1,
                "maximum" => ApiUtils::getMaxLimit(),
            ],
        ]);
        $query = $in->validate($query);

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);
        $result = $this->userMentionModel->getByUser($userID, $limit, $offset);

        $out = $this->schema([":a" => $this->getUserMentionSchema()], "out");
        $result = $out->validate($result);
        return new Data($result);
    }

    /**
     * Start the indexing process.
     *
     * [POST] `/user-mentions/index`
     *
     * @param array $query
     *
     * @return Data
     */
    public function post_indexerStart(array $query)
    {
        $this->permission("site.manage");
        $in = $this->schema([
            "recordType:s?" => [
                "description" => "RecordType to index the UserMentions from",
                "enum" => ["discussion", "comment", "all"],
                "default" => "all",
            ],
        ]);
        $query = $in->validate($query);
        $models = [];

        if ($query["recordType"] === "all") {
            $models = UserMentionsModel::INDEXABLE_RECORDS;
        } else {
            $models[] = UserMentionsModel::INDEXABLE_RECORDS[$query["recordType"]];
        }

        $response = $this->longRunner->runApi(
            new LongRunnerAction(UserMentionsModel::class, "indexUserMentions", [$models])
        );
        return new Data($response);
    }

    /**
     * Get the User Mention Schema.
     *
     * @return Schema
     */
    public function getUserMentionSchema(): Schema
    {
        $schema = $this->schema(
            [
                "userID:i",
                "recordType:s",
                "recordID:i",
                "mentionedName:s?",
                "parentRecordType:s?",
                "parentRecordID:i?",
                "dateInserted:dt|n?",
                "status:s",
            ],
            "usermentions"
        );
        return $schema;
    }
}
