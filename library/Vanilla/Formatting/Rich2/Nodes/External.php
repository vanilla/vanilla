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
use Vanilla\Utility\HtmlUtils;

class External extends AbstractLeafNode
{
    const TYPE_RICH_EMBED_CARD = "rich_embed_card";
    const TYPE_RICH_EMBED_INLINE = "rich_embed_inline";

    private EmbedService $embedService;

    /**
     * @inheritdoc
     */
    public function __construct(array $data, string $parseMode = Parser::PARSE_MODE_NORMAL)
    {
        $this->embedService = \Gdn::getContainer()->get(EmbedService::class);
        parent::__construct($data, $parseMode);
    }

    /**
     * @inheritdoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) &&
            in_array($node["type"], [self::TYPE_RICH_EMBED_CARD, self::TYPE_RICH_EMBED_INLINE], true);
    }

    /**
     * @inheritdoc
     */
    public function renderHtmlContent(): string
    {
        if (isset($this->data["error"])) {
            $url = $this->getUrl();
            $attributes = HtmlUtils::attributes([
                "href" => $url,
                "rel" => "noopener noreferrer ugc",
            ]);
            return "<a $attributes>" . htmlspecialchars($url) . "</a>";
        }
        if ($this->parseMode === Parser::PARSE_MODE_QUOTE) {
            return $this->renderQuote();
        }
        return $this->getEmbed()->renderHtml();
    }

    /**
     * @inheritdoc
     */
    public function renderTextContent(): string
    {
        return $this->getUrl();
    }

    /**
     * @inheritdoc
     */
    public function getTextEnd(): string
    {
        return $this->data["type"] === self::TYPE_RICH_EMBED_CARD ? "\n" : "";
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
        if ($embedData["embedType"] ?? null === "iframe") {
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
     * Get the url for the embed.
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        $url = $this->data["embedData"]["url"] ?? ($this->data["url"] ?? null);
        if ($url) {
            $url = \Gdn_Format::sanitizeUrl($url);
        }
        return $url;
    }

    /**
     * Render the version of the embed if it is inside a quote embed.
     * E.g. A nested embed.
     *
     * @return string
     */
    public function renderQuote(): string
    {
        $url = $this->getUrl();
        if ($url) {
            $sanitizedUrl = htmlspecialchars($url);
            return "<p><a href=\"$sanitizedUrl\">$sanitizedUrl</a></p>";
        }
        return "";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultTypeName(): string
    {
        return self::TYPE_RICH_EMBED_CARD;
    }
}
