<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentIPAddressProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\ArrayUtils;

/**
 * Model for managing the recordStatus table.
 */
class RecordStatusModel extends PipelineModel {

    private const TABLE_NAME = "recordStatus";

    public const DISCUSSION_STATUS_UNANSWERED = 1;
    public const DISCUSSION_STATUS_ANSWERED = 2;
    public const DISCUSSION_STATUS_ACCEPTED = 3;
    public const DISCUSSION_STATUS_REJECTED = 4;
    public const COMMENT_STATUS_ACCEPTED = 5;
    public const COMMENT_STATUS_REJECTED = 6;
    public const DISCUSSION_STATUS_UNRESOLVED = 7;
    public const DISCUSSION_STATUS_RESOLVED = 8;

    /** @var array $systemDefinedIDs */
    public static $systemDefinedIDs = [
        RecordStatusModel::DISCUSSION_STATUS_UNANSWERED,
        RecordStatusModel::DISCUSSION_STATUS_ANSWERED,
        RecordStatusModel::DISCUSSION_STATUS_ACCEPTED,
        RecordStatusModel::DISCUSSION_STATUS_REJECTED,
        RecordStatusModel::COMMENT_STATUS_ACCEPTED,
        RecordStatusModel::COMMENT_STATUS_REJECTED,
        RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED,
        RecordStatusModel::DISCUSSION_STATUS_RESOLVED,
    ];

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

        $booleanFields = new BooleanFieldProcessor(["isDefault", "isSystem"]);
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
            "isSystem" => ["type" => "boolean"],
        ]);

        return $schema;
    }

    /**
     * Add a record status.
     *
     * @param array $set Field values to set.
     * @param array $options See Vanilla\Models\Model::OPT_*
     * @return mixed ID of the inserted row.
     * @throws ClientException Attempting to insert a system-defined record status.
     * @throws \Exception If an error is encountered while performing the query.
     */
    public function insert(array $set, array $options = []) {
        if (!empty($set['isSystem'])) {
            throw new ClientException("Cannot insert a system defined record status");
        }
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
     * @return bool
     * @throws ClientException If attempting to update a system defined record status.
     * @throws \Exception If an error is encountered while performing the query.
     */
    public function update(array $set, array $where, array $options = []): bool {
        if (!empty($set['isSystem']) || !empty($where['isSystem'])) {
            throw new ClientException("Cannot update system defined statuses");
        }
        $matchingSystemRecords = array_filter(parent::select($where), function ($candidate) {
            return !empty($candidate['isSystem']);
        });
        if (!empty($matchingSystemRecords)) {
            throw new ClientException("Cannot update system defined statuses");
        }
        $result = parent::update($set, $where, $options);
        $statusID = $where["statusID"] ?? null;
        $this->updateRecordTypeStatus($statusID, null, $set);

        return $result;
    }

    /**
     * Delete resource rows.
     *
     * @param array $where Conditions to restrict the deletion.
     * @param array $options Options for the delete query.
     *    - limit (int): Limit on the results to be deleted.
     * @throws \Exception If an error is encountered while performing the query.
     * @return bool True.
     * @throws ClientException Attempting to delete system defined status.
     * @throws ClientException Attempting to delete default status.
     */
    public function delete(array $where, array $options = []): bool {
        $candidates = parent::select($where, $options);
        if (empty($candidates)) {
            return true;
        }
        $matchingSystemRecords = array_filter($candidates, function (array $candidate) {
            return !empty($candidate['isSystem']);
        });
        if (!empty($matchingSystemRecords)) {
            throw new ClientException("Cannot delete system defined statuses");
        }
        $matchingDefaults = array_filter($candidates, function (array $candidate) {
            return !empty($candidate['isDefault']);
        });
        if (!empty($matchingDefaults)) {
            throw new ClientException("Default status cannot be deleted");
        }
        return parent::delete($where, $options);
    }

    /**
     * Convert the provided ideation-specific status to its corresponding record status.
     * If corresponding record status does not exist, the array returned will not have a
     * primary key value set. It is then the caller's responsibility to insert the record status
     * using the returned value.
     *
     * @param array $ideationStatus Status record from GDN_Status specific to ideation
     * @return array Corresponding record status record, which, if missing its primary key value,
     * indicates that the corresponding record status record has not yet been persisted.
     * @throws \Garden\Schema\ValidationException Row fails to validate against schema.
     * @throws \Vanilla\Exception\Database\NoResultsException If ideation status references
     * a recordStatus record value that cannot be found.
     */
    public function convertFromIdeationStatus(array $ideationStatus): array {
        //StatusID excluded from required properties check to allow for converting pending inserts
        if (empty($ideationStatus['Name'])
            || empty($ideationStatus['State'])
            || !array_key_exists('IsDefault', $ideationStatus)) {
            throw new \InvalidArgumentException("Status provided does not include one or more required properties");
        }
        if (!empty($ideationStatus['recordStatusID'])) {
            $row = $this->selectSingle(['statusID' => $ideationStatus['recordStatusID']]);
            return $row;
        }

        // Most of the column names are the same between GDN_Status and GDN_recordStatus
        // but the older GDN_Status column names contain an initial capital letter that
        // we're normalizing to an initial lowercase letter.
        $ideationStatus = ArrayUtils::camelCase($ideationStatus);
        $schemaProps = $this->getSchema()->getSchemaArray()['properties'];
        $convertedStatus = array_intersect_key($ideationStatus, $schemaProps);
        $convertedStatus['state'] = isset($convertedStatus['state']) ? lcfirst($convertedStatus['state']) : 'open';
        $defaults = ['recordType' => 'discussion', 'recordSubtype' => 'ideation', 'isSystem' => 0];
        $convertedStatus = array_merge($convertedStatus, $defaults);
        unset($convertedStatus['statusID']);

        return $convertedStatus;
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
                "isSystem" => false,
                "statusID <>" => $statusID,
            ]
        );
    }
}
