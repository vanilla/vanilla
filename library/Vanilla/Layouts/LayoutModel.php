<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layouts;

use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Model for managing persisted layout data
 */
class LayoutModel extends PipelineModel {

    //region Properties
    private const TABLE_NAME = 'layout';

    //endregion

    //region Constructor
    /**
     * DI Constructor
     *
     * @param CurrentUserFieldProcessor $userFields
     * @param CurrentDateFieldProcessor $dateFields
     */
    public function __construct(CurrentUserFieldProcessor $userFields, CurrentDateFieldProcessor $dateFields) {
        parent::__construct(self::TABLE_NAME);

        $userFields->camelCase();
        $this->addPipelineProcessor($userFields);

        $dateFields->camelCase();
        $this->addPipelineProcessor($dateFields);
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
            ->column("layoutType", "varchar(40)", false)
            ->column("layout", "mediumtext", false)
            ->column("insertUserID", "int", false)
            ->column("dateInserted", "datetime", false)
            ->column("updateUserID", "int", null)
            ->column("dateUpdated", "datetime", null)
            ->set($explicit, $drop);
    }
    //endregion

    //region Non-Public Methods
    //endregion
}
