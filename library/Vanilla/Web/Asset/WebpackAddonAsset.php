<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

use Garden\Web\RequestInterface;
use Vanilla\Contracts;

/**
 * An webpack asset that it specific to an addon.
 */
class WebpackAddonAsset extends WebpackAsset {
    /**
     * Constructor.
     *
     * @param RequestInterface $request The current request.
     * @param string $extension The file extension to use.
     * @param string $section The section of the site to get scripts for.
     * @see https://docs.vanillaforums.com/developer/tools/building-frontend/#site-sections
     * @param Contracts\AddonInterface $addon The addon to get an asset for.
     * @param string $cacheBustingKey A string for busting the cache.
     * @param bool $isCommonChunk Whether to check append common to the path.
     */
    public function __construct(
        RequestInterface $request,
        string $extension,
        string $section,
        Contracts\AddonInterface $addon,
        $cacheBustingKey = "",
        bool $isCommonChunk = false
    ) {
        $assetName = $addon->getKey();
        if ($isCommonChunk) {
            $assetName .= "-common";
        }
        parent::__construct(
            $request,
            $extension,
            $section,
            $assetName,
            $cacheBustingKey
        );
        $this->fileSubpath = $section . DS . 'addons';
        $this->webSubpath = $section . '/' . 'addons';
    }
}
