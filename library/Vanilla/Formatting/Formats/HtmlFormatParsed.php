<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatParsedInterface;
use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Holds raw and parsed format data for one of the HTML based formats.
 */
class HtmlFormatParsed implements FormatParsedInterface
{
    /** @var string */
    private string $formatKey;

    /** @var string */
    private string $rawHtml;

    /** @var string */
    private string $processedHtml;

    /**
     * DI.
     *
     * @param string $formatKey
     * @param string $rawHtml
     * @param string $processedHtml
     */
    public function __construct(string $formatKey, string $rawHtml, string $processedHtml)
    {
        $this->formatKey = $formatKey;
        $this->rawHtml = $rawHtml;
        $this->processedHtml = $processedHtml;
    }

    public function getFormatKey(): string
    {
        return $this->formatKey;
    }

    /**
     * @return string
     */
    public function getRawHtml(): string
    {
        return $this->rawHtml;
    }

    /**
     * @return string
     */
    public function getProcessedHtml(): string
    {
        return $this->processedHtml;
    }
}
