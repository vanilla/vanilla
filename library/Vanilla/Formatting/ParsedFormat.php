<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

/**
 * Class to hold an intermediary parsed format to speed up post formatting.
 */
abstract class ParsedFormat
{
    /** @var string */
    private string $formatKey;

    /**
     * DI.
     *
     * @param string $formatKey
     */
    public function __construct(string $formatKey)
    {
        $this->formatKey = $formatKey;
    }

    public function getFormatKey(): string
    {
        return $this->formatKey;
    }
}
