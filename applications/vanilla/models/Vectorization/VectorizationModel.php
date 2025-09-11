<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard;

use Exception;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Container\Reference;
use Gdn;
use Vanilla\Contracts\Models\VectorizeInterface;
use Vanilla\Models\ModelFactory;
use vectorization\OpenAiVectorizationService;
use vectorization\VectorizationServiceInterface;

/**
 * Model to handle the vectorization of documents for search.
 */
class VectorizationModel
{
    const FEATURE_FLAG = "Feature.vectorizedSearch.Enabled";
    const SEARCH_VECTORIZATION_MODEL = "Search.Vectorization.Model";
    private array $vectorizationModels = [];

    /**
     * DI.
     *
     * @param ModelFactory $factory
     */
    public function __construct(private ModelFactory $factory)
    {
    }

    /**
     * Check if Vectorized Search is enabled.
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
    public function getSupportedResourceType(string $resourceType): string|null
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
     * Apply the `vectorize` expand.
     *
     * @param string $resourceType
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function applyExpand(string $resourceType, array $data): array
    {
        $resourceType = $this->getSupportedResourceType($resourceType);
        $model = $resourceType !== null ? $this->factory->get($resourceType) : null;
        if ($model instanceof VectorizeInterface) {
            $vectorizationService = $this->getVectorizationService();
            $primaryID = $data[$resourceType . "ID"] ?? null;
            if ($primaryID !== null) {
                $data = $vectorizationService->joinVectorizedData($resourceType, $data);
            } else {
                $result = [];
                foreach ($data as $row) {
                    $result = array_merge($result, $vectorizationService->joinVectorizedData($resourceType, $row));
                }
                $data = array_values($result);
            }
        }

        return $data;
    }

    /**
     * Get the vectorization service.
     *
     * @return VectorizationServiceInterface|false
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function getVectorizationService(): VectorizationServiceInterface|false
    {
        $model = false;
        $modelName = Gdn::config(self::SEARCH_VECTORIZATION_MODEL, "Elastic");

        if ($modelName) {
            $model = Gdn::getContainer()->get($this->vectorizationModels[$modelName]);
        }

        return $model;
    }

    /**
     * Register a Vectorization model.
     *
     * @param VectorizationServiceInterface $model
     * @return void
     */
    public function addVectorizationModel(VectorizationServiceInterface $model): void
    {
        $this->vectorizationModels[$model->getModelName()] = $model::class;
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
