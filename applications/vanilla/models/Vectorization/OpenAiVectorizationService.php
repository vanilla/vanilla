<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace vectorization;

use Exception;
use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\DocumentModel;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
/**
 * A service to generate sparse vectors.
 */
class OpenAiVectorizationService implements VectorizationServiceInterface
{
    const MODEL_NAME = "OpenAI";
    const VECTOR_LENGTH_CONFIG = "SearchVectorization.Dimensions";

    /**
     * Vectorize the document using OpenAI.
     *
     * @param ConfigurationInterface $config
     * @param OpenAIClient $openAIClient
     * @param LongRunner $longRunner
     */
    public function __construct(
        private ConfigurationInterface $config,
        private OpenAIClient $openAIClient,
        private LongRunner $longRunner
    ) {
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
        $action = new LongRunnerAction(DocumentModel::class, "generateVector", [$documentIDs]);
        $this->longRunner->runDeferred($action);
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
     * @param string $resourceType
     * @param array $row
     * @return array
     * @throws Exception
     */
    public function joinVectorizedData(string $resourceType, array $row): array
    {
        $documentModel = GDN::getContainer()->get(DocumentModel::class);
        return $documentModel->getTextFragments($resourceType, $row);
    }
}
