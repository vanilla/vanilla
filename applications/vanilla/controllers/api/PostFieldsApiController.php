<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Exception\PermissionException;
use Vanilla\Forum\Models\PostFieldModel;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Models\Model;

class PostFieldsApiController extends \AbstractApiController
{
    public function __construct(private PostFieldModel $postFieldModel)
    {
    }

    /**
     * Get a list of post fields with optional filters applied.
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
            "label:s?",
            "dataType:s?" => ["enum" => PostFieldModel::DATA_TYPES],
            "formType:s?" => ["enum" => PostFieldModel::FORM_TYPES],
            "visibility:s?",
            "isRequired:b?",
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
        $out = $this->schema([":a" => $this->outputSchema()]);

        $query = $in->validate($query);
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $rows = $this->postFieldModel->getWhere($query, [
            Model::OPT_LIMIT => $limit,
            Model::OPT_OFFSET => $offset,
            Model::OPT_ORDER => "sort",
        ]);
        $rows = $out->validate($rows);

        $totalCount = $this->postFieldModel->getWhereCount($query);

        $paging = ApiUtils::numberedPagerInfo($totalCount, "/api/v2/post-fields", $query, $in);

        return new Data($rows, ["paging" => $paging]);
    }

    /**
     * Get a post field.
     *
     * @param int $id
     * @return Data
     */
    public function get(int $id): Data
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");
        $out = $this->schema($this->outputSchema(), "out");
        $row = $this->getPostFieldByID($id);
        $row = $out->validate($row);

        return new Data($row);
    }

    /**
     * Create a post field.
     *
     * @param array $body
     * @return Data
     */
    public function post(array $body): Data
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");

        $in = $this->postSchema();
        $in->addValidator("", $this->postFieldModel->createUniqueApiNameValidator());
        $body = $in->validate($body);
        $id = $this->postFieldModel->insert($body);

        return $this->get($id);
    }

    /**
     * Update a post field.
     *
     * @param int $id
     * @param array $body
     * @return Data
     */
    public function patch(int $id, array $body): Data
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");
        $this->getPostFieldByID($id);

        $in = $this->patchSchema();
        $body = $in->validate($body);
        $this->postFieldModel->update($body, ["postFieldID" => $id]);

        return $this->get($id);
    }

    /**
     * Delete a post field.
     *
     * @param int $id
     * @return Data
     */
    public function delete(int $id): Data
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");

        $this->getPostFieldByID($id);
        $this->postFieldModel->delete(["postFieldID" => $id]);

        return new Data([], 204);
    }

    /**
     * Update sort values for records using a apiName => sort mapping.
     *
     * @param array $body
     * @return void
     * @throws HttpException|PermissionException|ValidationException|\Exception
     */
    public function put_sorts(array $body)
    {
        PostTypeModel::ensurePostTypesFeatureEnabled();
        $this->permission("settings.manage");

        $in = $this->schema(Schema::parse([":o" => "Key-value mapping of postFieldID => sort"]));
        $body = $in->validate($body);
        $this->postFieldModel->updateSorts($body);
    }

    /**
     * Get post field by ID.
     *
     * @param int $id
     * @return array
     * @throws NotFoundException
     */
    private function getPostFieldByID(int $id): array
    {
        $row = $this->postFieldModel->getPostField($id);

        if (empty($row)) {
            throw new NotFoundException("PostField");
        }
        return $row;
    }

    /**
     * Returns the schema for displaying post fields.
     *
     * @return Schema
     */
    private function outputSchema(): Schema
    {
        return Schema::parse([
            "postFieldID",
            "apiName",
            "postTypeID",
            "label",
            "description",
            "dataType",
            "formType",
            "visibility",
            "displayOptions",
            "dropdownOptions",
            "isRequired",
            "isActive",
            "sort",
            "dateInserted",
            "dateUpdated",
            "insertUserID",
            "updateUserID",
        ]);
    }

    /**
     * Returns the schema for creating post fields.
     *
     * @return Schema
     */
    private function postSchema(): Schema
    {
        $schema = $this->schema([
            "apiName:s",
            "postTypeID:i",
            "dataType:s" => ["enum" => PostFieldModel::DATA_TYPES],
        ])->merge($this->patchSchema());
        return $schema;
    }

    /**
     * Returns the schema for updating post fields.
     *
     * @return Schema
     */
    private function patchSchema(): Schema
    {
        $schema = $this->schema([
            "label:s",
            "description:s",
            "formType" => ["enum" => PostFieldModel::FORM_TYPES],
            "visibility" => ["enum" => PostFieldModel::VISIBILITIES],
            "dropdownOptions:a|n?",
            "isRequired:b",
            "isActive:b",
        ]);
        return $schema;
    }
}
