<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

/**
 * Represents a set of aggregate countable items.
 */
class AggregateCountOption implements \JsonSerializable
{
    /** @var string */
    private $table;

    /** @var class-string<AggregateCountableInterface> */
    private $modelClass;

    /** @var string */
    private $primaryField;

    /** @var string[] */
    private $aggregates;

    /**
     * The number of batches there are.
     * @var int|null
     */
    private $countBatches = null;

    /** @var int */
    private $currentAggregateIndex = 0;

    /** @var int */
    private $currentBatchIndex = 0;

    /**
     * DI.
     *
     * @param string $table
     * @param class-string<AggregateCountableInterface> $modelClass
     * @param string $primaryField
     * @param string[] $aggregates
     */
    public function __construct(string $table, string $modelClass, string $primaryField, array $aggregates)
    {
        $this->table = $table;
        $this->modelClass = $modelClass;
        $this->primaryField = $primaryField;
        $this->aggregates = $aggregates;
    }

    /**
     * Serialize to JSON.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            "table" => $this->table,
            "modelClass" => $this->modelClass,
            "primaryField" => $this->primaryField,
            "aggregates" => $this->aggregates,
            "countBatches" => $this->countBatches,
            "currentAggregateIndex" => $this->currentAggregateIndex,
            "currentBatchIndex" => $this->currentBatchIndex,
        ];
    }

    /**
     * Create an option from a JSON array.
     *
     * @param array $json
     *
     * @return AggregateCountOption
     */
    public static function fromJson(array $json): AggregateCountOption
    {
        $aggregate = new AggregateCountOption(
            $json["table"],
            $json["modelClass"],
            $json["primaryField"],
            $json["aggregates"]
        );
        $aggregate->countBatches = $json["countBatches"] ?? null;
        $aggregate->currentAggregateIndex = $json["currentAggregateIndex"] ?? 0;
        $aggregate->currentBatchIndex = $json["currentBatchIndex"] ?? 0;
        return $aggregate;
    }

    /**
     * Get the name of the current iteration.
     *
     * @return string
     */
    public function getIterationName()
    {
        return "{$this->getTable()}_{$this->getAggregates()[$this->getCurrentAggregateIndex()]}_{$this->getCurrentBatchIndex()}";
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * @return string
     */
    public function getPrimaryField(): string
    {
        return $this->primaryField;
    }

    /**
     * @return string[]
     */
    public function getAggregates(): array
    {
        return $this->aggregates;
    }

    /**
     * @return int|null
     */
    public function getCountBatches(): ?int
    {
        return $this->countBatches;
    }

    /**
     * @return int
     */
    public function getCurrentAggregateIndex(): int
    {
        return $this->currentAggregateIndex;
    }

    /**
     * @return int
     */
    public function getCurrentBatchIndex(): int
    {
        return $this->currentBatchIndex;
    }

    /**
     * @param int|null $countBatches
     */
    public function setCountBatches(?int $countBatches): void
    {
        $this->countBatches = $countBatches;
    }

    /**
     * @param int $currentBatchIndex
     */
    public function setCurrentBatchIndex(int $currentBatchIndex): void
    {
        $this->currentBatchIndex = $currentBatchIndex;
    }

    /**
     * @param int $currentAggregateIndex
     */
    public function setCurrentAggregateIndex(int $currentAggregateIndex): void
    {
        $this->currentAggregateIndex = $currentAggregateIndex;
    }

    /**
     * @param string[] $aggregates
     */
    public function setAggregates(array $aggregates): void
    {
        $this->aggregates = $aggregates;
    }
}
