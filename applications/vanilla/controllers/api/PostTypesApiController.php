<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Controllers\Api;

use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Models\Model;

class PostTypesApiController extends \AbstractApiController
{
    const GET_POST_TYPE_RESPONSE = "@@postTypes/GET_POST_TYPE_DONE";

    public function __construct(private PostTypeModel $postTypeModel)
    {
    }

    /**
     * Get a list of post types with optional filters applied.
     *
     * @param array $query
     * @return Data
     */
    public function index(array $query)
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $in = $this->schema([
            "postTypeID:s?" => ["x-filter" => true],
            "parentPostTypeID:s?" => ["x-filter" => true],
            "isOriginal:b?" => ["x-filter" => true],
            "isActive:b?" => ["x-filter" => true],
            "includeDeleted:b?",
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
        $out = $this->schema([":a" => $this->postTypeModel->outputSchema()], "out");

        $query = $in->validate($query);

        $filters = ApiUtils::queryToFilters($in, $query);
        $filters["isDeleted"] = 0;

        if ($query["includeDeleted"] ?? false) {
            unset($filters["isDeleted"]);
        }

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $rows = $this->postTypeModel->getWhere($filters, [
            Model::OPT_LIMIT => $limit,
            Model::OPT_OFFSET => $offset,
        ]);

        $rows = $out->validate($rows);

        $totalCount = $this->postTypeModel->getWhereCount($filters);

        $paging = ApiUtils::numberedPagerInfo($totalCount, "/api/v2/post-types", $query, $in);

        return new Data($rows, ["paging" => $paging]);
    }

    /**
     * Get a post type.
     *
     * @param string $id
     * @param array $query
     * @return array
     */
    public function get(string $id, array $query = []): array
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $in = $this->schema(["includeDeleted:b?"]);
        $out = $this->schema($this->postTypeModel->outputSchema(), "out");

        $query = $in->validate($query);

        $row = $this->getPostTypeByID($id, $query["includeDeleted"] ?? false);
        $row = $out->validate($row);

        return $row;
    }

    /**
     * Create a post type.
     *
     * @param array $body
     * @return array
     */
    public function post(array $body): array
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");

        $in = $this->schema($this->postTypeModel->postSchema());

        $body = $in->validate($body);
        $this->postTypeModel->insert($body);
        if (isset($body["categoryIDs"])) {
            $this->postTypeModel->putCategoriesForPostType($body["postTypeID"], $body["categoryIDs"]);
        }

        return $this->getPostTypeByID($body["postTypeID"], true);
    }

    /**
     * Update a post type.
     *
     * @param string $id
     * @param array $body
     * @return array
     */
    public function patch(string $id, array $body): array
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");
        $this->getPostTypeByID($id, true);

        $in = $this->schema($this->postTypeModel->patchSchema());
        $body = $in->validate($body, true);
        $this->postTypeModel->update($body, ["postTypeID" => $id]);
        if (isset($body["categoryIDs"])) {
            $this->postTypeModel->putCategoriesForPostType($id, $body["categoryIDs"]);
        }

        return $this->getPostTypeByID($id, true);
    }

    /**
     * Delete a post type.
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id): void
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");

        $postField = $this->getPostTypeByID($id);
        if ($postField["isOriginal"]) {
            throw new ClientException("Cannot delete original post type");
        }
        $this->postTypeModel->update(["isDeleted" => true], ["postTypeID" => $id]);
    }

    /**
     * Get post type by ID.
     *
     * @param string $id
     * @param bool $includeDeleted
     * @return array
     * @throws NotFoundException
     */
    private function getPostTypeByID(string $id, bool $includeDeleted = false): array
    {
        $where = ["postTypeID" => $id, "isDeleted" => 0];

        if ($includeDeleted) {
            $this->permission("settings.manage");
            unset($where["isDeleted"]);
        }

        $row = $this->postTypeModel->getWhere($where, [Model::OPT_LIMIT => 1])[0] ?? null;

        if (empty($row)) {
            throw new NotFoundException("postType", ["postTypeID" => $id]);
        }

        return $row;
    }
}
