<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\ApiUtils;
use Vanilla\Models\Model;
use Vanilla\Models\DeveloperProfileModel;
use Vanilla\Utility\Timers;

/**
 * /api/v2/developer-profiles
 */
class DeveloperProfilesApiController extends \AbstractApiController
{
    private DeveloperProfileModel $profilesModel;

    /**
     * Constructor.
     */
    public function __construct(DeveloperProfileModel $recordedProfileModel)
    {
        $this->profilesModel = $recordedProfileModel;
    }

    /**
     * GET /api/v2/developer-profiles
     *
     * @param array $query
     *
     * @return Data
     */
    public function index(array $query = []): Data
    {
        $this->permission("admin.only");
        Timers::instance()->setShouldRecordProfile(false);

        $schema = Schema::parse([
            "sort?" => ApiUtils::sortEnum("dateRecorded", "requestElapsedMs"),
            "limit:i?" => [
                "default" => 100,
            ],
            "page:i?" => [
                "default" => 1,
            ],
            "name:s?" => [
                "maxLength" => 255,
            ],
            "isTracked:b?",
        ]);

        $query = $schema->validate($query);

        [$offset, $limit] = offsetLimit("p" . $query["page"], $query["limit"]);

        $where = [];
        if ($query["name"] ?? false) {
            $where["name LIKE"] = "{$query["name"]}%s";
        }

        if (isset($query["isTracked"])) {
            $where["isTracked"] = $query["isTracked"];
        }

        $records = $this->profilesModel->select($where, [
            Model::OPT_ORDER => $query["sort"],
            Model::OPT_OFFSET => $offset,
            Model::OPT_LIMIT => $limit,
            Model::OPT_SELECT => ["-profile", "-timers"],
        ]);

        $pagingCount = $this->profilesModel->getPagingCount($where);
        $paging = ApiUtils::numberedPagerInfo($pagingCount, "/api/v2/developer-profiles", $query, $schema);

        return new Data($records, [
            "paging" => $paging,
        ]);
    }

    /**
     * GET /api/v2/developer-profiles/:developerProfileID
     *
     * @param int $id
     *
     * @return Data
     */
    public function get(int $id): Data
    {
        $this->permission("admin.only");
        Timers::instance()->setShouldRecordProfile(false);

        $record = $this->profilesModel->selectSingle([
            "developerProfileID" => $id,
        ]);

        return new Data($record);
    }

    /**
     * PATCH /api/v2/developer-profiles/:developerProfileID {BODY}
     *
     * @param int $id
     * @param array $body
     *
     * @return Data
     */
    public function patch(int $id, array $body)
    {
        $this->permission("admin.only");
        Timers::instance()->setShouldRecordProfile(false);

        $schema = $this->schema(["name:s?", "isTracked:b?"])->add($this->profilesModel->getWriteSchema());
        $body = $schema->validate($body);

        // Make sure the profile exists.
        $this->profilesModel->selectSingle([
            "developerProfileID" => $id,
        ]);

        $this->profilesModel->update($body, [
            "developerProfileID" => $id,
        ]);

        return $this->get($id);
    }
}
