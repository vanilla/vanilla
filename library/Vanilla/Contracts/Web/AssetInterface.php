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
     * Get the full web ready URL of the asset.
     *
     * @return string
     */
    public function getWebPath(): string;
}
