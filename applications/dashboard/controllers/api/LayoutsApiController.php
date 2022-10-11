<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\API;

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Psr\SimpleCache\CacheInterface;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\LayoutService;
use Vanilla\Layout\Providers\MutableLayoutProviderInterface;
use Vanilla\Layout\Resolvers\ReactResolver;
use Vanilla\Layout\Section\AbstractLayoutSection;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * API v2 endpoints for layouts and layout views
 */
class LayoutsApiController extends \AbstractApiController
{
    //region Properties
    /** @var string Cache key for layout assets. */
    private const LAYOUT_ASSET_CACHE_KEY = "layoutAsset/%s";

    /** @var int Cache time for user notification count. */
    private const LAYOUT_ASSET_TTL = 120; // 120 seconds.

    /** @var LayoutHydrator */
    private $layoutHydrator;

    /** @var LayoutModel $layoutModel */
    private $layoutModel;

    /** @var LayoutViewModel $layoutViewModel */
    private $layoutViewModel;

    /** @var LayoutService $layoutProviderService */
    private $layoutProviderService;

    /** @var ConfigurationInterface */
    private $config;

    //endregion

    /**
     * DI.
     *
     * @param LayoutHydrator $layoutHydrator
     * @param LayoutModel $layoutModel
     * @param LayoutViewModel $layoutViewModel
     * @param LayoutService $layoutProviderService
     * @param ConfigurationInterface $config
     */
    public function __construct(
        LayoutHydrator $layoutHydrator,
        LayoutModel $layoutModel,
        LayoutViewModel $layoutViewModel,
        LayoutService $layoutProviderService,
        ConfigurationInterface $config
    ) {
        $this->layoutHydrator = $layoutHydrator;
        $this->layoutModel = $layoutModel;
        $this->layoutViewModel = $layoutViewModel;
        $this->layoutProviderService = $layoutProviderService;
        $this->config = $config;
    }

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
    public function get_schema(array $query = []): Data
    {
        $this->permission();
        $in = Schema::parse([
            "layoutViewType:s?" => [
                "enum" => $this->layoutHydrator->getLayoutViewTypes(),
            ],
        ]);

        $query = $in->validate($query);

        $response = new Data($this->layoutHydrator->getSchema($query["layoutViewType"] ?? null));
        return $response;
    }

    /**
     * /api/v2/layouts/catalog
     *
     * Get a list of all layouts.
     *
     * @param array $query Query parameters for the request.
     *
     * @return Data
     */
    public function get_catalog(array $query = [])
    {
        $this->permission();
        $in = Schema::parse([
            "layoutViewType:s?" => [
                "enum" => $this->layoutHydrator->getLayoutViewTypes(),
            ],
        ]);
        $out = Schema::parse([
            "layoutViewType:s?" => [
                "default" => null,
            ],
            "layoutParams:o",
            "middlewares:o",
            "widgets:o",
            "assets:o",
            "sections:o",
        ]);

        $query = $in->validate($query);

        $layoutView = $this->layoutHydrator->getLayoutViewType($query["layoutViewType"]);
        $hydrator = $this->layoutHydrator->getHydrator($query["layoutViewType"] ?? null);
        $paramSchema = $this->layoutHydrator->getViewParamSchema($layoutView, true);
        $flattenedParamSchema = SchemaUtils::flattenSchema($paramSchema, "/");

        $widgetSchemas = [];
        $assetSchemas = [];
        $sectionSchemas = [];
        foreach ($hydrator->getResolvers() as $resolver) {
            if (!$resolver instanceof ReactResolver) {
                continue;
            }

            if (is_a($resolver->getReactWidgetClass(), AbstractLayoutAsset::class, true)) {
                // Assets are handled separately below.
                continue;
            }

            /** @var ReactWidgetInterface $widgetClass */
            $widgetClass = $resolver->getReactWidgetClass();
            $componentName = $widgetClass::getComponentName();
            $widgetIconUrl = asset($widgetClass::getWidgetIconPath(), true);
            $widgetName = $widgetClass::getWidgetName();

            if (is_a($widgetClass, AbstractLayoutSection::class, true)) {
                $sectionSchemas[$resolver->getType()] = [
                    '$reactComponent' => $componentName,
                    "schema" => $resolver->getSchema(),
                    "allowedWidgetIDs" => $this->getAllowedWidgetIDs($widgetClass::getWidgetID()),
                    "iconUrl" => $widgetIconUrl,
                    "name" => $widgetName,
                ];
            } else {
                $widgetSchemas[$resolver->getType()] = [
                    '$reactComponent' => $componentName,
                    "schema" => $resolver->getSchema(),
                    "iconUrl" => $widgetIconUrl,
                    "name" => $widgetName,
                ];
            }
        }

        // Go through the assets.

        if ($layoutView !== null) {
            /**
             * @var class-string<AbstractLayoutAsset> $assetClass
             */
            foreach ($layoutView->getAssetClasses() as $assetClass) {
                $resolver = new ReactResolver($assetClass, \Gdn::getContainer());
                $iconUrl = empty($assetClass::getWidgetIconPath())
                    ? $assetClass::getWidgetIconPath()
                    : asset($assetClass::getWidgetIconPath(), true);
                $assetSchemas[$resolver->getType()] = [
                    '$reactComponent' => $assetClass::getComponentName(),
                    "schema" => $resolver->getSchema(),
                    "iconUrl" => $iconUrl,
                    "name" => $assetClass::getWidgetName(),
                ];
            }
        }

        $result = [
            "layoutViewType" => $query["layoutViewType"],
            "layoutParams" => [],
            "widgets" => $widgetSchemas,
            "assets" => $assetSchemas,
            "sections" => $sectionSchemas,
        ];

        foreach ($flattenedParamSchema->getSchemaArray()["properties"] as $propName => $propSchema) {
            $result["layoutParams"][$propName]["schema"] = $propSchema;
        }

        foreach ($hydrator->getMiddlewares() as $middleware) {
            $result["middlewares"][$middleware->getType()]["schema"] = $middleware->getSchema();
        }

        $result = $out->validate($result);
        return new Data($result);
    }

    /**
     * Get a list allowed widgets for a section.
     *
     * @param string $sectionID Section ID.
     *
     * @return array
     */
    private function getAllowedWidgetIDs(string $sectionID): array
    {
        $allowed = [];
        foreach ($this->layoutHydrator->getResolvers() as $resolver) {
            if ($resolver instanceof ReactResolver && in_array($sectionID, $resolver->getAllowedSectionIDs())) {
                $allowed[] = $resolver->getType();
            }
        }
        return $allowed;
    }

    /**
     * GET /api/v2/layouts
     *
     * Get a set of layouts
     *
     * @param array $query The query string.
     *
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     * @throws \Exception Error during index processing.
     */
    public function index(array $query): Data
    {
        $this->permission("site.manage");
        $in = $this->schema($this->layoutModel->getQueryInputSchema(false));

        $query = $in->validate($query);

        $out = $this->schema($this->layoutModel->getMetadataSchema(), "out");

        $layouts = [];
        foreach ($this->layoutProviderService->getProviders() as $layoutProvider) {
            $layouts = array_merge($layouts, $layoutProvider->getAll());
        }
        $layouts = $this->layoutModel->normalizeRows($layouts, $query["expand"]);

        SchemaUtils::validateArray($layouts, $out);

        $layouts = $this->sortLayouts($layouts);

        return new Data($layouts);
    }

    /**
     * GET /api/v2/layouts/:layoutID
     *
     * Get an individual layout by ID
     *
     * @param int|string $layoutID ID of the layout to retrieve
     * @param array $query The query string.
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Garden\Web\Exception\NotFoundException Layout not found.
     * @throws \Garden\Web\Exception\ClientException Invalid layout ID specified.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     */
    public function get($layoutID, array $query): Data
    {
        $query["layoutID"] = $layoutID;
        $this->permission("site.manage");

        $query = $this->schema($this->layoutModel->getQueryInputSchema(true), "in")->validate($query);

        $out = $this->schema($this->layoutModel->getMetadataSchema(), "out");

        $provider = $this->layoutProviderService->getCompatibleProvider($layoutID);
        if (!isset($provider)) {
            throw new ClientException("Invalid layout ID format", 400, ["layoutID" => $layoutID]);
        }
        try {
            $row = $provider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Layout");
        }
        $row = $this->layoutModel->normalizeRow($row, $query["expand"]);

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
    public function get_edit($layoutID): Data
    {
        $this->permission("site.manage");

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(["layoutID" => $layoutID]);

        $layoutProvider = $this->layoutProviderService->getCompatibleProvider($layoutID);
        if (!isset($layoutProvider)) {
            throw new ClientException("Invalid layout ID format", 400, ["layoutID" => $layoutID]);
        }
        try {
            $layout = $layoutProvider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Layout");
        }

        $out = $this->schema($this->layoutModel->getEditSchema(), "out");
        $result = $out->validate($layout);

        return new Data($result);
    }

    /**
     * POST /api/v2/layouts/hydrate
     *
     * Used to hydrate a spec passed dynamically.
     * As a result this endpoint requires site.manage permission.
     *
     * @param array $body The request body.
     *
     * @return Data API output.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     */
    public function post_hydrate(array $body): Data
    {
        $this->permission("site.manage");
        $in = $this->schema([
            "layoutViewType:s" => [
                "enum" => $this->layoutHydrator->getLayoutViewTypes(),
            ],
            "layout:a",
            "params:o",
        ]);
        $body = $in->validate($body);
        $rowData = [
            "layoutViewType" => $body["layoutViewType"],
            "layout" => $body["layout"],
        ];
        $result = $this->layoutHydrator->hydrateLayout($body["layoutViewType"], $body["params"], $rowData);

        return new Data($result, ["status" => 200]);
    }

    /**
     * GET /api/v2/layouts/lookup-hydrate
     *
     * Lookup a layout to Hydrate.
     *
     * @param array $query Parameters used to hydrate the child elements of the layout
     *
     * @return Data
     * @throws \Garden\Web\Exception\NotFoundException Layout not found.
     * @throws \Garden\Web\Exception\ClientException No layout provider found for ID format/value.
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     */
    public function get_lookupHydrate(array $query = []): Data
    {
        $this->permission();

        $in = $this->schema(["layoutViewType:s", "recordID:s", "recordType:s", "params:o?"], "in");
        $query = $in->validate($query);

        $layoutID = $this->layoutViewModel->getLayoutIdLookup(
            $query["layoutViewType"],
            $query["recordType"],
            $query["recordID"]
        );

        return $this->get_hydrate($layoutID, $query);
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
    public function get_hydrate($layoutID, array $query = []): Data
    {
        $this->permission();

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(["layoutID" => $layoutID]);

        $in = $this->schema(["params:o?"], "in");
        $out = $this->layoutModel->getHydratedSchema();
        $query = $in->validate($query);
        $params = $query["params"] ?? [];

        // Grab the record from the database if it exists.
        $provider = $this->layoutProviderService->getCompatibleProvider($layoutID);
        if (!isset($provider)) {
            throw new ClientException("Invalid layout ID format", 400, ["layoutID" => $layoutID]);
        }
        try {
            $row = $provider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Layout");
        }
        $layoutViewType = $row["layoutViewType"];

        $hydrated = $this->layoutHydrator->hydrateLayout($layoutViewType, $params, $row);

        $result = $this->layoutModel->normalizeRow($hydrated);
        $result = $out->validate($result);

        return new Data($result);
    }

    /**
     * GET /api/v2/layouts/lookup-hydrate-assets
     *
     * Lookup a layout to Hydrate.
     *
     * @param array $query Parameters used to hydrate the child elements of the layout
     *
     * @return Data
     * @throws \Garden\Web\Exception\NotFoundException Layout not found.
     * @throws \Garden\Web\Exception\ClientException No layout provider found for ID format/value.
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     */
    public function get_lookupHydrateAssets(array $query = []): Data
    {
        $this->permission();

        $in = $this->schema(["layoutViewType:s", "recordID:i", "recordType:s", "params:o?"], "in");
        $parsedQuery = $in->validate($query);
        $layoutID = $this->layoutViewModel->getLayoutIdLookup(
            $parsedQuery["layoutViewType"],
            $parsedQuery["recordType"],
            $parsedQuery["recordID"]
        );

        return $this->get_hydrateAssets($layoutID, $query);
    }

    /**
     * GET /api/v2/layouts/:layoutID/hydrate-assets
     *
     * Get static assets for the layout, without hydrating data.
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
    public function get_hydrateAssets($layoutID, array $query = []): Data
    {
        $this->permission();

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(["layoutID" => $layoutID]);

        $in = $this->schema(["params:o?"], "in");
        $out = Schema::parse(["js:a", "css:a"]);
        $query = $in->validate($query);
        $params = $query["params"] ?? [];

        // Grab the record from the database if it exists.
        $provider = $this->layoutProviderService->getCompatibleProvider($layoutID);
        if (!isset($provider)) {
            throw new ClientException("Invalid layout ID format", 400, ["layoutID" => $layoutID]);
        }
        try {
            $row = $provider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Layout");
        }
        $layoutViewType = $row["layoutViewType"];

        $assets = $this->layoutHydrator->getAssetLayout($layoutViewType, $params, $row);

        $result = $out->validate($assets);
        $response = new Data($result);
        $response->setHeader("Cache-Control", self::PUBLIC_CACHE);
        return $response;
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
    public function delete($layoutID): void
    {
        $this->permission("site.manage");

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(["layoutID" => $layoutID]);

        $layoutProvider = $this->tryGetMutableLayoutProvider($layoutID);

        try {
            $layoutProvider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Layout");
        }

        // If the layout has associated views, we prevent deletion & throw an exception.
        $associatedViews = $this->get_views($layoutID, [])->getData();
        if (count($associatedViews) > 0) {
            throw new ClientException("Active layout cannot be deleted", 422);
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
    public function post(array $body = []): Data
    {
        $this->permission("site.manage");

        $in = $this->schema($this->layoutModel->getCreateSchema($this->layoutHydrator->getLayoutViewTypes()), "in");
        $out = $this->schema($this->layoutModel->getFullSchema(), "out");

        $body = $in->validate($body);

        $layoutID = intval($this->layoutModel->insert($body));
        $row = $this->layoutModel->selectSingle(["layoutID" => $layoutID]);
        $row = $this->layoutModel->normalizeRow($row);

        $result = $out->validate($row);

        return new Data($result, ["status" => 201]);
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
    public function patch($layoutID, array $body = []): Data
    {
        $this->permission("site.manage");

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(["layoutID" => $layoutID], true);

        $layoutProvider = $this->tryGetMutableLayoutProvider($layoutID);

        try {
            $layout = $layoutProvider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Layout");
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
     * GET /api/v2/layouts/:layoutID/views
     *
     * Get the set of layout views for the given layout
     *
     * @param int|string $layoutID ID of layout for which to retrieve its set of views
     * @param array $query The query string.
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\Database\NoResultsException Layout not found.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to access resource.
     */
    public function get_views($layoutID, array $query): Data
    {
        $this->permission("site.manage");
        $query["layoutID"] = $layoutID;

        $query = $this->schema($this->layoutViewModel->getInputSchema(), "in")->validate($query);

        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(["layoutID" => $layoutID]);
        $out = $this->schema(LayoutViewModel::getSchema());
        $rows = $this->layoutViewModel->getViewsByLayoutID($layoutID);

        $rows = $this->layoutViewModel->normalizeRows($rows, $query["expand"]);
        SchemaUtils::validateArray($rows, $out);

        return new Data($rows);
    }

    /**
     * Save a value of a legacy view and write disable custom layout rendering for that view.
     *
     * @param string $layoutViewType
     * @param array $body
     *
     * @return Data
     */
    public function put_viewsLegacy(array $body): Data
    {
        $this->permission("site.manage");
        $in = $this->schema([
            "layoutViewType:s" => [
                "enum" => $this->layoutProviderService->getLayoutViewTypes(),
            ],
            "legacyViewValue:s?",
        ])->addValidator("", function (array $value, ValidationField $field) {
            $layoutViewType = $value["layoutViewType"];
            switch ($layoutViewType) {
                case "discussionList":
                    $validValues = ["modern", "table", "foundation"];
                    break;
                case "categoryList":
                    $validValues = ["modern", "table", "mixed", "foundation"];
                    break;
                case "home":
                default:
                    return;
            }

            $legacyViewValue = $value["legacyViewValue"];
            if (!in_array($legacyViewValue, $validValues)) {
                $field->addError(
                    "Invalid legacyViewValue for layoutViewType $layoutViewType. Expected one of: " .
                        implode(", ", $validValues)
                );
            }
        });
        $body = $in->validate($body);
        $layoutViewType = $body["layoutViewType"];
        $legacyViewValue = $body["legacyViewValue"] ?? null;

        // Delete the active layouts.
        $this->layoutViewModel->delete([
            "layoutViewType" => $layoutViewType,
        ]);

        // Now switch the configs.
        switch ($layoutViewType) {
            case "home":
                $this->config->saveToConfig([
                    "Routes.DefaultController" => [trim($legacyViewValue, ", "), "Internal"],
                    "Feature.customLayout.home.Enabled" => false,
                ]);
                break;
            case "discussionList":
                $this->config->saveToConfig([
                    "Vanilla.Discussions.Layout" => $legacyViewValue,
                    "Feature.customLayout.discussionList.Enabled" => false,
                ]);
                break;
            case "categoryList":
                $this->config->saveToConfig([
                    "Vanilla.Categories.Layout" => $legacyViewValue,
                    "Feature.customLayout.categoryList.Enabled" => false,
                ]);
                break;
            default:
                $this->config->saveToConfig([
                    "Feature.customLayout.{$layoutViewType}.Enabled" => false,
                ]);
                break;
        }

        return new Data();
    }

    /**
     * PUT /api/v2/layouts/:layoutID/views
     *
     * Put the set of layout views for the given layout
     *
     * @param int|string $layoutID ID of layout for which to insert layout views
     * @param array $body array of layout views to insert
     * @return Data
     * @throws \Garden\Schema\ValidationException Data does not validate against its schema.
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\Database\NoResultsException Retrieval of inserted layout view fails.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to perform action on this resource.
     * @throws \Garden\Web\Exception\ClientException User attempted to insert a duplicate layout view.
     * @throws \Exception Error during record insertion.
     */
    public function put_views($layoutID, array $body = []): Data
    {
        $this->permission("site.manage");
        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(["layoutID" => $layoutID]);
        $in = $this->schema([]);
        $in->addValidator("", function (array $value, ValidationField $field) {
            if (!is_array($value)) {
                $value = [$value];
            }
            $uniqueArray = [];
            foreach ($value as $current) {
                if (!in_array($current, $uniqueArray)) {
                    $uniqueArray[] = $current;
                }
            }
            if (count($value) > count($uniqueArray)) {
                $field->addError("No Duplicate Keys allowed");
            }
            $in = $this->schema(["recordID:i", "recordType:s"]);
            SchemaUtils::validateArray($value, $in);
        });
        $in->validate($body);
        //Get layoutViewType from Layout.
        $provider = $this->layoutProviderService->getCompatibleProvider($layoutID);
        if (!isset($provider)) {
            throw new ClientException("Invalid layout ID format", 400, ["layoutID" => $layoutID]);
        }
        try {
            $row = $provider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Layout");
        }

        $layoutViewType = $row["layoutViewType"];

        $layoutViewIDs = $this->layoutViewModel->saveLayoutViews($body, $layoutViewType, $layoutID);
        // Set this layoutViewType to use custom layouts.
        $this->config->saveToConfig("Feature.customLayout.{$layoutViewType}.Enabled", true);

        $layoutViews = $this->layoutViewModel->select(["layoutViewID" => $layoutViewIDs]);
        $result = $this->layoutViewModel->normalizeRows($layoutViews, ["record"]);
        $out = $this->schema(LayoutViewModel::getSchema());
        SchemaUtils::validateArray($result, $out);
        return new Data($result, 201);
    }

    /**
     * DELETE /api/v2/layouts/:layoutID/views
     *
     * Delete the set of layout views for the given layout
     *
     * @param int $layoutID ID of layout for which to delete select views
     * @param array $query Layout View IDs to delete
     * @throws \Garden\Web\Exception\HttpException Ban applied to permission for this session.
     * @throws \Vanilla\Exception\PermissionException User does not have permission to perform action on this resource.
     * @throws \Exception Error during deletion.
     */
    public function delete_views(int $layoutID, array $query): void
    {
        $this->permission("site.manage");
        $this->schema($this->layoutModel->getIDSchema(), "in")->validate(["layoutID" => $layoutID]);
        $query = $this->schema(["layoutViewIDs:a?" => [":i"]], "in")->validate($query);

        $where = [
            "layoutID" => $layoutID,
        ];
        if (isset($query["layoutViewIDs"])) {
            $where["layoutViewID"] = $query["layoutViewIDs"];
        }
        $this->layoutViewModel->delete($where);
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
    protected function tryGetMutableLayoutProvider($layoutID): MutableLayoutProviderInterface
    {
        $layoutProvider = $this->layoutProviderService->getCompatibleProvider($layoutID);
        if (!isset($layoutProvider)) {
            throw new ClientException("Invalid layout ID format", 400, ["layoutID" => $layoutID]);
        }
        if (!$layoutProvider instanceof MutableLayoutProviderInterface) {
            throw new ClientException("Layout is not mutable", 422);
        }
        return $layoutProvider;
    }

    /**
     * Sort an array of layouts so that layouts with string keys are always indexed first.
     *
     * @param array $layouts Layouts to sort
     * @return array Sorted layouts
     */
    private function sortLayouts(array $layouts): array
    {
        usort($layouts, function ($a, $b) {
            if (is_numeric($a["layoutID"]) && !is_numeric($b["layoutID"])) {
                return 1;
            } elseif (!is_numeric($a["layoutID"]) && is_numeric($b["layoutID"])) {
                return -1;
            } else {
                return $a["layoutID"] <=> $b["layoutID"];
            }
        });
        return $layouts;
    }

    //endregion
}
