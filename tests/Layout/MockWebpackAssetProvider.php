<?php
/**
 * @copyright 2009-2012 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace VanillaTests\Layout;

use Garden\Web\RequestInterface;
use Gdn;
use Vanilla\Web\Asset\HotBuildAsset;
use Vanilla\Web\Asset\LocaleAsset;
use Vanilla\Web\Asset\WebpackAsset;
use Vanilla\Web\Asset\WebpackAssetDefinition;
use Vanilla\Web\Asset\WebpackAssetProvider;

/**
 * Mock Class to provide assets from the webpack build process.
 */
class MockWebpackAssetProvider extends WebpackAssetProvider
{
    /**
     * Get script assets built from webpack using the in-repo build process.
     *
     * These follow a pretty strict pattern of:
     *
     * - webpack runtime
     * - vendor chunk
     * - library chunk
     * - addon chunks
     * - bootstrap
     *
     * @param string $section - The section of the site to lookup.
     * @param bool $includeAsync - use Async param
     * @return WebpackAsset[] The assets files for all webpack scripts.
     */
    public function getScripts(string $section, bool $includeAsync = false): array
    {
        $scripts = $this->createAssets("js");
        return $scripts;
    }

    /**
     * Get all stylesheets for a particular site section.
     *
     * @param string $section
     * @param bool $includeAsync Include async assets.
     *
     * @return WebpackAsset[]
     */
    public function getStylesheets(string $section, bool $includeAsync = false): array
    {
        $styles = $this->createAssets("css");
        return $styles;
    }

    /**
     * Get webpack script assets based on the definitions.
     *
     * @param string $type "css" or "js"
     *
     * @return WebpackAsset[]
     */
    public function createAssets(string $type): array
    {
        $assets = [];
        $arrays = [
            [
                "assetPath" => "LeaderboardWidget." . $type,
                "assetType" => $type,
                "section" => "widgets",
                "addonKey" => "widget",
            ],
            [
                "assetPath" => "BreadcrumbsWidget." . $type,
                "assetType" => $type,
                "section" => "widgets",
                "addonKey" => "widget",
            ],
            [
                "assetPath" => "DiscussionWidget." . $type,
                "assetType" => $type,
                "section" => "widgets",
                "addonKey" => "widget",
            ],
            [
                "assetPath" => "GroupWidget." . $type,
                "assetType" => $type,
                "section" => "widgets",
                "addonKey" => "widget",
            ],
            [
                "assetPath" => "CommentWidget." . $type,
                "assetType" => $type,
                "section" => "widgets",
                "addonKey" => "widget",
            ],
        ];
        $request = Gdn::getContainer()->get(RequestInterface::class);
        foreach ($arrays as $array) {
            $assets[] = (new WebpackAssetDefinition(
                $array["assetPath"],
                $array["assetType"],
                $array["section"],
                $array["addonKey"]
            ))->asAsset($request);
        }
        return $assets;
    }
}
