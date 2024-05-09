<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Web\Data;
use Vanilla\Web\Asset\DeploymentCacheBuster;
use Vanilla\Web\Asset\HotBuildAsset;
use Vanilla\Web\Asset\WebpackAsset;
use Vanilla\Web\Asset\WebpackAssetProvider;
use Vanilla\Web\CacheControlMiddleware;
use Vanilla\Web\Controller;

/**
 * API controller for data related to assets.
 */
class AssetsApiController extends Controller
{
    /** @var WebpackAssetProvider */
    private $assetProvider;

    /**
     * DI.
     *
     * @param WebpackAssetProvider $assetProvider
     */
    public function __construct(WebpackAssetProvider $assetProvider)
    {
        $this->assetProvider = $assetProvider;
    }

    /**
     * Get the current cache buster for the application.
     */
    public function get_embedScript(\Gdn_Request $request)
    {
        if ($this->assetProvider->isHotReloadEnabled()) {
            $asset = new HotBuildAsset("embed");
        } else {
            $asset = $this->assetProvider->getEmbedAsset();
        }

        return new Data(
            "",
            [],
            [
                CacheControlMiddleware::HEADER_CACHE_CONTROL => CacheControlMiddleware::PUBLIC_CACHE,
                "Access-Control-Allow-Origin" => "*",
                "Location" => $asset->getWebPath(),
            ]
        );
    }
}
