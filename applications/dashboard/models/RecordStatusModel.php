<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentIPAddressProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * Model for managing the recordStatus table.
 */
class RecordStatusModel extends PipelineModel {

    private const TABLE_NAME = "recordStatus";

    /**
     * Setup the model.
     *
     * @param CurrentUserFieldProcessor $userFields
     * @param CurrentIPAddressProcessor $ipFields
     */
    public function __construct(CurrentUserFieldProcessor $userFields, CurrentIPAddressProcessor $ipFields) {
        parent::__construct(self::TABLE_NAME);

        $userFields->camelCase();
        $this->addPipelineProcessor($userFields);

        $ipFields->camelCase();
        $this->addPipelineProcessor($ipFields);

        $dateFields = new CurrentDateFieldProcessor();
        $dateFields->camelCase();
        $this->addPipelineProcessor($dateFields);

        $booleanFields = new BooleanFieldProcessor(["isDefault"]);
        $this->addPipelineProcessor($booleanFields);
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema {
        $schema = Schema::parse([
            "statusID" => ["type" => "integer"],
            "name" => ["type" => "string"],
            "state" => [
                "type" => "string",
                "enum" => [
                    "closed",
                    "open",
                ],
            ],
            "recordType" => ["type" => "string"],
            "recordSubtype" => [
                "type" => "string",
                "allowNull" => true,
            ],
            "isDefault" => ["type" => "boolean"],
        ]);
        return $schema;
    }

    /**
     * Add a record status.
     *
     * @param array $set Field values to set.
     * @param array $options See Vanilla\Models\Model::OPT_*
     * @return mixed ID of the inserted row.
     * @throws Exception If an error is encountered while performing the query.
     */
    public function insert(array $set, array $options = []) {
        $result = parent::insert($set, $options);
        $recordType = $set["recordType"] ?? null;
        $this->updateRecordTypeStatus($result, $recordType, $set);
        return $result;
    }

    /**
     * Update existing record statuses.
     *
     * @param array $set Field values to set.
     * @param array $where Conditions to restrict the update.
     * @param array $options See Vanilla\Models\Model::OPT_*
     * @throws Exception If an error is encountered while performing the query.
     * @return bool
     */
    public function update(array $set, array $where, array $options = []): bool {
        $result = parent::update($set, $where, $options);
        $statusID = $where["statusID"] ?? null;
        $this->updateRecordTypeStatus($statusID, null, $set);

        return $result;
    }

    /**
     * Update the isDefault flag of a record type's statuses, based on updates to that type's statuses.
     *
     * @param mixed $statusID
     * @param string|null $recordType
     * @param array $set
     */
    private function updateRecordTypeStatus($statusID, ?string $recordType = null, array $set = []): void {
        if (is_int($statusID) === false) {
            return;
        }

        $isDefault = $set["isDefault"] ?? null;
        if ($isDefault != true) {
            return;
        }

        // Make an effort to obtain the recordType, if not provided.
        if ($recordType === null) {
            try {
                $row = $this->selectSingle(["statusID" => $statusID]);
            } catch (\Exception $e) {
                return;
            }
            $recordType = $row["recordType"] ?? null;
            if (!is_string($recordType)) {
                return;
            }
        }

        // The setting of isDefault for the base record should've already been performed. We just need to reset the others.
        $this->update(
            ["isDefault" => 0],
            [
                "recordType" => $recordType,
                "statusID <>" => $statusID,
            ]
        );
    }
}
