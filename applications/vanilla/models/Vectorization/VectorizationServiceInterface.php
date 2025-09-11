<?php

namespace vectorization;

/**
 * Abstract interface for vectorization services.
 */
interface VectorizationServiceInterface
{
    /**
     * Check if the service is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Turn the document fragments into vectors.
     *
     * @param array $documentIDs
     * @return void
     */
    public function vectorizeDocuments(array $documentIDs): void;

    /**
     * Vectorize a text.
     *
     * @param string $text
     * @return array
     */
    public function vectorizeText(string $text): array;

    /**
     * Get this model's name.
     *
     * @return string
     */
    public function getModelName(): string;

    /**
     * Join the data that need to be ingested for Vectorized search.
     *
     * @param string $resourceType
     * @param array $row
     * @return array
     */
    public function joinVectorizedData(string $resourceType, array $row): array;
}
