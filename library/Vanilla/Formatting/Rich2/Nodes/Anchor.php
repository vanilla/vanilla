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

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        $attributes = HtmlUtils::attributes([
            "href" => $this->getUrl(),
            "target" => $this->data["target"] ?? null,
        ]);
        return "<a $attributes>";
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public static function getDefaultTypeName(): string
    {
        return self::TYPE_KEY;
    }
}
