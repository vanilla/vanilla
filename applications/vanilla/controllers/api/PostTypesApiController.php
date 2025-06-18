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
            "postTypeID:s?",
            "parentPostTypeID:s?",
            "isOriginal:b?",
            "isActive:b?",
            "includeDeleted:b?",
        ]);
        $out = $this->schema([":a" => $this->postTypeModel->outputSchema()], "out");

        $query = $in->validate($query);

        $query["isDeleted"] = 0;

        if ($query["includeDeleted"] ?? false) {
            unset($query["isDeleted"]);
        }
        unset($query["includeDeleted"]);

        $rows = $this->postTypeModel->getWhere($query);
        $rows = $out->validate($rows);
        return $rows;
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

        if (str_contains($id, "edit")) {
            $id = str_replace("edit", "", $id);
        }

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

        $this->postTypeModel->updateByID($id, $body);

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
