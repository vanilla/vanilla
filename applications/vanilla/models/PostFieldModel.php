<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Garden\Schema\ValidationField;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;
class PostFieldModel extends PipelineModel
{
    const DATA_TYPES = ["text", "boolean", "date", "number", "string[]", "number[]"];

    const FORM_TYPES = ["text", "text-multiline", "dropdown", "tokens", "checkbox", "date", "number"];

    const VISIBILITIES = ["public", "private", "internal"];

    /**
     * D.I.
     */
    public function __construct()
    {
        parent::__construct("postField");

        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted", "dateUpdated"], ["dateUpdated"]));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["isRequired", "isActive"]));
        $userProcessor = new CurrentUserFieldProcessor(\Gdn::session());
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
        $this->addPipelineProcessor(new JsonFieldProcessor(["displayOptions"]));
        $this->addPipelineProcessor(new JsonFieldProcessor(["dropdownOptions"], 0));
    }

    /**
     * Get current max sort value from the table
     *
     * @return int
     * @throws \Exception
     */
    private function getMaxSort(): int
    {
        return (int) $this->createSql()
            ->select("max(sort) as sortMax")
            ->from($this->getTable())
            ->get()
            ->value("sortMax");
    }

    /**
     * Add a row with a default sort value if not provided.
     *
     * {@inheritDoc}
     */
    public function insert(array $set, array $options = [])
    {
        if (!isset($set["sort"])) {
            // By default, set the sort value to the max sort value + 1;
            $set["sort"] = $this->getMaxSort() + 1;
        }
        return parent::insert($set, $options);
    }

    /**
     * Update sort values for records using a postFieldID => sort mapping.
     *
     * @param array<int,int> $sorts Key-value mapping of postFieldID => sort
     * @return void
     * @throws \Exception
     */
    public function updateSorts(array $sorts): void
    {
        try {
            $this->database->beginTransaction();
            foreach ($sorts as $postFieldID => $sort) {
                $this->update(["sort" => $sort], ["postFieldID" => $postFieldID]);
            }
            $this->database->commitTransaction();
        } catch (\Exception $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Get base query for querying post fields.
     *
     * @param array $where
     * @return \Gdn_SQLDriver
     */
    private function getWhereQuery(array $where)
    {
        unset($where["page"], $where["limit"]);
        $sql = $this->createSql()->from($this->getTable());

        $sql->where($where);

        return $sql;
    }

    /**
     * Query post fields with filters.
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
        $this->normalizeRows($rows);
        return $rows;
    }

    /**
     * Query post field count with filters.
     *
     * @param array $where
     * @return int
     */
    public function getWhereCount(array $where): int
    {
        return $this->getWhereQuery($where)->getPagingCount("postFieldID");
    }

    /**
     * @param array $rows
     * @return void
     */
    private function normalizeRows(array &$rows): void
    {
        foreach ($rows as &$row) {
            if (is_string($row["dropdownOptions"])) {
                $row["dropdownOptions"] = json_decode($row["dropdownOptions"]);
            }
        }
    }

    /**
     * @param int $id
     * @return array|null
     * @throws \Exception
     */
    public function getPostField(int $id)
    {
        $rows = $this->getWhere(["postFieldID" => $id], [Model::OPT_LIMIT => 1]);
        return $rows[0] ?? null;
    }

    /**
     * Validator that checks if the table already contains a record with the given field value.
     *
     * @param int|null $id
     * @return callable
     */
    public function createUniqueApiNameValidator(): callable
    {
        return function (array $data, ValidationField $field) {
            $where = ["apiName" => $data["apiName"], "postTypeID" => $data["postTypeID"]];

            $count = $this->createSql()->getCount($this->getTable(), $where);
            if ($count !== 0) {
                $field->addError("This post field API name is already in use. Use a unique API name.");
            }
        };
    }

    /**
     * Structures the postField table.
     *
     * @param \Gdn_MySQLStructure $structure
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        $structure
            ->table("postField")
            ->primaryKey("postFieldID")
            ->column("apiName", "varchar(100)", keyType: "unique.apiName_postTypeID")
            ->column("postTypeID", "int", keyType: "unique.apiName_postTypeID")
            ->column("label", "varchar(100)")
            ->column("description", "varchar(500)")
            ->column("dataType", self::DATA_TYPES)
            ->column("formType", self::FORM_TYPES)
            ->column("visibility", "varchar(500)")
            ->column("displayOptions", "json", true)
            ->column("dropdownOptions", "json", true)
            ->column("isRequired", "tinyint", 0)
            ->column("isActive", "tinyint", 0)
            ->column("sort", "tinyint", 0)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("insertUserID", "int")
            ->column("updateUserID", "int", true)
            ->set();
    }
}
