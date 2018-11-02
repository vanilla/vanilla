<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

use Garden\Web\RequestInterface;
use Vanilla\Contracts;

/**
 * Class representing an asset from the current site.
 */
abstract class SiteAsset implements Contracts\Web\AssetInterface {

    /** @var RequestInterface */
    protected $request;

    /** @var Contracts\Web\CacheBusterInterface */
    protected $cacheBuster;

    /**
     * SiteAsset constructor.
     *
     * @param RequestInterface $request The current request.
     * @param Contracts\Web\CacheBusterInterface $cacheBuster A cache buster instance.
     */
    public function __construct(RequestInterface $request, Contracts\Web\CacheBusterInterface $cacheBuster) {
        $this->request = $request;
        $this->cacheBuster = $cacheBuster;
    }

    /**
     * @inheritdoc
     */
    abstract public function getWebPath(): string;

    /**
     * Utility function for calculating a full asset URL w/the domain of site, and the asset root.
     *
     * @param string[] $pieces The pieces of the web path.
     * @return string The full web path.
     */
    protected function makeAssetPath(string ...$pieces): string {
        return SiteAsset::joinWebPath(
            $this->request->urlDomain(),
            $this->request->getAssetRoot(),
            ...$pieces
        );
    }

    /**
     * Utility for joining together path pieces with a `/` (such as a web url).
     *
     * @param string[] $pieces The pieces of the url.
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
     * @param string[] $pieces The pieces of the path to join together.
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
