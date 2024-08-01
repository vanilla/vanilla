<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 * Represents a search result item.
 */
class SearchTypeaheadResult implements \JsonSerializable
{
    /**
     * @param string $phrase
     */
    public function __construct(public string $phrase)
    {
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            "recordType" => "typeahead",
            "type" => "typeahead",
            "name" => $this->phrase,
        ];
    }
}
