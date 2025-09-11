<?php
/**
 * @author Pavel Goncharov <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard;

use Exception;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Gdn;
use Generator;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\PipelineModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;

/**
 * Model for Document.
 */
class DocumentModel extends PipelineModel implements SystemCallableInterface
{
    // Feature flag for document vectorization
    public const FEATURE_FLAG = "Search.Vectorization.Enabled";
    public const FRAGMENT_SIZE_CONFIG_KEY = "Search.Vectorization.fragmentSize";
    public const VECTOR_SIZE = 1536;
    const VECTOR_LENGTH_CONFIG = "SearchVectorization.Dimensions";

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     * @param VectorizationModel $vectorizationModel
     */
    public function __construct(private ConfigurationInterface $config, private VectorizationModel $vectorizationModel)
    {
        parent::__construct("document");
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"])->setUpdateFields(["dateInserted"]);
        $this->addPipelineProcessor($dateProcessor);

        $this->addPipelineProcessor(new JsonFieldProcessor(["documentVector"]));
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["generateVector"];
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
            ->column("recordType", "varchar(30)")
            ->column("recordID", "varchar(30)")
            ->column("documentVector", "text", true)
            ->column("documentFragment", "text")
            ->column("dateInserted", "datetime")
            ->set($explicit, $drop);

        $database
            ->structure()
            ->table("document")
            ->createIndexIfNotExists("IX_document_recordType_recordID", ["recordType", "recordID"]);

        // Delete the sparseVector column if it exists.
        $sparseVector = $database
            ->structure()
            ->table("document")
            ->columnExists("sparseVector");
        if ($sparseVector) {
            $database->structure()->dropColumn("sparseVector");
        }
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
     * Generate Sparse vector for LongRunner.
     *
     * @param array $documentIDs
     * @return Generator
     */
    public function generateVector(array $documentIDs): Generator
    {
        $dimensions = $this->config->get(self::VECTOR_LENGTH_CONFIG, self::VECTOR_SIZE);
        $vectorizationService = $this->vectorizationModel->getVectorizationService();
        try {
            $remainingDocumentIDs = $documentIDs;
            foreach ($documentIDs as $documentID) {
                // $documentID Must be integer.
                if (is_int($documentID)) {
                    array_shift($remainingDocumentIDs);
                    $document = $this->selectSingle(["documentID" => $documentID]);
                    $textChunk = $document["documentFragment"];
                    $vector = $vectorizationService->vectorizeText($textChunk);
                    $this->update(["documentVector" => $vector], ["documentID" => $documentID]);
                    yield new LongRunnerSuccessID($documentID);
                } else {
                    yield new LongRunnerFailedID($documentID);
                }
            }
        } catch (LongRunnerTimeoutException $timeoutException) {
            return new LongRunnerNextArgs([$remainingDocumentIDs]);
        } catch (Exception $e) {
            ErrorLogger::error(
                "Error generating Vanilla vector",
                [$vectorizationService->getModelName(), "vectorization"],
                [
                    "exception" => $e->getMessage(),
                ]
            );
        }

        return LongRunner::FINISHED;
    }

    /**
     * Return an array of text fragments based on the record.
     *
     * @param string $resourceType
     * @param array $row
     * @return array
     * @throws \Exception
     */
    public function getTextFragments(string $resourceType, array $row): array
    {
        $result = [];
        if (array_key_exists("bodyPlainText", $row)) {
            $fragments = $this->select(["recordID" => $row["canonicalID"], "recordType" => $resourceType]);
            if (count($fragments) === 0) {
                $fragments = $this->createDocumentFragments($resourceType, $row["canonicalID"], $row["bodyPlainText"]);
            }
            if ($fragments > 0) {
                $index = 0;
                $id = $row["canonicalID"];
                $row["documentID"] = $id;
                unset($row["body"]);
                foreach ($fragments as $fragment) {
                    $row["canonicalID"] = $id . "_" . $index++;
                    $row["bodyPlainText"] = $fragment["documentFragment"];

                    // Dense Vector that are in the DB.
                    if (isset($fragment["documentVector"]) && count($fragment["documentVector"]) == self::VECTOR_SIZE) {
                        $row["documentVector"] = $fragment["documentVector"];
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
     * Create documentFragments, and return those fragments.
     *
     * @param string $resourceType
     * @param string $recordID
     * @param string $body
     * @return array
     * @throws \Exception
     */
    public function createDocumentFragments(string $resourceType, string $recordID, string $body): array
    {
        // Purge the existing documents.
        $fragments = [];
        $fragmentSize = $this->config->get(self::FRAGMENT_SIZE_CONFIG_KEY, 300);
        $words = preg_split("/\s+/", $body);
        $currentFragment = "";
        $fragmentCount = 0;
        foreach ($words as $word) {
            if ($fragmentCount >= $fragmentSize) {
                $fragments[] = [
                    "recordID" => $recordID,
                    "recordType" => $resourceType,
                    "documentFragment" => $currentFragment,
                ];
                $currentFragment = "";
                $fragmentCount = 0;
            }
            $currentFragment .= (strlen($currentFragment) > 0 ? " " : "") . $word;
            $fragmentCount++;
        }
        if ($currentFragment !== "") {
            $fragments[] = [
                "recordID" => $recordID,
                "recordType" => $resourceType,
                "documentFragment" => $currentFragment,
            ];
            $documentIDs = [];
            foreach ($fragments as $fragmentArray) {
                $documentIDs[] = $this->insert($fragmentArray);
            }
            $this->vectorizeDocuments($documentIDs);
        }
        return $this->select(["recordID" => $recordID, "recordType" => $resourceType]);
    }

    /**
     * Being the process of vectorizing a document. This will likely be an async process.
     *
     * @param array $documentIDs
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function vectorizeDocuments(array $documentIDs): void
    {
        if (empty($documentIDs)) {
            return;
        }

        $vectorizationService = $this->vectorizationModel->getVectorizationService();
        if (!$vectorizationService || !$vectorizationService->isEnabled()) {
            ErrorLogger::error("Vectorization Service is not enabled: " . $vectorizationService->getModelName(), [
                "vectorization",
            ]);
            return;
        }

        $vectorizationService->vectorizeDocuments($documentIDs);
    }
}
