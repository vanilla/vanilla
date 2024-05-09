<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Web;

/**
 * Base implementation for {@link AssetInterface}
 */
trait AssetTrait
{
    /**
     * Get the url of the asset.
     *
     * @return string
     */
    abstract public function getWebPath(): string;

    /**
     * @return bool
     */
    public function isScript(): bool
    {
        return str_ends_with($this->getWebPath(), ".js");
    }

    /**
     * @return bool
     */
    public function isStyleSheet(): bool
    {
        return str_ends_with($this->getWebPath(), ".css");
    }

    /**
     * @return string
     */
    public function isScriptModule(): string
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return false;
    }
}
