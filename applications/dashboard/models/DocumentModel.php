<?php
/**
 * @author Pavel Goncharov <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard;

use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Models\VectorizeInterface;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Models\ModelFactory;
use Vanilla\Models\PipelineModel;

/**
 * Model for Document.
 */
class DocumentModel extends PipelineModel
{
    // Feature flag for document vectorization
    public const FEATURE_FLAG = "Feature.vectorizedSearch.Enabled";

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(private ModelFactory $factory, private ConfigurationInterface $config)
    {
        parent::__construct("document");
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"])->setUpdateFields(["dateInserted"]);
        $this->addPipelineProcessor($dateProcessor);
    }

    /**
     * Structure for the document table.
     *
     * @param \Gdn_Database $database
     * @param bool $explicit
     * @param bool $drop If true, and the table specified with $this->table() already exists,
     *  this method will drop the table before attempting to re-create it.
     * @return void
     * @throws \Exception
     */
    public static function structure(\Gdn_Database $database, bool $explicit = false, bool $drop = false): void
    {
        $database
            ->structure()
            ->table("document")
            ->primaryKey("documentID")
            ->column("recordType", "varchar(30)", false)
            ->column("recordID", "int", false)
            ->column("sparseVector", "text", true)
            ->column("documentFragment", "text", false)
            ->column("dateInserted", "datetime")
            ->set($explicit, $drop);

        $database
            ->structure()
            ->table("document")
            ->createIndexIfNotExists("IX_document_recordType_recordID", ["recordType", "recordID"]);
    }

    /**
     * Check if Document Vectorization is enabled.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return Gdn::config(self::FEATURE_FLAG);
    }

    /**
     * Return keys of supported resource types.
     *
     * @param string $resourceType - Resource Type coming from API call.
     *
     * @return string|null
     */
    public function supportToChunk(string $resourceType): string|null
    {
        $model = null;
        foreach ($this->factory->getAll() as $recordType => $resource) {
            if ($recordType === $resourceType) {
                $model = $recordType;
                break;
            } elseif ($recordType === substr($resourceType, 0, -1)) {
                $model = $recordType;
                break;
            }
        }
        return $model;
    }

    /**
     * Main call to start evaluating and generating chunks.
     *
     * @param string $resourceType
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function processDocument(string $resourceType, array $data): array
    {
        if ($this->isEnabled()) {
            $resourceType = $this->supportToChunk($resourceType);
            $model = $resourceType !== null ? $this->factory->get($resourceType) : null;
            if ($model instanceof VectorizeInterface) {
                $primaryID = $data[$resourceType . "ID"] ?? null;
                if ($primaryID !== null) {
                    $data = $this->chunkDocument($resourceType, $data);
                } else {
                    $result = [];
                    foreach ($data as $row) {
                        $result = array_merge($result, $this->chunkDocument($resourceType, $row));
                    }
                    $data = array_values($result);
                }
            }
        }
        return $data;
    }

    /**
     * Return an array of the record in chunks.
     *
     * @param string $resourceType
     * @param array $row
     * @return array
     * @throws \Exception
     */
    public function chunkDocument(string $resourceType, array $row): array
    {
        $result = [];

        $primaryID = $row[$resourceType . "ID"] ?? null;

        if ($primaryID !== null && array_key_exists("bodyPlainText", $row)) {
            $chunks = $this->select(["recordID" => $primaryID, "recordType" => $resourceType]);
            if (count($chunks) === 0) {
                $chunks = $this->createDocumentChunks($resourceType, $primaryID, $row["bodyPlainText"]);
            }
            if ($chunks > 0) {
                $index = 0;
                $id = $row["canonicalID"];
                unset($row["body"]);
                unset($row["bodyPlainText"]);
                foreach ($chunks as $chunk) {
                    $row["canonicalID"] = $id . "_" . $index++;
                    $row["chunkText"] = $chunk["documentFragment"];
                    if (isset($chunk["sparseVector"])) {
                        $row["sparse_vector"] = $chunk["sparseVector"];
                    }
                    $result[] = $row;
                }
            } else {
                $result[] = $row;
            }
            return $result;
        }
        return $row;
    }

    /**
     * Create document chunks, and return those chunks
     *
     * @param string $resourceType
     * @param int $primaryID
     * @param string $body
     * @return array
     * @throws \Exception
     */
    public function createDocumentChunks(string $resourceType, int $primaryID, string $body): array
    {
        $chunks = [];
        $chunkSize = $this->config->get("Elastic.chunkSize", 300);
        $words = preg_split("/\s+/", $body);
        $chunk = "";
        $chunkCount = 0;
        foreach ($words as $word) {
            if ($chunkCount >= $chunkSize) {
                $chunks[] = ["recordID" => $primaryID, "recordType" => $resourceType, "documentFragment" => $chunk];
                $chunk = "";
                $chunkCount = 0;
            }
            $chunk .= (strlen($chunk) > 0 ? " " : "") . $word;
            $chunkCount++;
        }
        if ($chunk !== "") {
            $chunks[] = ["recordID" => $primaryID, "recordType" => $resourceType, "documentFragment" => $chunk];
            foreach ($chunks as $chunkArray) {
                $this->insert($chunkArray);
            }
        }
        return $this->select(["recordID" => $primaryID, "recordType" => $resourceType]);
    }
}
