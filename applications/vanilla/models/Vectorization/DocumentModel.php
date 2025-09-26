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
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Models\VectorizeInterface;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\ModelFactory;
use Vanilla\Models\PipelineModel;
use vectorization\ElasticVectorizationService;
use vectorization\OpenAiVectorizationService;
use vectorization\VectorizationServiceInterface;

/**
 * Model for Document.
 */
class DocumentModel extends PipelineModel
{
    // Feature flag for document vectorization
    public const FEATURE_FLAG = "Feature.vectorizedSearch.Enabled";
    public const SEARCH_SERVICE_CONFIG_KEY = "Search.Vectorization.Model";
    public const FRAGMENT_SIZE_CONFIG_KEY = "Search.Vectorization.fragmentSize";
    public const VECTOR_SIZE = 1536;
    public const VECTORIZATION_MODEL_NAME = [
        OpenAiVectorizationService::MODEL_NAME => OpenAiVectorizationService::class,
        ElasticVectorizationService::MODEL_NAME => ElasticVectorizationService::class,
    ];

    /**
     * DI.
     *
     * @param ModelFactory $factory
     * @param ConfigurationInterface $config
     */
    public function __construct(private ModelFactory $factory, private ConfigurationInterface $config)
    {
        parent::__construct("document");
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"])->setUpdateFields(["dateInserted"]);
        $this->addPipelineProcessor($dateProcessor);

        $this->addPipelineProcessor(new JsonFieldProcessor(["documentVector"]));
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
     * Return keys of supported resource types.
     *
     * @param string $resourceType - Resource Type coming from API call.
     *
     * @return string|null
     */
    public function supportTextFragmentation(string $resourceType): string|null
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
     * Main call to start evaluating and generating text fragments.
     *
     * @param string $resourceType
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function processDocument(string $resourceType, array $data): array
    {
        $resourceType = $this->supportTextFragmentation($resourceType);
        $model = $resourceType !== null ? $this->factory->get($resourceType) : null;
        if ($model instanceof VectorizeInterface) {
            $vectorizationService = $this->getVectorizationService();
            $primaryID = $data[$resourceType . "ID"] ?? null;
            if ($primaryID !== null) {
                $data = $vectorizationService->preProcessing($resourceType, $data);
            } else {
                $result = [];
                foreach ($data as $row) {
                    $result = array_merge($result, $vectorizationService->preProcessing($resourceType, $row));
                }
                $data = array_values($result);
            }
        }

        return $data;
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

            if ($this->isEnabled()) {
                $this->vectorizeDocuments($documentIDs);
            }
        }
        return $this->select(["recordID" => $recordID, "recordType" => $resourceType]);
    }

    /**
     * Get the vectorization service.
     *
     * @return VectorizationServiceInterface
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function getVectorizationService(): VectorizationServiceInterface
    {
        $modelName = $this->config->get(self::SEARCH_SERVICE_CONFIG_KEY, OpenAiVectorizationService::MODEL_NAME);
        $services = array_keys(self::VECTORIZATION_MODEL_NAME);
        if (!in_array($modelName, $services)) {
            throw new Exception("Invalid Vectorization Service: " . $modelName);
        }

        $currentModel = Gdn::getContainer()->get(self::VECTORIZATION_MODEL_NAME[$modelName]);
        return $currentModel;
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

        $vectorizationService = $this->getVectorizationService();
        if (!$vectorizationService->isEnabled()) {
            ErrorLogger::error("Vectorization Service is not enabled: " . $vectorizationService->getModelName(), [
                "vectorization",
            ]);
            return;
        }

        $vectorizationService->vectorizeDocuments($documentIDs);
    }

    /**
     * Generate a vector out of a text.
     *
     * @param string $text
     * @return array
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function vectorizeText(string $text): array
    {
        $vectorizationService = $this->getVectorizationService();
        if (!$vectorizationService->isEnabled()) {
            throw new Exception("Vectorization Service is not enabled: " . $vectorizationService->getModelName());
        }

        $vector = $vectorizationService->vectorizeText($text);
        return $vector;
    }
}
