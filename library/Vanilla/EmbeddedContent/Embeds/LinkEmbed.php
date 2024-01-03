<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;

/**
 * Fallback scraped link embed.
 */
class LinkEmbed extends AbstractEmbed
{
    const TYPE = "link";

    /**
     * @inheritdoc
     */
    protected function getAllowedTypes(): array
    {
        return [self::TYPE];
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema
    {
        return Schema::parse(["body:s?", "photoUrl:s?"]);
    }

    /**
     * Override the parent method to render with InlineEmbed if embed style is inline.
     *
     * @return string
     */
    public function renderHtml(): string
    {
        $embedStyle = $this->data["embedStyle"] ?? null;

        // Inline Embed
        if ($embedStyle === self::EMBED_STYLE_INLINE) {
            $viewPath = dirname(__FILE__) . "/InlineEmbed.twig";
            return $this->renderTwig($viewPath, [
                "url" => $this->getUrl(),
                "data" => json_encode($this, JSON_UNESCAPED_UNICODE),
            ]);
        }

        // Plain link
        if ($embedStyle === self::EMBED_STYLE_PLAIN_LINK) {
            $viewPath = dirname(__FILE__) . "/PlainLink.twig";
            return $this->renderTwig($viewPath, [
                "url" => $this->getUrl(),
                "data" => json_encode($this, JSON_UNESCAPED_UNICODE),
            ]);
        }

        return parent::renderHtml();
    }
}
