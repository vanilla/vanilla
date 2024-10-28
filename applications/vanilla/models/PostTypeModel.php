<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ClientException;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;

class PostTypeModel extends PipelineModel
{
    const FEATURE_POST_TYPES_AND_POST_FIELDS = "PostTypesAndPostFields";

    const BASE_TYPES = ["discussion", "question", "idea", "poll", "event"];

    /**
     * D.I.
     */
    public function __construct(private EventManager $eventManager)
    {
        parent::__construct("postType");

        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted", "dateUpdated"], ["dateUpdated"]));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["isOriginal", "isActive", "isDeleted"]));
        $userProcessor = new CurrentUserFieldProcessor(\Gdn::session());
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Get base query for querying post types.
     *
     * @param array $where
     * @return \Gdn_SQLDriver
     */
    private function getWhereQuery(array $where)
    {
        unset($where["page"], $where["limit"]);
        $sql = $this->createSql()->from($this->getTable());

        $sql->where("isDeleted", 0);
        $sql->where($where);

        return $sql;
    }

    /**
     * Query post types with filters.
     *
     * @param array $where
     * @param array $options
     * @return array|null
     * @throws \Exception
     */
    public function getWhere(array $where, array $options = [])
    {
        $sql = $this->getWhereQuery($where);

        $sql->applyModelOptions($options);

        $rows = $sql->get()->resultArray();
        return $rows;
    }

    /**
     * Query post type count with filters.
     *
     * @param array $where
     * @return int
     */
    public function getWhereCount(array $where): int
    {
        return $this->getWhereQuery($where)->getPagingCount("postTypeID");
    }

    /**
     * @param int $id
     * @return array|null
     * @throws \Exception
     */
    public function getPostType(int $id)
    {
        $rows = $this->getWhere(["postTypeID" => $id], [Model::OPT_LIMIT => 1]);
        return $rows[0] ?? null;
    }

    /**
     * Returns the schema for displaying post types.
     *
     * @return Schema
     */
    public function outputSchema(): Schema
    {
        return Schema::parse([
            "postTypeID",
            "apiName",
            "name",
            "parentPostTypeID",
            "baseType",
            "isOriginal",
            "isActive",
            "isDeleted",
            "dateInserted",
            "dateUpdated",
            "insertUserID",
            "updateUserID",
        ]);
    }

    /**
     * Return possible base types including types from enabled addons.
     *
     * @return mixed
     */
    public function getBaseTypes(): array
    {
        $baseTypes = ["discussion"];

        return $this->eventManager->fireFilter("PostTypeModel_getBaseTypes", $baseTypes);
    }

    /**
     * Returns the schema for creating post types.
     *
     * @return Schema
     */
    public function postSchema(): Schema
    {
        $schema = Schema::parse([
            "apiName:s",
            "parentPostTypeID:i?",
            "baseType:s" => ["enum" => $this->getBaseTypes()],
        ])->merge($this->patchSchema());
        return $schema;
    }

    /**
     * Returns the schema for updating post types.
     *
     * @return Schema
     */
    public function patchSchema(): Schema
    {
        $schema = Schema::parse(["name:s", "isActive:b?" => ["default" => false]]);
        return $schema;
    }

    /**
     * Validator that checks if the table already contains a record with the given field value.
     *
     * @return callable
     */
    public function createUniqueApiNameValidator(): callable
    {
        return function ($value, ValidationField $field) {
            $where = ["apiName" => $value, "isDeleted <>" => 1];

            $count = $this->createSql()->getCount($this->getTable(), $where);
            if ($count !== 0) {
                $field->addError("This post type API name is already in use. Use a unique API name.");
            }
        };
    }

    /**
     * Structures the postType table.
     *
     * @param \Gdn_MySQLStructure $structure
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        $structure
            ->table("postType")
            ->primaryKey("postTypeID")
            ->column("apiName", "varchar(100)", "index")
            ->column("name", "varchar(100)")
            ->column("parentPostTypeID", "int", true)
            ->column("baseType", self::BASE_TYPES)
            ->column("isOriginal", "tinyint", 0)
            ->column("isActive", "tinyint", 0)
            ->column("isDeleted", "tinyint", 0)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("insertUserID", "int")
            ->column("updateUserID", "int", true)
            ->set();
    }

    public static function ensurePostTypesFeatureEnabled()
    {
        if (!FeatureFlagHelper::featureEnabled(self::FEATURE_POST_TYPES_AND_POST_FIELDS)) {
            throw new ClientException("Post Types & Post Fields is not enabled.");
        }
    }
}
