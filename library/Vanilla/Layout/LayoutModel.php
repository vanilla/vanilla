<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2022 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Layout\Providers\MutableLayoutProviderInterface;
use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\ModelUtils;

/**
 * Model for managing persisted layout data
 */
class LayoutModel extends PipelineModel implements MutableLayoutProviderInterface {

    //region Properties
    private const TABLE_NAME = 'layout';

    /** @var LayoutModel $layoutViewModel */
    private $layoutViewModel;

    //endregion

    //region Constructor
    /**
     * DI Constructor
     *
     * @param CurrentUserFieldProcessor $userFieldProcessor
     * @param CurrentDateFieldProcessor $dateFieldProcessor
     * @param JsonFieldProcessor $jsonFieldProcessor
     * @param LayoutViewModel $layoutViewModel
     */
    public function __construct(
        CurrentUserFieldProcessor $userFieldProcessor,
        CurrentDateFieldProcessor $dateFieldProcessor,
        JsonFieldProcessor        $jsonFieldProcessor,
        LayoutViewModel           $layoutViewModel
    ) {
        parent::__construct(self::TABLE_NAME);

        $userFieldProcessor->camelCase();
        $this->addPipelineProcessor($userFieldProcessor);

        $dateFieldProcessor->camelCase();
        $this->addPipelineProcessor($dateFieldProcessor);

        $jsonFieldProcessor->setFields(['layout']);
        $this->addPipelineProcessor($jsonFieldProcessor);
        $this->layoutViewModel = $layoutViewModel;
    }
    //endregion

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
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void {
        $database
            ->structure()
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
    }

    /**
     * Normalize a layout record from the database into API output.
     *
     * @param array $row Layout record from database row
     * @param array|string|bool $expand Additional parameters used to expand output based on row property values
     * @return array Normalized row
     */
    public function normalizeRow(array $row, $expand = false): array {

        // File-based layouts are set as defaults, which have string IDs
        $row['isDefault'] = !is_numeric($row['layoutID']);
        if (ModelUtils::isExpandOption('layoutViews', $expand)) {
            $row['layoutViews'] = $this->layoutViewModel->normalizeRows($this->layoutViewModel->getViewsByLayoutID($row['layoutID']), $expand);
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
    public function normalizeRows(array $rows, $expand): array {
        $layoutViews = [];
        if (ModelUtils::isExpandOption('layoutViews', $expand)) {
            // Get all of the IDs from the result row
            $ids = array_map(function (array $row) {
                return $row['layoutID'];
            }, $rows);
            //expand layout Views based on layoutIDs .
            $layoutViews = $this->layoutViewModel->normalizeRows($this->layoutViewModel->getViewsByLayoutIDs($ids), $expand);
        }

        $rows = array_map(function (array $row) use ($layoutViews, $expand) {
            $row = $this->normalizeRow($row);
            //If expand parameter present add layoutViews to the request.
            if (ModelUtils::isExpandOption('layoutViews', $expand)) {
                $currentLayoutModel = array_filter($layoutViews, function ($layoutView) use ($row) {
                    return $layoutView['layoutID'] == $row['layoutID'];
                });
                $row['layoutViews'] = $currentLayoutModel;
            }
            return $row;
        }, $rows);

        return $rows;
    }

    //region Schema retrieval methods
    /**
     * Get the schema specific to validating layout IDs
     *
     * @return Schema
     */
    public function getIDSchema(): Schema {
        return Schema::parse(["layoutID:i|s"]);
    }

    /**
     * Get the schema to use when inputting a layout's metadata.
     *
     * @param bool $includesLayoutID include Layout ID in the schema or not.
     *
     * @return Schema
     */
    public function getQueryInputSchema($includesLayoutID = false): Schema {
        $schema = [
            'expand?' => ApiUtils::getExpandDefinition(['layoutViews'])
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
    public function getMetadataSchema(): Schema {
        return Schema::parse([
            "layoutID:i|s",
            "name:s",
            "layoutViewType:s",
            "isDefault:b",
            "insertUserID:i",
            "dateInserted:dt",
            "updateUserID:i?",
            "dateUpdated:dt?",
            'layoutViews:a?'
        ]);
    }

    /**
     * Get a schema representing a hydrated layout.
     *
     * @return Schema
     */
    public function getHydratedSchema(): Schema {
        return Schema::parse([
            "layoutID:i|s",
            "name:s",
            "layoutViewType:s",
            "isDefault:b",
            "layout:a",
            'seo:o',
        ]);
    }

    /**
     * Get the schema for layout definition for output.
     *
     * @return Schema
     */
    public function getFullSchema(): Schema {
        return $this->getMetadataSchema()->merge(Schema::parse(["layout:a"]));
    }

    /**
     * Get the schema to use when retrieving the editable layout fields
     *
     * @return Schema
     */
    public function getEditSchema(): Schema {
        return Schema::parse([
            "layoutID:i|s",
            "name:s" => ['maxLength' => 100],
            "layout:a"
        ]);
    }

    /**
     * Get the schema to use when applying a layout edit to a layout
     *
     * @return Schema
     */
    public function getPatchSchema(): Schema {
        return Schema::parse([
            "name:s?" => ['maxLength' => 100],
            "layout:a?"
        ]);
    }

    /**
     * Get the schema to use when creating a new layout
     *
     * @param string[] $viewTypes The layout view types to use for validation.
     *
     * @return Schema
     */
    public function getCreateSchema(array $viewTypes): Schema {
        return Schema::parse([
            "name:s" => ['maxLength' => 100],
            "layoutViewType:s" => ['enum' => $viewTypes],
            "layout:a",
        ]);
    }
    //endregion

    //region MutableLayoutProviderInterface methods
    /**
     * @inheritdoc
     */
    public function isIDFormatSupported($layoutID): bool {
        return filter_var($layoutID, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * @inheritdoc
     */
    public function getAll(): array {
        return $this->select([], [self::OPT_ORDER => 'layoutID']);
    }

    /**
     * @inheritdoc
     */
    public function getByID($layoutID): array {
        return $this->selectSingle(['layoutID' => $layoutID]);
    }

    /**
     * @inheritdoc
     */
    public function updateLayout($layoutID, array $fields): array {
        $_ = $this->update($fields, ['layoutID' => $layoutID], [Model::OPT_LIMIT => 1]);
        return $this->getByID($layoutID);
    }

    /**
     * @inheritdoc
     */
    public function deleteLayout($layoutID): void {
        $this->delete(['layoutID' => $layoutID]);
    }
    //endregion
    //endregion

    //region Non-Public Methods
    //endregion
}
