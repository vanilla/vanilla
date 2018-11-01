<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Assets;

abstract class AbstractAsset {

    /** @var \Gdn_Request */
    protected $request;

    /** @var DeploymentCacheBuster */
    protected $cacheBuster;

    /**
     * AbstractAsset constructor.
     *
     * @param \Gdn_Request $request
     * @param DeploymentCacheBuster $cacheBuster
     */
    public function __construct(\Gdn_Request $request, DeploymentCacheBuster $cacheBuster) {
        $this->request = $request;
        $this->cacheBuster = $cacheBuster;
    }

    /**
     * Get the full web ready URL of the asset.
     *
     * @return string
     */
    abstract public function getWebPath(): string;

    /**
     * Utility function for calculating a full asset URL w/the domain of site, and the asset root.
     *
     * @param string ...$pieces The pieces of the web path.
     * @return string The full web path.
     */
    protected function makeAssetPath(string ...$pieces): string {
        return AbstractAsset::joinWebPath(
            $this->request->urlDomain(),
            $this->request->assetRoot(),
            ...$pieces
        );
    }

    /**
     * Utility for joining together path pieces with a `/` (such as a web url).
     *
     * @param string ...$pieces The pieces of the url.
     * @return string A joined version of the pieces with no duplicate `/`s
     */
    public static function joinWebPath(string ...$pieces): string {
        $path = "";
        foreach ($pieces as $piece) {
            if ($piece !== '') {
                $path .= trim($piece, '/') . '/';
            }
        }

        return rtrim($path, '/');
    }

    /**
     * A utility for joining together path pieces with a platform agnostic directory separate.
     *
     * Prevents duplicate separators.
     *
     * @param string ...$pieces The pieces of the path to join together.
     * @return string A joined version of the pieces with no duplicate separators.
     */
    public static function joinFilePath(string ...$pieces): string {
        $path = "";
        foreach ($pieces as $piece) {
            $path .= DS . trim($piece, DS);
        }

        return $path;
    }
}
