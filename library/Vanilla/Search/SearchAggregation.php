<?php

namespace Vanilla\Search;

/**
 * Aggregation options for search.
 */
abstract class SearchAggregation implements \JsonSerializable
{
    /**
     * @param string $name Name for this aggregation.
     * @param string $type The type of aggregation to perform.
     * @param string $field The field to aggregate on.
     * @param int|null $size Optional size.
     */
    public function __construct(
        public string $name,
        public string $type,
        public string $field,
        public ?int $size = null
    ) {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
