<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatParsedInterface;
use Vanilla\Formatting\Quill\BlotGroupCollection;

/**
 * Store intermediary parsed content for the rich format.
 */
class RichFormatParsed implements FormatParsedInterface
{
    private string $rawContent;

    private BlotGroupCollection $blotGroups;

    /**
     * DI.
     *
     * @param string $rawContent
     * @param BlotGroupCollection $blotGroups
     */
    public function __construct(string $rawContent, BlotGroupCollection $blotGroups)
    {
        $this->rawContent = $rawContent;
        $this->blotGroups = $blotGroups;
    }

    /**
     * @inheritdoc
     */
    public function getFormatKey(): string
    {
        return RichFormat::FORMAT_KEY;
    }

    /**
     * @return string
     */
    public function getRawContent(): string
    {
        return $this->rawContent;
    }

    /**
     * @return BlotGroupCollection
     */
    public function getBlotGroups(): BlotGroupCollection
    {
        return $this->blotGroups;
    }
}
