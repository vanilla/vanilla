<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Utility\HtmlUtils;

class Anchor extends AbstractNode
{
    /**
     * @inheritDoc
     */
    public function getFormatString(): string
    {
        $attributes = HtmlUtils::attributes([
            "href" => $this->getUrl(),
            "target" => $this->data["target"] ?? null,
        ]);
        return "<a $attributes>%s</a>";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) && $node["type"] === "a";
    }

    public function getUrl(): ?string
    {
        return $this->data["url"] ?? null;
    }

    public function setUrl(string $url)
    {
        $this->data["url"] = $url;
    }
}
