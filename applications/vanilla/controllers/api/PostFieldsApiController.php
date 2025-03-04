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
        $this->permission();
        $in = $this->schema([
            "postTypeID:s?" => ["x-filter" => true],
            "dataType:s?" => ["enum" => PostFieldModel::DATA_TYPES, "x-filter" => true],
            "formType:s?" => ["enum" => PostFieldModel::FORM_TYPES, "x-filter" => true],
            "visibility:s?" => ["x-filter" => true],
            "isRequired:b?" => ["x-filter" => true],
            "isActive:b?" => ["x-filter" => true],
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
        $out = $this->schema([":a" => $this->postFieldModel->outputSchema()], "out");

        $query = $in->validate($query);

        $filters = ApiUtils::queryToFilters($in, $query);

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $rows = $this->postFieldModel->getWhere($filters, [
            Model::OPT_LIMIT => $limit,
            Model::OPT_OFFSET => $offset,
        ]);

        $rows = array_map(function ($row) {
            $row["isRequired"] = (bool) $row["isRequired"];
            $row["isActive"] = (bool) $row["isActive"];
            return $row;
        }, $rows);

        $rows = $out->validate($rows);

        $totalCount = $this->postFieldModel->getWhereCount($filters);

        $paging = ApiUtils::numberedPagerInfo($totalCount, "/api/v2/post-fields", $query, $in);

        return new Data($rows, ["paging" => $paging]);
    }

    /**
     * Get a post field.
     *
     * @param string $postFieldID
     * @return array
     */
    public function get(string $postFieldID): array
    {
        $this->permission();

        $out = $this->schema($this->postFieldModel->outputSchema(), "out");
        $row = $this->getPostFieldByID($postFieldID);
        $row = $out->validate($row);

        return $row;
    }

    /**
     * Create a post field.
     *
     * @param array $body
     * @return array
     */
    public function post(array $body): array
    {
        $this->permission("settings.manage");

        $in = $this->schema($this->postFieldModel->postSchema());
        $body = $in->validate($body);
        $this->postFieldModel->insert($body);

        return $this->getPostFieldByID($body["postFieldID"]);
    }

    /**
     * Update a post field.
     *
     * @param string $postFieldID
     * @param array $body
     * @return array
     */
    public function patch(string $postFieldID, array $body): array
    {
        $this->permission("settings.manage");

        $existingPostField = $this->getPostFieldByID($postFieldID);

        $in = $this->schema($this->postFieldModel->patchSchema($existingPostField));
        $body = $in->validate($body, true);
        $this->postFieldModel->update($body, ["postFieldID" => $postFieldID]);

        return $this->getPostFieldByID($postFieldID);
    }

    /**
     * Delete a post field.
     *
     * @param string $postFieldID
     * @return void
     */
    public function delete(string $postFieldID): void
    {
        $this->permission("settings.manage");

        $this->getPostFieldByID($postFieldID);
        $this->postFieldModel->delete(["postFieldID" => $postFieldID]);
    }

    /**
     * Update sort values for post fields for the given postTypeID in the path and postFieldID => sort mapping in the body.
     *
     * @param array $body
     * @return void
     * @throws HttpException|PermissionException|ValidationException|\Exception
     */
    public function put_sorts(string $postTypeID, array $body): void
    {
        $this->permission("settings.manage");

        $in = $this->schema(Schema::parse([":o" => "Key-value mapping of postFieldID => sort"]));
        $body = $in->validate($body);
        $this->postFieldModel->updateSorts($postTypeID, $body);
    }

    /**
     * Get post field by ID.
     *
     * @param string $postFieldID
     * @return array
     * @throws NotFoundException
     */
    private function getPostFieldByID(string $postFieldID): array
    {
        $row = $this->postFieldModel->getWhere(["pf.postFieldID" => $postFieldID], [Model::OPT_LIMIT => 1])[0] ?? null;

        if (empty($row)) {
            throw new NotFoundException("postField", ["postField" => $postFieldID]);
        }

        return $row;
    }
}
