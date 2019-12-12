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
 * Class representing an asset from the current site.
 */
abstract class SiteAsset implements Contracts\Web\AssetInterface {

    /** @var RequestInterface */
    protected $request;

    /** @var string */
    protected $cacheBusterKey = '';

    /**
     * SiteAsset constructor.
     *
     * @param RequestInterface $request The current request.
     * @param string $cacheBusterKey A cache busting string.
     */
    public function __construct(RequestInterface $request, $cacheBusterKey = "") {
        $this->request = $request;
        $this->cacheBusterKey = $cacheBusterKey;
    }

    /**
     * @inheritdoc
     */
    abstract public function getWebPath(): string;

    /**
     * Utility function for calculating a relative asset URL
     *
     * @param string[] $pieces The pieces of the web path.
     * @return string The relative web path.
     */
    protected function makeAssetPath(string ...$pieces): string {
        $path = self::joinWebPath(
            $this->request->urlDomain(),
            $this->request->getAssetRoot(),
            ...$pieces
        );

        return $this->addCacheBuster($path);
    }

    /**
     * Add a cache busting query parameter if one is available.
     *
     * @param string $url The URL to modify.
     *
     * @return string The new URL.
     */
    private function addCacheBuster(string $url): string {
        if ($this->cacheBusterKey !== "") {
            $url .= "?h=" . $this->cacheBusterKey;
        }
        return $url;
    }

    /**
     * Utility for joining together path pieces with a `/` (such as a web url).
     *
     * @param string[] $pieces The pieces of the url.
     * @return string A joined version of the pieces with no duplicate `/`s
     */
    public static function joinWebPath(string ...$pieces): string {
        return self::joinPieces('/', ...$pieces);
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
        return self::joinPieces(DS, ...$pieces);
    }

    /**
     * Join together an array of items with a string joiner. Normalize the spaces between the joiners to prevent
     * duplicates.
     *
     * @param string $joiner The item to join with.
     * @param string[] $pieces The pieces to join.
     *
     * @return string The normalized path.
     */
    private static function joinPieces(string $joiner, string ...$pieces): string {
        $path = "";
        foreach ($pieces as $index => $piece) {
            if ($piece !== '') {
                $trimmedPiece = $index === 0 ? rtrim($piece, $joiner) : trim($piece, $joiner);
                $path .= $trimmedPiece . $joiner;
            }
        }

        return rtrim($path, $joiner);
    }

    /**
     * The default behaviour is to be non-static
     *
     * @inheritDoc
     *
     * @return bool
     */
    public function isStatic(): bool {
        return false;
    }
}
