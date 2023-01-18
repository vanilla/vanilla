<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Rich2\NodeList;
use Vanilla\Formatting\Rich2\Parser;

class External extends AbstractNode
{
    private EmbedService $embedService;

    /**
     * @inheritDoc
     */
    public function __construct(array $data, NodeList $children, string $parseMode = Parser::PARSE_MODE_NORMAL)
    {
        $this->embedService = \Gdn::getContainer()->get(EmbedService::class);
        parent::__construct($data, $children, $parseMode);
    }

    /**
     * @inheritDoc
     */
    public function getFormatString(): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) && $node["type"] === "rich_embed_card";
    }
    /**
     * @inheritDoc
     */
    public function render(): string
    {
        if ($this->parseMode === Parser::PARSE_MODE_QUOTE) {
            return $this->renderQuote();
        }
        return $this->getEmbed()->renderHtml();
    }

    /**
     * Get embed object represented by the data.
     *
     * @return AbstractEmbed
     */
    public function getEmbed(): AbstractEmbed
    {
        $data = $this->data["embedData"] ?? [];
        return $this->embedService->createEmbedFromData($data, $this->parseMode === Parser::PARSE_MODE_EXTENDED);
    }

    /**
     * Render the version of the embed if it is inside a quote embed.
     * E.g. A nested embed.
     *
     * @return string
     */
    public function renderQuote(): string
    {
        $url = $this->data["embedData"]["url"] ?? ($this->data["url"] ?? null);
        if ($url) {
            $sanitizedUrl = htmlspecialchars(\Gdn_Format::sanitizeUrl($url));
            return "<p><a href=\"$sanitizedUrl\">$sanitizedUrl</a></p>";
        }
        return "";
    }
}
