<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Exception;
use Garden\Web\Exception\ClientException;
use Gdn_Database;
use Gdn_DatabaseStructure;
use Gdn_Session;
use PDO;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\PipelineModel;

/**
 * TranslationPropertyModel class.
 */
class TranslationPropertyModel extends PipelineModel
{
    /** @var Gdn_Session */
    private $session;

    const RESOURCE_KEY_RECORD = [
        "recordType" => true,
        "recordID" => true,
        "recordKey" => true,
        "propertyName" => true,
        "propertyType" => true,
        "key" => true,
    ];

    /** Default limit on the number of results returned. */
    const LIMIT_DEFAULT = 1000;

    /**
     * resourceKeyModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session)
    {
        parent::__construct("translationProperty");

        $this->session = $session;

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Create a resource key.
     *
     * @param string $path
     * @param array $record
     *
     * @return array
     */
    public function createTranslationProperty(string $path, array $record): array
    {
        $record["resource"] = $path;

        $recordIdentifier = $this->getRecordIdentifier($record);

        $record["translationPropertyKey"] = self::createTranslationPropertyKey(
            $record["propertyName"],
            $record["recordType"],
            $recordIdentifier
        );
        $this->insert($record);
        $translationProperty = $this->get(["translationPropertyKey" => $record["translationPropertyKey"]]);

        $result = reset($translationProperty);
        return $result;
    }

    /**
     * Get a Translation-Property.
     *
     * @param array $record
     * @return array
     */
    public function getTranslationProperty(array $record): array
    {
        $recordIdentifier = $this->getRecordIdentifier($record);
        $key = self::createTranslationPropertyKey($record["propertyName"], $record["recordType"], $recordIdentifier);
        $translationProperty = $this->get(["translationPropertyKey" => $key]);
        if ($translationProperty) {
            $translationProperty = reset($translationProperty);
        }

        return $translationProperty;
    }

    /**
     * Construct a translationPropertyKey for the resource translations.
     *
     * @param string $recordProperty
     * @param string $recordType
     * @param mixed $recordIdentifier
     *
     * @return string;
     */
    public static function createTranslationPropertyKey(
        string $recordProperty,
        string $recordType = null,
        $recordIdentifier = null
    ): string {
        return $recordType . "." . $recordIdentifier . "." . $recordProperty;
    }

    /**
     * Get translations.
     *
     * @param array $where
     * @param array $options
     * @return array
     */
    public function getTranslations(array $where = [], array $options = [])
    {
        $limit = $options["limit"] ?? self::LIMIT_DEFAULT;
        $offset = $options["offset"] ?? 0;

        $sql = $this->sql();
        $sql->from($this->getTable() . " as tp")->join(
            "translation t",
            "tp.translationPropertyKey = t.translationPropertyKey",
            "inner"
        );

        $sql->where($where);
        $sql->limit($limit, $offset);

        $result = $sql->get()->resultArray();

        return $result;
    }

    /**
     * Get translation of properties.
     *
     * @param array $where
     * @param array $properties
     * @return array
     */
    public function translateProperties(array $where, array $properties)
    {
        $result = [];

        $sql = $this->sql();
        $sql->from($this->getTable() . " as tp")
            ->select("tp.recordID")
            ->join("translation t", "tp.translationPropertyKey = t.translationPropertyKey", "inner");

        if (count($properties) > 0) {
            $where["tp.propertyName"] = $properties;
            foreach ($properties as $propertyName) {
                $pdo = $sql->Database->connection();
                $sql->select(
                    "IF(tp.propertyName = " . $pdo->quote($propertyName, PDO::PARAM_STR) . ", t.translation, null)",
                    "MAX",
                    $propertyName
                );
            }

            $sql->where($where);
            $sql->groupBy("recordID");

            $result = $sql->get()->resultArray();
            $result = array_combine(array_column($result, "recordID"), $result);
        }
        return $result;
    }
    /**
     * Get the record identifier used to build a translation-property key.
     *
     * @param array $record
     * @return mixed
     * @throws ClientException
     */
    private function getRecordIdentifier(array $record)
    {
        $identifier = null;
        if (isset($record["recordID"]) && isset($record["recordKey"])) {
            throw new ClientException("A resource key can't have both a recordID or recordKey");
        } elseif (isset($record["recordID"])) {
            $identifier = $record["recordID"];
        } elseif (isset($record["recordKey"])) {
            $identifier = $record["recordKey"];
        } else {
            $identifier = "";
        }
        return $identifier;
    }

    /**
     * Structure our database schema.
     *
     * @param Gdn_Database $database
     * @param bool $explicit
     * @param bool $drop
     * @return void
     * @throws Exception
     */
    public static function structure(Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("translationProperty")
            ->column("resource", "varchar(64)", false, ["index"])
            ->column("recordType", "varchar(64)", false, ["index"])
            ->column("recordID", "int", true)
            ->column("recordKey", "varchar(32)", true)
            ->column("propertyName", "varchar(32)", false, ["index"])
            ->column("propertyType", "varchar(32)", true)
            ->column("translationPropertyKey", "varchar(255)", false, ["index", "unique.translationPropertyKey"])
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime")
            ->set($explicit, $drop);
    }
}
