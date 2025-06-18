<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\JsInterpop;

use Garden\Web\Data;
use JsonSerializable;

/**
 * Class for preloading data into react-query.
 */
class PreloadedQuery implements JsonSerializable
{
    /**
     * @param array $queryKey
     * @param Data $data
     */
    public function __construct(public array $queryKey, public Data $data)
    {
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [$this->queryKey, $this->data];
    }
}
