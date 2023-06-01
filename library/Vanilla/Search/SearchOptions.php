<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

/**
 * Options for a search. Includes information like pagination.
 */
class SearchOptions
{
    const DEFAULT_LIMIT = 10;

    /** @var int */
    private $offset;

    /** @var int */
    private $limit;

    private bool $skipConversion;

    /**
     * Constructor.
     *
     * @param int $offset
     * @param int $limit
     * @param bool $skipConversion
     */
    public function __construct(int $offset = 0, int $limit = self::DEFAULT_LIMIT, bool $skipConversion = false)
    {
        $this->offset = $offset;
        $this->limit = $limit;
        $this->skipConversion = $skipConversion;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Whether we should skip AbstractSearchDriver::convertRecordsToResultItems().
     *
     * @return bool
     */
    public function skipConversion(): bool
    {
        return $this->skipConversion;
    }
}
