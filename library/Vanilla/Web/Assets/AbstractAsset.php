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

    /** @var CacheBusterInterface */
    protected $cacheBuster;

    /**
     * AbstractAsset constructor.
     *
     * @param \Gdn_Request $request
     * @param CacheBusterInterface $cacheBuster
     */
    public function __construct(\Gdn_Request $request, CacheBusterInterface $cacheBuster) {
        $this->request = $request;
        $this->cacheBuster = $cacheBuster;
    }

    abstract public function getWebPath(): string;

    protected function makeAssetPath(string ...$pieces): string {
        return AbstractAsset::joinWebPath(
            $this->request->urlDomain(),
            $this->request->assetRoot(),
            ...$pieces
        );
    }

    public static function joinWebPath(string ...$pieces): string {
        $path = "";
        foreach ($pieces as $piece) {
            if ($piece !== '') {
                $path .= trim($piece, '/') . '/';
            }
        }

        return rtrim($path, '/');
    }

    public static function joinFilePath(string ...$pieces): string {
        $path = "";
        foreach ($pieces as $piece) {
            $path .= DS . trim($piece, DS);
        }

        return $path;
    }
}
