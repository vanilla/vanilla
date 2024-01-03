<?php

namespace Vanilla\Search;

/**
 * Aggregation options for search.
 */
abstract class SearchAggregation implements \JsonSerializable
{
    protected string $name;

    protected string $type;

    protected string $field;

    protected int $size;

    /**
     * @param string $name Name for this aggregation.
     * @param string $type The type of aggregation to perform.
     * @param string $field The field to aggregate on.
     * @param int|null $size Optional size.
     */
    public function __construct(string $name, string $type, string $field, ?int $size = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->field = $field;
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
