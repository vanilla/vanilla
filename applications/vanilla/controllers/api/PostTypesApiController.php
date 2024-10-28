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
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Models\Model;

class PostTypesApiController extends \AbstractApiController
{
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
        $this->permission("settings.manage");
        $in = $this->schema([
            "apiName:s?",
            "name:s?",
            "baseType:s?" => ["enum" => $this->postTypeModel->getBaseTypes()],
            "isOriginal:b?",
            "isActive:b?",
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
        $out = $this->schema([":a" => $this->postTypeModel->outputSchema()]);

        $query = $in->validate($query);
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $rows = $this->postTypeModel->getWhere($query, [
            Model::OPT_LIMIT => $limit,
            Model::OPT_OFFSET => $offset,
        ]);
        $rows = $out->validate($rows);

        $totalCount = $this->postTypeModel->getWhereCount($query);

        $paging = ApiUtils::numberedPagerInfo($totalCount, "/api/v2/post-types", $query, $in);

        return new Data($rows, ["paging" => $paging]);
    }

    /**
     * Get a post type.
     *
     * @param int $id
     * @return Data
     */
    public function get(int $id): Data
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");
        $out = $this->schema($this->postTypeModel->outputSchema(), "out");
        $row = $this->getPostTypeByID($id);
        $row = $out->validate($row);

        return new Data($row);
    }

    /**
     * Create a post type.
     *
     * @param array $body
     * @return Data
     */
    public function post(array $body): Data
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");

        $in = $this->schema($this->postTypeModel->postSchema());
        $in->addValidator("apiName", $this->postTypeModel->createUniqueApiNameValidator());
        $body = $in->validate($body);
        $id = $this->postTypeModel->insert($body);

        return $this->get($id);
    }

    /**
     * Update a post type.
     *
     * @param int $id
     * @param array $body
     * @return Data
     */
    public function patch(int $id, array $body): Data
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");
        $this->getPostTypeByID($id);

        $in = $this->schema($this->postTypeModel->patchSchema());
        $body = $in->validate($body);
        $this->postTypeModel->update($body, ["postTypeID" => $id]);

        return $this->get($id);
    }

    /**
     * Delete a post type.
     *
     * @param int $id
     * @return Data
     */
    public function delete(int $id): Data
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");

        $postField = $this->getPostTypeByID($id);
        if ($postField["isOriginal"]) {
            throw new ClientException("Cannot delete original post type");
        }
        $this->postTypeModel->update(["isDeleted" => true], ["postTypeID" => $id]);

        return new Data([], 204);
    }

    /**
     * Get post type by ID.
     *
     * @param int $id
     * @return array
     * @throws NotFoundException
     */
    private function getPostTypeByID(int $id): array
    {
        $row = $this->postTypeModel->getPostType($id);

        if (empty($row)) {
            throw new NotFoundException("PostType");
        }
        return $row;
    }
}
