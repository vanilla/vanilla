<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\API;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\LayoutService;
use Vanilla\Layout\Providers\MutableLayoutProviderInterface;

/**
 * API v2 endpoints for layouts and layout views
 */
class LayoutsApiController extends \AbstractApiController {

    //region Properties
    /** @var LayoutHydrator */
    private $layoutHydrator;

    /** @var LayoutModel $layoutModel */
    private $layoutModel;

    /** @var LayoutModel $layoutViewModel */
    private $layoutViewModel;

    /** @var LayoutService $layoutProviderService */
    private $layoutProviderService;

    //endregion

    //region Constructor

    /**
     * DI.
     *
     * @param LayoutModel $layoutModel
     * @param LayoutViewModel $layoutViewModel
     * @param LayoutHydrator $layoutHydrator
     * @param LayoutService $layoutProviderService
     */
    public function __construct(
        LayoutModel $layoutModel,
        LayoutViewModel $layoutViewModel,
        LayoutHydrator $layoutHydrator,
        LayoutService $layoutProviderService
    ) {
        $this->layoutModel = $layoutModel;
        $this->layoutViewModel = $layoutViewModel;
        $this->layoutProviderService = $layoutProviderService;
        $this->layoutHydrator = $layoutHydrator;
    }
    //endregion

    //region Layout API endpoints

    /**
     * GET /api/v2/layouts/schema
     *
     * Get the hydrate schema with the currently enabled addons.
     *
     * @param array $query Query parameters.
     *
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     */
    public function get_schema(array $query = []): Data {
        $this->permission();
        $in = Schema::parse([
            'layoutViewType:s?' => [
                'enum' => $this->layoutHydrator->getLayoutViewTypes(),
            ]
        ]);

        $query = $in->validate($query);

        $response = new Data($this->layoutHydrator->getSchema($query['layoutViewType'] ?? null));
        return $response;
    }

    /**
     * GET /api/v2/layouts
     *
     * Get a set of layouts
     *
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     * @throws \Exception Error during index processing.
     */
    public function index(): Data {
        $this->permission("settings.manage");

        $out = $this->schema($this->layoutModel->getMetadataSchema(), "out");

        $layouts = [];
        foreach ($this->layoutProviderService->getProviders() as $layoutProvider) {
            $layouts = array_merge($layouts, $layoutProvider->getAll());
        }

        $layouts = array_map(function (array $row) use ($out) {
            return $out->validate($this->layoutModel->normalizeRow($row));
        }, $layouts);

        $layouts = $this->sortLayouts($layouts);

        return new Data($layouts);
    }

    /**
     * GET /api/v2/layouts/:layoutID
     *
     * Get an individual layout by ID
     *
     * @param int|string $layoutID ID of the layout to retrieve
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Garden\Web\Exception\NotFoundException Layout not found.
     * @throws \Garden\Web\Exception\ClientException Invalid layout ID specified.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     */
    public function get($layoutID): Data {
        $this->permission("settings.manage");

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(['layoutID' => $layoutID]);

        $out = $this->schema($this->layoutModel->getMetadataSchema(), "out");

        $provider = $this->layoutProviderService->getCompatibleProvider($layoutID);
        if (!isset($provider)) {
            throw new ClientException('Invalid layout ID format', 400, ['layoutID' => $layoutID]);
        }
        try {
            $row = $provider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Layout');
        }
        $row = $this->layoutModel->normalizeRow($row);

        $result = $out->validate($row);
        return new Data($result);
    }

    /**
     * Get the editable fields for a layout
     *
     * @param int|string $layoutID ID of layout to present for editing
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Garden\Web\Exception\NotFoundException Layout not found.
     * @throws \Garden\Web\Exception\ClientException No layout provider found for ID format/value.
     * @throws \Garden\Web\Exception\ClientException Attempted to edit an immutable layout.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     */
    public function get_edit($layoutID): Data {
        $this->permission("settings.manage");

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(['layoutID' => $layoutID]);

        $layoutProvider = $this->tryGetMutableLayoutProvider($layoutID);

        try {
            $layout = $layoutProvider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Layout');
        }

        $out = $this->schema($this->layoutModel->getEditSchema(), "out");
        $result = $out->validate($layout);

        return new Data($result);
    }


    /**
     * POST /api/v2/layouts/hydrate
     *
     * Used to hydrate a spec passed dynamically.
     * As a result this endpoint requires settings.manage permission.
     *
     * @param array $body The request body.
     *
     * @return Data API output.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     */
    public function post_hydrate(array $body): Data {
        $this->permission('settings.manage');
        $in = $this->schema([
            'layoutViewType:s' => [
                'enum' => $this->layoutHydrator->getLayoutViewTypes(),
            ],
            'layout:a',
            'params:o',
        ]);
        $body = $in->validate($body);

        // Validate the params.
        $layoutViewType = $body['layoutViewType'];
        $params = $this->layoutHydrator->resolveParams($layoutViewType, $body['params']);

        $hydrator = $this->layoutHydrator->getHydrator($layoutViewType);

        $result = $hydrator->resolve([
            'layoutViewType' => $layoutViewType,
            'layout' => $body['layout'],
        ], $params);

        return new Data($result, ['status' => 200]);
    }

    /**
     * GET /api/v2/layouts/:layoutID/hydrate
     *
     * Hydrate a saved layout.
     *
     * @param int|string $layoutID ID of layout to hydrate
     * @param array $query Parameters used to hydrate the child elements of the layout
     *
     * @return Data
     * @throws \Garden\Web\Exception\NotFoundException Layout not found.
     * @throws \Garden\Web\Exception\ClientException No layout provider found for ID format/value.
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     */
    public function get_hydrate($layoutID, array $query = []): Data {
        $this->permission();

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(['layoutID' => $layoutID]);

        $in = $this->schema([
            'params:o?',
        ], 'in');
        $out = $this->layoutModel->getHydratedSchema();
        $query = $in->validate($query);
        $params = $query['params'] ?? [];

        // Grab the record from the database if it exists.
        $provider = $this->layoutProviderService->getCompatibleProvider($layoutID);
        if (!isset($provider)) {
            throw new ClientException('Invalid layout ID format', 400, ['layoutID' => $layoutID]);
        }
        try {
            $row = $provider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Layout');
        }
        $layoutViewType = $row['layoutViewType'];

        $layoutParams = $this->layoutHydrator->resolveParams($layoutViewType, $params);
        $hydrator = $this->layoutHydrator->getHydrator($layoutViewType);
        $hydrated = $hydrator->resolve($row, $layoutParams);

        $result = $this->layoutModel->normalizeRow($hydrated);
        $result = $out->validate($result);

        return new Data($result);
    }

    /**
     * DELETE /api/v2/layouts/:layoutID
     *
     * Delete a layout by ID
     *
     * @param int|string $layoutID ID of the layout to delete
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Garden\Web\Exception\NotFoundException Layout to delete not found.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to perform action on this resource.
     * @throws \Garden\Web\Exception\ClientException Layout provider not found for layout ID.
     * @throws \Garden\Web\Exception\ClientException Layout specified cannot be deleted, i.e. active or immutable.
     * @throws \Exception Error during deletion.
     */
    public function delete($layoutID): void {
        $this->permission("settings.manage");

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(['layoutID' => $layoutID]);

        $layoutProvider = $this->tryGetMutableLayoutProvider($layoutID);

        try {
            $layout = $layoutProvider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Layout');
        }

        $layout = $this->layoutModel->normalizeRow($layout);
        if (isset($layout['isActive']) && $layout['isActive']) {
            throw new ClientException('Active layout cannot be deleted');
        }
        $layoutProvider->deleteLayout($layoutID);
    }

    /**
     * POST /api/v2/layouts
     *
     * Create a new layout
     *
     * @param array $body
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\Database\NoResultsException Retrieval of inserted layout fails.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to perform action on this resource.
     * @throws \Exception Error during record insertion.
     */
    public function post(array $body = []): Data {
        $this->permission("settings.manage");

        $in = $this->schema($this->layoutModel->getCreateSchema($this->layoutHydrator->getLayoutViewTypes()), 'in');
        $out = $this->schema($this->layoutModel->getFullSchema(), 'out');

        $layoutID = intval($this->layoutModel->insert($body));
        $row = $this->layoutModel->selectSingle(["layoutID" => $layoutID]);
        $row = $this->layoutModel->normalizeRow($row);
        $result = $out->validate($row);

        return new Data($result, ['status' => 201]);
    }

    /**
     * PATCH /api/v2/layouts/:layoutID
     *
     * Update an existing layout.
     *
     * @param int|string $layoutID ID of layout to update
     * @param array $body Fields to update within the layout
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Garden\Web\Exception\NotFoundException Layout for specified ID not found.
     * @throws \Garden\Web\Exception\ClientException Layout provider not found for layout ID.
     * @throws \Garden\Web\Exception\ClientException Attempted to patch an immutable layout.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to perform action on this resource.
     * @throws \Exception Error on update.
     */
    public function patch($layoutID, array $body = []): Data {
        $this->permission("settings.manage");

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(['layoutID' => $layoutID], true);

        $layoutProvider = $this->tryGetMutableLayoutProvider($layoutID);

        try {
            $layout = $layoutProvider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException('Layout');
        }

        $body = $this->schema($this->layoutModel->getPatchSchema(), "in")->validate($body);
        if (!empty($body)) {
            $layout = $layoutProvider->updateLayout($layoutID, $body);
        }

        $out = $this->schema($this->layoutModel->getFullSchema(), "out");
        $result = $out->validate($this->layoutModel->normalizeRow($layout));

        return new Data($result);
    }

    //endregion

    //region Layout Views API endpoints

    /**
     * Get the set of layout views for the given layout
     *
     * @param int|string $layoutID ID of layout for which to retrieve its set of views
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\Database\NoResultsException Layout not found.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     */
    public function get_views($layoutID): Data {
        $this->permission("settings.manage");

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(['layoutID' => $layoutID]);
        $out = $this->schema(LayoutViewModel::getSchema());
        $rows = $this->layoutViewModel->getViewsByLayoutID($layoutID);

        $result = array_map(function (array $row) use ($out) {
            return $out->validate($this->layoutModel->normalizeRow($row));
        }, $rows);

        return new Data($result);
    }

    /**
     * Post the set of layout views for the given layout
     *
     * @param int|string $layoutID ID of layout for which to insert layout views
     * @param array $body layout view to insert
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\Database\NoResultsException Retrieval of inserted layout view fails.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to perform action on this resource.
     * @throws \Garden\Web\Exception\ClientException User attempted to insert a duplicate layout view.
     * @throws \Exception Error during record insertion.
     */
    public function post_views($layoutID, array $body = []): Data {
        $this->permission("settings.manage");

        $in = $this->schema(['recordID:i', 'recordType:s']);
        $out = $this->schema(LayoutViewModel::getSchema());
        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(['layoutID' => $layoutID]);
        $body = $in->validate($body);
        $body['layoutID'] = $layoutID;
        $existing = $this->layoutViewModel->select($body);
        if (!empty($existing)) {
            throw new ClientException("Cannot create a duplicate layout view", 422, $body);
        }
        $layoutViewID = $this->layoutViewModel->insert($body);
        $layout = $this->layoutModel->selectSingle(['layoutID' => $layoutID]);

        $layoutView = $this->layoutViewModel->selectSingle(['layoutViewID' => $layoutViewID]);
        $layoutView['layoutViewType'] = $layout['layoutViewType'];
        $result = $out->validate($layoutView);

        return new Data($result, 201);
    }

    /**
     * Delete the set of layout views for the given layout
     *
     * @param int $layoutID ID of layout for which to delete select views
     * @param array $query Layout View IDs to delete
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to perform action on this resource.
     * @throws \Exception Error during deletion.
     */
    public function delete_views(int $layoutID, array $query): void {
        $this->permission("settings.manage");
        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(['layoutID' => $layoutID]);
        $this->schema(["layoutViewIDs:a" => [":i"]], "in")->validate($query);
        $this->layoutViewModel->delete(['layoutID' => $layoutID, 'layoutViewID' => $query['layoutViewIDs']]);
    }
    //endregion

    //region Non-Public Methods
    /**
     * Try to get a layout provider that can mutate a layout with the provided ID.
     *
     * @param int|string $layoutID ID of the layout to mutate
     * @return MutableLayoutProviderInterface
     * @throws ClientException Invalid layout ID format.
     * @throws ClientException Layout corresponding to ID is not mutable.
     */
    protected function tryGetMutableLayoutProvider($layoutID): MutableLayoutProviderInterface {
        $layoutProvider = $this->layoutProviderService->getCompatibleProvider($layoutID);
        if (!isset($layoutProvider)) {
            throw new ClientException('Invalid layout ID format', 400, ['layoutID' => $layoutID]);
        }
        if (!$layoutProvider instanceof MutableLayoutProviderInterface) {
            throw new ClientException('Layout is not mutable', 422);
        }
        return $layoutProvider;
    }

    /**
     * Sort an array of layouts so that layouts with string keys are always indexed first.
     *
     * @param array $layouts Layouts to sort
     * @return array Sorted layouts
     */
    private function sortLayouts(array $layouts): array {
        usort($layouts, function ($a, $b) {
            if (is_numeric($a['layoutID']) && !is_numeric($b['layoutID'])) {
                return 1;
            } elseif (!is_numeric($a['layoutID']) && is_numeric($b['layoutID'])) {
                return -1;
            } else {
                return $a['layoutID'] <=> $b['layoutID'];
            }
        });
        return $layouts;
    }

    //endregion
}
