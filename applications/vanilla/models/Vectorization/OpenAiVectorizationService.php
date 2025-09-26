<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace vectorization;

use Exception;
use Generator;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\DocumentModel;
use Vanilla\Logging\ErrorLogger;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;

/**
 * A service to generate sparse vectors.
 */
class OpenAiVectorizationService implements SystemCallableInterface, VectorizationServiceInterface
{
    const MODEL_NAME = "OpenAI";
    const VECTOR_LENGTH_CONFIG = "SearchVectorization.Dimensions";
    public const VECTOR_SIZE = 1536;

    /**
     * Vectorize the document using OpenAI.
     *
     * @param ConfigurationInterface $config
     * @param OpenAIClient $openAIClient
     * @param LongRunner $longRunner
     * @param DocumentModel $documentModel
     */
    public function __construct(
        private ConfigurationInterface $config,
        private OpenAIClient $openAIClient,
        private LongRunner $longRunner,
        private DocumentModel $documentModel
    ) {
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["generateVector"];
    }

    /**
     * Check if OpenAI is configured to generate the vectors generation.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->get("Feature.aiFeatures.Enabled");
    }

    /**
     * Start the process to vectorize the document by calling longRunner.
     *
     * @param array $documentIDs
     * @return void
     */
    public function vectorizeDocuments(array $documentIDs): void
    {
        $action = new LongRunnerAction(self::class, "generateVector", [$documentIDs]);
        $this->longRunner->runDeferred($action);
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
        try {
            $remainingDocumentIDs = $documentIDs;
            foreach ($documentIDs as $documentID) {
                // $documentID Must be integer.
                if (is_int($documentID)) {
                    array_shift($remainingDocumentIDs);
                    $document = $this->documentModel->selectSingle(["documentID" => $documentID]);
                    $textChunk = $document["documentFragment"];
                    $vector = $this->openAIClient->embeds(OpenAIClient::MODEL_GPTTEXTEMBED, $textChunk, $dimensions);
                    $this->documentModel->update(["documentVector" => $vector], ["documentID" => $documentID]);
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
                ["OpenAI", "vectorization"],
                [
                    "exception" => $e->getMessage(),
                ]
            );
        }

        return LongRunner::FINISHED;
    }

    /**
     * @inheritdoc
     */
    public function vectorizeText(string $text): array
    {
        $dimensions = $this->config->get(self::VECTOR_LENGTH_CONFIG, 1536);
        return $this->openAIClient->embeds(OpenAIClient::MODEL_GPTTEXTEMBED, $text, $dimensions);
    }

    /**
     * @inheritdoc
     */
    public function getModelName(): string
    {
        return self::MODEL_NAME;
    }

    /**
     * Separate the document fragments into multiple fragments.
     *
     * TODO: separate the fragmentation from the vectorization.
     *
     * @param string $resourceType
     * @param array $row
     * @return array
     * @throws Exception
     */
    public function preProcessing(string $resourceType, array $row): array
    {
        return $this->documentModel->getTextFragments($resourceType, $row);
    }
}
