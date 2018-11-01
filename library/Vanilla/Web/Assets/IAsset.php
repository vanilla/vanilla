<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Assets;

interface IAsset {
    /**
     * Get the full web ready URL of the asset.
     *
     * @return string
     */
    public function getWebPath(): string;
}
