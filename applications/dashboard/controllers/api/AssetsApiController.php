<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Web\Data;
use Vanilla\Web\Asset\ViteAssetProvider;
use Vanilla\Web\CacheControlConstantsInterface;
use Vanilla\Web\Controller;

/**
 * API controller for data related to assets.
 */
class AssetsApiController extends Controller
{
    private ViteAssetProvider $assetProvider;

    /**
     * DI.
     *
     * @param ViteAssetProvider $assetProvider
     */
    public function __construct(ViteAssetProvider $assetProvider)
    {
        $this->assetProvider = $assetProvider;
    }

    /**
     * Get the current cache buster for the application.
     */
    public function get_embedScript()
    {
        $asset = $this->assetProvider->getEmbedAsset();

        if ($asset === null) {
            $jsContent = <<<JS
console.error("Vanilla embed script could not be generated. This is due to a build error in vanilla.");
JS;

            return new Data(
                $jsContent,
                [],
                [
                    "content-type" => "application/javascript",
                    CacheControlConstantsInterface::HEADER_CACHE_CONTROL =>
                        CacheControlConstantsInterface::PUBLIC_CACHE,
                    "Access-Control-Allow-Origin" => "*",
                ]
            );
        }

        return new Data(
            "",
            [],
            [
                CacheControlConstantsInterface::HEADER_CACHE_CONTROL => CacheControlConstantsInterface::PUBLIC_CACHE,
                "Access-Control-Allow-Origin" => "*",
                "Location" => $asset->getWebPath(),
            ]
        );
    }
}
