<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Utility\HtmlUtils;

class Heading extends AbstractNode
{
    const TYPE_H2 = "h2";
    const TYPE_H3 = "h3";
    const TYPE_H4 = "h4";
    const TYPE_H5 = "h5";

    /**
     * @inheritDoc
     */
    protected function getHtmlStart(): string
    {
        $level = $this->getLevel();
        $ref = $this->getRef();
        $attributes = HtmlUtils::attributes([
            "data-id" => $ref !== "" ? $ref : null,
        ]);
        return "<h$level $attributes>";
    }

    /**
     * @inheritDoc
     */
    protected function getHtmlEnd(): string
    {
        $level = $this->getLevel();
        return "</h$level>";
    }

    /**
     * @inheritDoc
     */
    public static function matches(array $node): bool
    {
        return isset($node["type"]) &&
            in_array($node["type"], [self::TYPE_H2, self::TYPE_H3, self::TYPE_H4, self::TYPE_H5]);
    }

    /**
     * Get heading level (2 - 5)
     *
     * @return int
     */
    public function getLevel(): int
    {
        return (int) substr($this->data["type"], 1);
    }

    /**
     * Get heading ref
     *
     * @return string
     */
    public function getRef(): string
    {
        return $this->data["ref"] ?? "";
    }

    /**
     * Set heading ref
     *
     * @param string $ref
     * @return void
     */
    public function setRef(string $ref)
    {
        $this->data["ref"] = $ref;
    }

    /**
     * Check if this heading has a ref property.
     *
     * @return bool
     */
    public function hasRef(): bool
    {
        return isset($this->data["ref"]);
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultTypeName(): string
    {
        return self::TYPE_H2;
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedChildClasses(): array
    {
        return [Text::class, Anchor::class, Mention::class, External::class];
    }
}
