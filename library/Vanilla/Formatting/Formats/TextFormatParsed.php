<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatParsedInterface;

/**
 * We don't actually store any intermediary stages for this.
 * It's not necessary due to how fast the format is.
 */
class TextFormatParsed implements FormatParsedInterface, \Stringable
{
    private string $formatKey;

    private string $rawText;

    /**
     * DI.
     *
     * @param string $formatKey
     * @param string $rawText
     */
    public function __construct(string $formatKey, string $rawText)
    {
        $this->formatKey = $formatKey;
        $this->rawText = $rawText;
    }

    /**
     * @inheritdoc
     */
    public function getFormatKey(): string
    {
        return $this->formatKey;
    }

    /**
     * @return string
     */
    public function getRawText(): string
    {
        return $this->rawText;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getRawText();
    }
}
