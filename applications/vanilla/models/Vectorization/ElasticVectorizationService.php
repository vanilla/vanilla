<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace vectorization;

use Gdn;

/**
 * Vectorization service that uses ElasticSearch.
 */
class ElasticVectorizationService implements VectorizationServiceInterface
{
    const ELASTIC_SEARCH_ENTERPRISE = "Feature.elasticSearchEnterprise.Enabled";
    const ELASTIC_INDEX_VECTOR_FIELD = "elasticVector";
    const MODEL_NAME = "Elastic";

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return Gdn::config()->get(self::ELASTIC_SEARCH_ENTERPRISE);
    }

    /**
     * @param array $documentIDs
     * @inheritdoc
     */
    public function vectorizeDocuments(array $documentIDs): void
    {
        // Do nothing. We will rely on models in Elastic Search to do the vectorization.
    }

    /**
     * @inheritdoc
     */
    public function vectorizeText(string $text): array
    {
        // Do nothing. We will rely on models in Elastic Search to do the vectorization.
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getModelName(): string
    {
        return self::MODEL_NAME;
    }

    /**
     * Set the elasticVector field to be processed.
     *
     * @param string $resourceType
     * @param array $row
     * @return array
     */
    public function preProcessing(string $resourceType, array $row): array
    {
        $row[ElasticVectorizationService::ELASTIC_INDEX_VECTOR_FIELD] = $row["bodyPlainText"];
        return [$row];
    }
}
