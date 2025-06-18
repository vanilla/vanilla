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
     * Apply pre-processing to the data before vectorization.
     *
     * @param string $resourceType
     * @param array $row
     * @return array
     */
    public function preProcessing(string $resourceType, array $row): array;
}
