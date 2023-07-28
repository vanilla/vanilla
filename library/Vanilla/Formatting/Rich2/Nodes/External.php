<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\Embeds\LinkEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Rich2\Parser;

class External extends AbstractLeafNode
{
    const TYPE_RICH_EMBED_CARD = "rich_embed_card";
    const TYPE_RICH_EMBED_INLINE = "rich_embed_inline";

    private EmbedService $embedService;

    /**
     * @inheritDoc
     */
    public function __construct(array $data, string $parseMode = Parser::PARSE_MODE_NORMAL)
    {
        $this->embedService = \Gdn::getContainer()->get(EmbedService::class);
        parent::__construct($data, $parseMode);
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) &&
            in_array($node["type"], [self::TYPE_RICH_EMBED_CARD, self::TYPE_RICH_EMBED_INLINE], true) &&
            !isset($node["error"]);
    }

    /**
     * @inheritDoc
     */
    public function renderHtmlContent(): string
    {
        if ($this->parseMode === Parser::PARSE_MODE_QUOTE) {
            return $this->renderQuote();
        }
        return $this->getEmbed()->renderHtml();
    }

    public function renderTextContent(): string
    {
        return "";
    }

    /**
     * Get the array representation of the embed.
     *
     * @return array
     */
    public function getEmbedData(): array
    {
        $embedData = $this->data["embedData"] ?? [];
        $additionalData = [];
        if ($embedData["embedType"] === "iframe") {
            $this->parseMode = Parser::PARSE_MODE_EXTENDED;
            $additionalData = $this->data["frameAttributes"] ?? [];
        }

        // If `Garden.Format.DisableUrlEmbeds` is `true`, change the embed's data to ensure it gets rendered as a simple link.
        if (c("Garden.Format.DisableUrlEmbeds")) {
            $embedData["embedType"] = "link";
            $embedData["embedStyle"] = LinkEmbed::EMBED_STYLE_PLAIN_LINK;
        }

        return array_merge($embedData, $additionalData);
    }

    /**
     * Get embed object represented by the data.
     *
     * @return AbstractEmbed
     */
    public function getEmbed(): AbstractEmbed
    {
        $data = $this->getEmbedData();
        $data["embedStyle"] = $data["embedStyle"] ?? ($this->data["type"] ?? null);
        return $this->embedService->createEmbedFromData($data, $this->parseMode === Parser::PARSE_MODE_EXTENDED);
    }

    /**
     * Set embed object using its data.
     *
     * @param AbstractEmbed $embed
     * @return void
     */
    public function setEmbed(AbstractEmbed $embed)
    {
        $this->data["embedData"] = $embed->getData();
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

    /**
     * @inheritDoc
     */
    public static function getDefaultTypeName(): string
    {
        return self::TYPE_RICH_EMBED_CARD;
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedChildClasses(): array
    {
        return [Text::class];
    }
}
