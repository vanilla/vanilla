<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Web;

/**
 * A web asset interface.
 */
interface AssetInterface {
    /**
     * It tells if the asset is static or not
     * Static means that the asset is inmutable during the build's lifecycle
     * The value can be used for Caching purposes
     *
     * @return bool
     */
    public function isStatic(): bool;

    /**
     * Get the full web ready URL of the asset.
     *
     * @return string
     */
    public function getWebPath(): string;
}
