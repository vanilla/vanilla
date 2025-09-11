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
    const TYPE_KEY = "a";

    const TYPE_LINK_AS_BUTTON = "link_as_button";

    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        $role = null;
        $class = null;
        if ($this->data["type"] === self::TYPE_LINK_AS_BUTTON) {
            $role = "button";
            $class = $this->data["buttonType"] && $this->data["buttonType"] === "primary" ? "Button Primary" : "Button";
        }
        $attributes = HtmlUtils::attributes([
            "href" => $this->getUrl(),
            "target" => $this->data["target"] ?? null,
            "rel" => "nofollow noopener ugc",
            "role" => $role,
            "class" => $class,
        ]);

        return "<a $attributes>";
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "</a>";
    }

    public function getUrl(): ?string
    {
        $url = $this->data["url"] ?? null;
        if (isset($url)) {
            $url = \Gdn_Format::sanitizeUrl($this->data["url"]);
        }
        return $url;
    }

    public function setUrl(string $url)
    {
        $this->data["url"] = $url;
    }

    /**
     * @inheritdoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) &&
            in_array($node["type"], [self::TYPE_KEY, self::TYPE_LINK_AS_BUTTON], true) &&
            !isset($node["error"]);
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultTypeName(): string
    {
        return self::TYPE_KEY;
    }
}
