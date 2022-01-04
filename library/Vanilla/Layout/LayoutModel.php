<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Garden\Schema\Schema;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Layout\Providers\MutableLayoutProviderInterface;
use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;

/**
 * Model for managing persisted layout data
 */
class LayoutModel extends PipelineModel implements MutableLayoutProviderInterface {

    //region Properties
    private const TABLE_NAME = 'layout';

    //endregion

    //region Constructor
    /**
     * DI Constructor
     *
     * @param CurrentUserFieldProcessor $userFieldProcessor
     * @param CurrentDateFieldProcessor $dateFieldProcessor
     * @param JsonFieldProcessor $jsonFieldProcessor
     */
    public function __construct(
        CurrentUserFieldProcessor $userFieldProcessor,
        CurrentDateFieldProcessor $dateFieldProcessor,
        JsonFieldProcessor        $jsonFieldProcessor
    ) {
        parent::__construct(self::TABLE_NAME);

        $userFieldProcessor->camelCase();
        $this->addPipelineProcessor($userFieldProcessor);

        $dateFieldProcessor->camelCase();
        $this->addPipelineProcessor($dateFieldProcessor);

        $jsonFieldProcessor->setFields(['layout']);
        $this->addPipelineProcessor($jsonFieldProcessor);
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
     * @param array $expand Additional parameters used to expand output based on row property values
     * @return array Normalized row
     */
    public function normalizeRow(array $row, array $expand = []): array {
        // File-based layouts are set as defaults, which have string IDs
        $row['isDefault'] = !is_numeric($row['layoutID']);
        $row['isActive'] = false; //TODO: derive the value based on whether any layout views reference this layout ID
        return $row;
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
            "isActive:b",
            "insertUserID:i",
            "dateInserted:dt",
            "updateUserID:i?",
            "dateUpdated:dt?"
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
            "isActive:b",
            "layout:a",
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
            "layoutID:i",
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
