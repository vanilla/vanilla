<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\API;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\ApiUtils;
use Vanilla\Layouts\LayoutModel;
use Vanilla\Models\Model;

/**
 * API v2 endpoints for layouts and layout views
 */
class LayoutsApiController extends \AbstractApiController {

    //region Properties
    private $layoutsModel;
    //endregion

    //region Constructor
    /**
     * DI Constructor
     *
     * @param LayoutModel $layoutsModel
     */
    public function __construct(LayoutModel $layoutsModel) {
        $this->layoutsModel = $layoutsModel;
    }
    //endregion

    //region Layout API endpoints
    /**
     * Get a set of layouts
     *
     * @param array $query Set of query string parameters that specify criteria for set of layouts to return.
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     */
    public function index(array $query = []): Data {
        $this->permission();

        $in = $this->schema([
            'page:i?' => [
                'default' => 1,
                'minimum' => 1,
                'maximum' => 100
            ],
            'limit:i?' => [
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100
            ],
            "sort:s?" => [
                'enum' => ApiUtils::sortEnum('layoutID', 'name', 'dateInserted'),
            ]
        ], "in");

        $query = $in->validate($query);

        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);
        $where = ApiUtils::queryToFilters($in, $query);

        $sort = $query['sort'] ?? 'layoutID';

        $out = $this->schema([], "out");

        $options = [
            Model::OPT_LIMIT => $limit,
            Model::OPT_OFFSET => $offset,
            Model::OPT_ORDER => $sort,
        ];
        $rows = $this->layoutsModel->select($where, $options);

        $result = $out->validate($rows);
        $paging = ApiUtils::morePagerInfo($result, "/api/v2/layouts", $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Get an individual layout by ID
     *
     * @param int $layoutID ID of the layout to retrieve
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\Database\NoResultsException Layout not found.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     */
    public function get(int $layoutID): Data {
        $this->permission();

        $in = $this->schema([], "in");
        $out = $this->schema([], "out");

        $row = $this->layoutsModel->selectSingle(['layoutID' => $layoutID]);

        $result = $out->validate($row);
        return new Data($result);
    }

    /**
     * Get the editable fields for a layout
     *
     * @param int $layoutID ID of layout to present for editing
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\Database\NoResultsException Layout not found.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     */
    public function get_edit(int $layoutID): Data {
        $this->permission("Garden.Settings.Manage");

        $out = $this->schema(["layoutID:i", "name:s", "layout:s"], "out");

        $row = $this->layoutsModel->selectSingle(['layoutID' => $layoutID]);

        $result = $out->validate($row);
        return new Data($result);
    }

    /**
     * Delete a layout by ID
     *
     * @param int $layoutID ID of the layout to delete
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to perform action on this resource.
     * @throws \Exception Error during deletion.
     */
    public function delete(int $layoutID): void {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema([], "in");

        //TODO: Delete all layout views

        $this->layoutsModel->delete(['layoutID' => $layoutID]);
    }

    /**
     * Create a new layout
     *
     * @param array $body
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\Database\NoResultsException Retrieval of inserted layout fails.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to perform action on this resource.
     */
    public function post(array $body = []): Data {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(Schema::parse([
            "name:s",
            "layout:s",
            "layoutType:s"
        ]));
        $out = $this->schema([]);

        $body = $in->validate($body);
        $layoutID = intval($this->layoutsModel->insert($body));
        $row = $this->layoutsModel->selectSingle(["layoutID" => $layoutID]);

        $result = $out->validate($row);

        return new Data($result, 201);
    }

    /**
     * Update an existing layout.
     *
     * @param int $layoutID ID of layout to update
     * @param array $body Fields to update within the layout
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\Database\NoResultsException Layout ID specified not found.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to perform action on this resource.
     * @throws \Exception Error on update.
     */
    public function patch(int $layoutID, array $body = []): Data {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(Schema::parse([
            "name:s?",
            "layout:s?"
        ]));
        $out = $this->schema([]);

        $body = $in->validate($body);
        $row = $this->layoutsModel->selectSingle(["layoutID" => $layoutID]);
        if (!empty($body)) {
            $where = ['layoutID' => $layoutID];
            $this->layoutsModel->update($body, $where, [Model::OPT_LIMIT => 1]);
            $row = $this->layoutsModel->selectSingle($where);
        }
        $result = $out->validate($row);

        return new Data($result);
    }

    //endregion

    //region Layout Views API endpoints

    /**
     * Get the set of layout views for the given layout
     *
     * @param int $layoutID ID of layout for which to retrieve its set of views
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     */
    public function get_views(int $layoutID): Data {
        $this->permission();

        $out = $this->schema([]);
        $result = $out->validate([]);
        return new Data($result);
    }

    //endregion

    //region Non-Public Methods
    //endregion
}
