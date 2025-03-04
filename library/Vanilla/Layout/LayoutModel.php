<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Events\LayoutEvent;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Operation\ResourceEventProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Forum\Models\CommunityMachineTranslationModel;
use Vanilla\Layout\Providers\MutableLayoutProviderInterface;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Models\Model;
use Vanilla\Utility\ModelUtils;

/**
 * Model for managing persisted layout data
 */
class LayoutModel extends FullRecordCacheModel implements MutableLayoutProviderInterface
{
    private const TABLE_NAME = "layout";

    /**
     * DI Constructor
     */
    public function __construct(
        protected CurrentUserFieldProcessor $userFieldProcessor,
        protected CurrentDateFieldProcessor $dateFieldProcessor,
        protected JsonFieldProcessor $jsonFieldProcessor,
        protected LayoutViewModel $layoutViewModel,
        protected LayoutService $layoutProviderService,
        protected \GDN_Cache $cache,
        protected ResourceEventProcessor $resourceEventProcessor,
        protected EventManager $eventManager
    ) {
        parent::__construct(self::TABLE_NAME, $cache);

        $userFieldProcessor->camelCase();
        $this->addPipelineProcessor($userFieldProcessor);

        $dateFieldProcessor->camelCase();
        $this->addPipelineProcessor($dateFieldProcessor);

        $jsonFieldProcessor->setFields(["layout"]);
        $this->addPipelineProcessor($jsonFieldProcessor);

        $this->resourceEventProcessor->setResourceEventClass(LayoutEvent::class);
        $this->addPipelineProcessor($this->resourceEventProcessor);
    }

    //region Public Methods
    /**
     * Structure the table schema.
     *
     * @param \Gdn_Database $database Database handle
     * @param bool $explicit Optional, true to remove any columns that are not specified here,
     * false to retain those columns. Default false.
     * @param bool $drop Optional, true to drop table if it already exists,
     * false to retain table if it already exists. Default false.
     */
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $construct = $database->structure();
        $construct
            ->table(self::TABLE_NAME)
            ->primaryKey("layoutID")
            ->column("name", "varchar(100)", false)
            ->column("layoutViewType", "varchar(40)", false)
            ->column("layout", "mediumtext", false)
            ->column("insertUserID", "int", false)
            ->column("dateInserted", "datetime", false)
            ->column("updateUserID", "int", null)
            ->column("dateUpdated", "datetime", null)
            ->set($explicit, $drop);

        // Remove the layoutType column if it's there (it shouldn't be).
        $removeLayoutTypeColumn = $construct->table(self::TABLE_NAME)->columnExists("layoutType");
        if ($removeLayoutTypeColumn) {
            $construct->table(self::TABLE_NAME)->dropColumn("layoutType");
        }
    }

    /**
     * Normalize a layout record from the database into API output.
     *
     * @param array $row Layout record from database row
     * @param array|string|bool $expand Additional parameters used to expand output based on row property values
     * @return array Normalized row
     */
    public function normalizeRow(array $row, $expand = false): array
    {
        // File-based layouts are set as defaults, which have string IDs
        $row["isDefault"] = !is_numeric($row["layoutID"]);
        if (ModelUtils::isExpandOption("layoutViews", $expand)) {
            $allLayoutViewsForLayoutViewType = $this->layoutViewModel->normalizeRows(
                $this->layoutViewModel->select(["layoutViewType" => $row["layoutViewType"]]),
                $expand
            );

            $row["layoutViews"] = $this->layoutViewModel->normalizeRows(
                $this->layoutViewModel->getViewsByLayoutID($row["layoutID"]),
                $expand
            );

            $row = $this->synthesizeTemplateLayoutViews($row, $allLayoutViewsForLayoutViewType);
        }
        return $row;
    }

    /**
     * Normalize a layout records from the database into API output.
     *
     * @param array $rows Layout record from database rows
     * @param array|string|bool $expand Additional parameters used to expand output based on row property values
     * @return array Normalized row
     */
    public function normalizeRows(array $rows, $expand): array
    {
        $layoutViews = [];
        if (ModelUtils::isExpandOption("layoutViews", $expand)) {
            // Get all of the IDs from the result row
            $ids = array_map(function (array $row) {
                return $row["layoutID"];
            }, $rows);
            // expand layout Views based on layoutIDs .
            $layoutViews = $this->layoutViewModel->normalizeRows(
                $this->layoutViewModel->getViewsByLayoutIDs($ids),
                $expand
            );
        }

        $rows = array_map(function (array $row) use ($layoutViews, $expand) {
            $row = $this->normalizeRow($row);
            // If expand parameter present add layoutViews to the request.
            if (ModelUtils::isExpandOption("layoutViews", $expand)) {
                $appliedLayouts = array_values(
                    array_filter($layoutViews, function ($layoutView) use ($row) {
                        return $layoutView["layoutID"] == $row["layoutID"];
                    })
                );
                $row["layoutViews"] = $appliedLayouts;

                $allLayoutViews = array_filter($layoutViews, function (array $layoutView) use ($row) {
                    return $layoutView["layoutViewType"] === $row["layoutViewType"];
                });
                $row = $this->synthesizeTemplateLayoutViews($row, $allLayoutViews);
            }
            return $row;
        }, $rows);

        return $rows;
    }

    /**
     * A default layout with applied children may be the actual fallback layout.
     * If that is the case we will synthethize an applied layout for the record.
     * The actual lookup process reflects this so we want the UI to also reflect it.
     *
     * @param array $layout The layout to check.
     * @param array $layoutViewsForViewType All layout views for this layoutViewType
     *
     * @return array The modified layout.
     */
    private function synthesizeTemplateLayoutViews(array $layout, array $layoutViewsForViewType): array
    {
        $appliedLayouts = $layout["layoutViews"] ?? [];
        if (empty($appliedLayouts) && $layout["isDefault"]) {
            // A default layout with applied children may be the actual fallback layout.
            // If that is the case we will synthethize an applied layout for the record.
            // The actual lookup process reflects this so we want the UI to also reflect it.
            $hasAppliedDefault =
                count(
                    array_filter($layoutViewsForViewType, function (array $layoutView) {
                        return $layoutView["recordType"] === "global" || $layoutView["recordType"] === "root";
                    })
                ) > 0;

            if (!$hasAppliedDefault) {
                $appliedLayouts[] = [
                    "layoutViewID" => -1,
                    "layoutID" => $layout["layoutID"],
                    "recordID" => -1,
                    "recordType" => "global",
                ];
            }
        }
        $layout["layoutViews"] = $appliedLayouts;
        return $layout;
    }

    //region Schema retrieval methods
    /**
     * Get the schema specific to validating layout IDs
     *
     * @return Schema
     */
    public function getIDSchema(): Schema
    {
        return Schema::parse(["layoutID:i|s"]);
    }

    /**
     * Get the schema to use when inputting a layout's metadata.
     *
     * @param bool $includesLayoutID include Layout ID in the schema or not.
     *
     * @return Schema
     */
    public function getQueryInputSchema($includesLayoutID = false): Schema
    {
        $schema = [
            "expand?" => ApiUtils::getExpandDefinition(["layoutViews"]),
        ];
        if ($includesLayoutID) {
            $schema += ["layoutID:i|s"];
        }
        return Schema::parse($schema);
    }

    /**
     * Get the schema to use when outputting a layout's metadata.
     *
     * @return Schema
     */
    public function getMetadataSchema(): Schema
    {
        return Schema::parse([
            "layoutID:i|s",
            "name:s",
            "layoutViewType:s",
            "isDefault:b",
            "insertUserID:i",
            "dateInserted:dt",
            "updateUserID:i?",
            "dateUpdated:dt?",
            "layoutViews:a?",
        ]);
    }

    /**
     * Get a schema representing a hydrated layout.
     *
     * @return Schema
     */
    public function getHydratedSchema(): Schema
    {
        return Schema::parse([
            "layoutID:i|s",
            "name:s",
            "layoutViewType:s",
            "isDefault:b",
            "layout:a",
            "seo:o",
            "contexts:a?",
        ]);
    }

    /**
     * Get the schema for layout definition for output.
     *
     * @return Schema
     */
    public function getFullSchema(): Schema
    {
        return $this->getMetadataSchema()->merge(Schema::parse(["layout:a"]));
    }

    /**
     * Get the schema to use when retrieving the editable layout fields
     *
     * @return Schema
     */
    public function getEditSchema(): Schema
    {
        return Schema::parse(["layoutID:i|s", "name:s" => ["maxLength" => 100], "layout:a"]);
    }

    /**
     * Get the schema to use when applying a layout edit to a layout
     *
     * @return Schema
     */
    public function getPatchSchema(): Schema
    {
        return Schema::parse([
            "name:s?" => ["maxLength" => 100],
            "layout:a?",
        ]);
    }

    /**
     * Get the schema to use when creating a new layout
     *
     * @param string[] $viewTypes The layout view types to use for validation.
     *
     * @return Schema
     */
    public function getCreateSchema(array $viewTypes): Schema
    {
        return Schema::parse([
            "name:s" => ["maxLength" => 100],
            "layoutViewType:s" => ["enum" => $viewTypes],
            "layout:a",
        ]);
    }
    //endregion

    //region MutableLayoutProviderInterface methods
    /**
     * @inheritdoc
     */
    public function isIDFormatSupported($layoutID): bool
    {
        return filter_var($layoutID, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * @inheritdoc
     */
    public function getAll(): array
    {
        return $this->select([], [self::OPT_ORDER => "layoutID"]);
    }

    /**
     * Get Original Layout by ID, without applying Machine Translation
     *
     * @param int $layoutID The layout ID
     */
    public function getOriginalByID($layoutID): array
    {
        $layout = $this->selectSingle(["layoutID" => $layoutID]);
        return $layout;
    }

    /**
     * @inheritdoc
     */
    public function getByID($layoutID): array
    {
        $layout = $this->selectSingle(["layoutID" => $layoutID]);
        $communityMachineTranslationModel = Gdn::getContainer()->get(CommunityMachineTranslationModel::class);
        $layout = $communityMachineTranslationModel->replaceTranslatableRecord(
            "layout",
            $layout,
            \Gdn::locale()->current()
        );
        return $layout;
    }

    /**
     * @inheritdoc
     */
    public function updateLayout($layoutID, array $fields): array
    {
        $_ = $this->update($fields, ["layoutID" => $layoutID], [Model::OPT_LIMIT => 1]);
        return $this->getByID($layoutID);
    }

    /**
     * @inheritdoc
     */
    public function deleteLayout($layoutID): void
    {
        $this->delete(["layoutID" => $layoutID]);
    }

    /**
     * Get Layout to hydrate based on the layout Type.
     *
     * @param string $layoutType type of the layout.
     * @param AbstractCustomLayoutView|null $fileLayoutView default file based layout for the given type.
     *
     * @throws ClientException Client Exception.
     * @throws NotFoundException Not Found Exception.
     */
    public function getLayoutFromLayoutType(string $layoutType, ?AbstractCustomLayoutView $fileLayoutView)
    {
        $layoutView = $this->layoutViewModel->getLayoutViews(true, $layoutType);
        if (count($layoutView) == 0) {
            $layoutID = $fileLayoutView->getTemplateID();
        } else {
            $layoutID = $layoutView["layoutID"];
        }

        $provider = $this->layoutProviderService->getCompatibleProvider($layoutID);
        if (!isset($provider)) {
            throw new ClientException("Invalid layout ID format", 400, ["layoutID" => $layoutID]);
        }
        try {
            $row = $provider->getByID($layoutID);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Layout");
        }
        return $row;
    }
    //endregion
    //endregion

    //region Non-Public Methods
    //endregion
}
