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
use Vanilla\Models\TrustedDomainModel;

class External extends AbstractLeafNode
{
    private EmbedService $embedService;
    private TrustedDomainModel $trustedDomainModel;

    /**
     * @inheritDoc
     */
    public function __construct(array $data, NodeList $children, string $parseMode = Parser::PARSE_MODE_NORMAL)
    {
        $this->embedService = \Gdn::getContainer()->get(EmbedService::class);
        $this->trustedDomainModel = \Gdn::getContainer()->get(TrustedDomainModel::class);
        parent::__construct($data, $children, $parseMode);
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) && in_array($node["type"], ["rich_embed_card", "rich_embed_inline"], true);
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
            // If the post is from outside knowledge,
            // We should check iframe src is trusted
            $isTrusted = $embedData["isKnowledge"] ?? false;
            $isTrustedDomain = $this->trustedDomainModel->isTrustedDomain($embedData["url"]);

            if ($isTrusted || $isTrustedDomain) {
                $this->parseMode = Parser::PARSE_MODE_EXTENDED;
            } else {
                $embedData = [];
            }

            $additionalData = $this->data["frameAttributes"] ?? [];
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
        $data["embedStyle"] = $this->data["type"] ?? null;
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
}
