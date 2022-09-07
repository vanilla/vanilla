<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Controllers\Api;

use Garden\Schema\Schema;
use Vanilla\Models\ContentGroupModel;
use Vanilla\ApiUtils;
use Vanilla\Utility\SchemaUtils;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;

/**
 * Controller for the /content-groups endpoint
 */
class ContentGroupsApiController extends \AbstractApiController
{
    /** @var ContentGroupModel */
    protected $contentGroupModel;

    /**
     * ContentGroupsApiController constructor.
     *
     * @param ContentGroupModel $contentGroupModel
     */
    public function __construct(ContentGroupModel $contentGroupModel)
    {
        $this->contentGroupModel = $contentGroupModel;
    }

    /**
     * Get Content Groups from a query string.
     *
     * @param array $query
     * @return Data
     */
    public function index(array $query): Data
    {
        $this->permission("community.manage");
        $in = Schema::parse([
            "contentGroupID?" => \Vanilla\Schema\RangeExpression::createSchema([":int"]),
            "name:s?",
            "page:i?" => [
                "description" => "Page number. [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "description" => "Desired number of content group records.",
                "minimum" => 1,
                "default" => ContentGroupModel::LIMIT_DEFAULT,
            ],
        ]);
        $query = $in->validate($query);
        $options = [];
        [$options["offset"], $options["limit"]] = offsetLimit("p{$query["page"]}", $query["limit"]);
        $where = [];
        if (isset($query["contentGroupID"])) {
            $where["contentGroupID"] = $query["contentGroupID"];
        }
        if (isset($query["name"])) {
            $where["name"] = $query["name"];
        }
        $results = $this->contentGroupModel->searchContentGroupRecords($where, $options);
        $out = Schema::parse([
            "contentGroupID:i",
            "name:s",
            "dateInserted:dt",
            "dateUpdated:dt|n",
            "insertUserID:i",
            "updateUserID:i|n",
            "records:a" => $this->contentGroupRecordSchema(),
        ]);
        SchemaUtils::validateArray($results, $out, true);

        $paging = ApiUtils::morePagerInfo($results, "/api/v2/content-group/", $query, $in);

        return new Data($results, ["paging" => $paging]);
    }

    /**
     * Get a single content Group and its records
     *
     * @param int $contentGroupID
     * @return Data
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\HttpException
     * @throws \Vanilla\Exception\Database\NoResultsException
     * @throws \Vanilla\Exception\PermissionException
     */
    public function get(int $contentGroupID): Data
    {
        $this->permission("community.manage");
        $result = $this->contentGroupModel->getContentGroupRecordByID($contentGroupID);
        $out = Schema::parse([
            "contentGroupID:i",
            "name:s",
            "dateInserted:dt",
            "dateUpdated:dt|n",
            "insertUserID:i",
            "updateUserID:i|n",
            "records:a" => $this->contentGroupRecordSchema(),
        ]);
        $result = $out->validate($result);

        return new Data($result);
    }

    /**
     * Deleta a content Group
     *
     * @param int $contentGroupID
     * @return void
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\HttpException
     * @throws \Vanilla\Exception\Database\NoResultsException
     * @throws \Vanilla\Exception\PermissionException
     */
    public function delete(int $contentGroupID): void
    {
        $this->permission("community.manage");
        $this->contentGroupModel->selectSingle(["contentGroupID" => $contentGroupID]);
        $this->contentGroupModel->deleteContentGroup($contentGroupID);
    }

    /**
     * Create a new content group with records
     *
     * @param array $body
     * @return Data
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\ClientException
     */
    public function post(array $body): Data
    {
        $this->permission("community.manage");
        $in = $this->postSchema();
        $body = $in->validate($body);
        // save the content Record
        $contentGroupID = $this->contentGroupModel->saveContentGroup($body);

        return $this->get($contentGroupID);
    }

    /**
     * Update content group and its contents
     *
     * @param int $contentGroupID
     * @param array $body
     * @return Data
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\HttpException
     * @throws \Vanilla\Exception\Database\NoResultsException
     * @throws \Vanilla\Exception\PermissionException
     */
    public function patch(int $contentGroupID, array $body): Data
    {
        $this->permission("community.manage");
        $in = $this->patchSchema();
        $body = $in->validate($body);

        $contentGroupRecord = $this->contentGroupModel->select(["contentGroupID" => $contentGroupID]);
        if (empty($contentGroupRecord)) {
            throw new NotFoundException("Content Group");
        }
        $this->contentGroupModel->updateContentGroup($contentGroupID, $body);

        return $this->get($contentGroupID);
    }

    /**
     * @return Schema
     */
    private function postSchema(): Schema
    {
        return Schema::parse([
            "name:s" => ["minLength" => 1, "maxLength" => 255],
            "records" => [
                "type" => "array",
                "minItems" => 1,
                "maxItems" => 30,
                "items" => $this->contentGroupRecordSchema(),
            ],
        ])->addValidator("records", [$this->contentGroupModel, "validateContentGroupRecords"]);
    }

    /**
     * @return Schema
     */
    private function patchSchema(): Schema
    {
        return Schema::parse(["name?", "records?"])
            ->add($this->postSchema())
            ->addValidator("records", [$this->contentGroupModel, "validateContentGroupRecords"]);
    }

    /**
     * Get a schema representing contentGroup records.
     *
     * @return Schema
     */
    private function contentGroupRecordSchema(): Schema
    {
        return Schema::parse([
            "recordID:i",
            "recordType:s" => ["enum" => $this->contentGroupModel->getAllRecordTypes()],
            "sort:i?",
        ]);
    }
}
