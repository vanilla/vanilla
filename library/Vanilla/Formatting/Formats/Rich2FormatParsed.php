<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatParsedInterface;
use Vanilla\Formatting\Rich2\NodeList;

/**
 * Holds raw and parsed node list data.
 */
class Rich2FormatParsed implements FormatParsedInterface
{
    private string $rawContent;

    private NodeList $nodeList;

    /**
     * DI.
     *
     * @param string $rawContent
     * @param NodeList $nodeList
     */
    public function __construct(string $rawContent, NodeList $nodeList)
    {
        $this->rawContent = $rawContent;
        $this->nodeList = $nodeList;
    }

    public function getFormatKey(): string
    {
        return Rich2Format::FORMAT_KEY;
    }

    /**
     * @return string
     */
    public function getRawContent(): string
    {
        return $this->rawContent;
    }

    /**
     * @return NodeList
     */
    public function getNodeList(): NodeList
    {
        return $this->nodeList;
    }
}
