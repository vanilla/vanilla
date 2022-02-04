<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Garden\Schema\Schema;
use Garden\Web\Exception\ServerException;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\SchemaFactory;

/**
 * Model for managing the layout view table.
 */
class LayoutViewModel extends FullRecordCacheModel {

    //region Properties
    private const TABLE_NAME = "layoutView";
    private $layoutModel;
    //endregion

    //region Constructor
    /**
     * DI Constructor.
     *
     * @param CurrentUserFieldProcessor $userFields
     * @param CurrentDateFieldProcessor $dateFields
     * @param LayoutModel $layoutModel
     * @param \GDN_Cache $cache
     */
    public function __construct(
        CurrentUserFieldProcessor $userFields,
        CurrentDateFieldProcessor $dateFields,
        LayoutModel $layoutModel,
        \GDN_Cache $cache
    ) {
        parent::__construct(self::TABLE_NAME, $cache);
        $userFields->camelCase();
        $this->addPipelineProcessor($userFields);
        $dateFields->camelCase();
        $this->addPipelineProcessor($dateFields);
        $this->layoutModel = $layoutModel;
    }

    //endregion


    //region Public Methods
    /**
     * Get a list of Layout Views by a provided Layout ID
     *
     * @param int $layoutID ID of the layout
     *
     * @return array
     */
    public function getViewsByLayoutID(int $layoutID): array {

        $rows = $this->select(['layoutID' => $layoutID]);
        $layout = $this->layoutModel->selectSingle(['layoutID' => $layoutID]);
        foreach ($rows as &$row) {
            $row['layoutViewType'] = $layout['layoutViewType'];
        }
        return $rows;
    }

    /**
     * Get a list of Layout Views by a provided viewType, recordType and or record ID
     *
     * @param string $layoutViewType view type
     * @param string|null $recordType record Type
     * @param int|null $recordID ID of the record
     *
     * @return array
     */
    public function getLayoutViews(string $layoutViewType, string $recordType = null, int $recordID = null): array {

        $layout = $this->layoutModel->selectSingle(['layoutViewType' => $layoutViewType]);

        $where = ['layoutID' => $layout['layoutID']];
        if ($recordType != null) {
            $where['recordType'] = $recordType;
        }
        if ($recordID != null) {
            $where['recordID'] = $recordID;
        }
        $row = $this->selectSingle($where);
        if ($row === null) {
            if ($recordID != null) {
                $row = $this->getLayoutViews($layoutViewType, $recordType);
            } else if ($recordType != null) {
                $row = $this->getLayoutViews($layoutViewType);
            }
        } else {
            $row['layoutViewType'] = $layout['layoutViewType'];
        }
        return $row;
    }

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
        $construct = $database->structure();
        $sql = $database->sql();
        $construct->table(self::TABLE_NAME)
            ->primaryKey("layoutViewID")
            ->column("layoutID", "int", false, ["index", "unique.record"])
            ->column("recordID", "int", 0, ["unique.record"])
            ->column("recordType", "varchar(100)", false, ["unique.record"])
            ->column("insertUserID", "int", false)
            ->column("dateInserted", "datetime", false)
            ->column("updateUserID", "int", null)
            ->column("dateUpdated", "datetime", null)
            ->set($explicit, $drop);
        $default = [
            'layoutViewID' => 1,
            'layoutID' => 1,
            'recordID' => 0,
            'recordType' => 'global',
            'insertUserID' => 2,
            'dateInserted' => date('Y-m-d H:i:s'),
            'updateUserID' => 2,
            'dateUpdated' => date('Y-m-d H:i:s')];

        $insertDefault = !$database->structure()->tableExists(self::TABLE_NAME)
            || $sql->getWhere(self::TABLE_NAME, ['layoutViewID' => 1])->numRows() == 0;

        if ($insertDefault) {
            $sql->insert(self::TABLE_NAME, $default);
        }
    }

    /**
     * @return Schema
     */
    public static function getSchema(): Schema {
        return SchemaFactory::parse([
            'layoutViewID:i',
            'layoutID:i|s',
            'recordID:i',
            'recordType:s',
            'layoutViewType:s',
            'insertUserID:i',
            'dateInserted:dt'
        ], 'LayoutView');
    }
    //endregion
}
