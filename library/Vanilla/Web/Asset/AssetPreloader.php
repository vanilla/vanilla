<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Asset;

use Vanilla\Contracts\Web\AssetInterface;

/**
 * Class for holder data of a preload.
 */
class AssetPreloader {
    // `as` parameter values
    const AS_SCRIPT = "script";
    const AS_STYLE = "style";
    const AS_FONT = "font";
    const AS_IMAGE = "image";

    // `rel` parameter values.
    const REL_FULL = "full-file-include"; // Add it as an actual script/style. More than a preload. Use sparingly.
    const REL_PRELOAD = "preload";
    const REL_PREFETCH = "prefetch";

    /** @var AssetInterface */
    private $asset;

    /** @var string */
    private $as;

    /** @var string */
    private $rel;

    /**
     * Constructor method.
     *
     * @param AssetInterface $asset The asset to preload.
     * @param string $rel
     * @param string $as
     */
    public function __construct(AssetInterface $asset, string $rel, string $as) {
        $this->asset = $asset;
        $this->as = $as;
        $this->rel = $rel;
    }

    /**
     * @return AssetInterface
     */
    public function getAsset(): AssetInterface {
        return $this->asset;
    }

    /**
     * @return string
     */
    public function getAs(): string {
        return $this->as;
    }

    /**
     * @return string
     */
    public function getRel(): string {
        return $this->rel;
    }
}
