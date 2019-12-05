<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

/**
 * A class representing the polyfill file loaded by
 * @see WebpackAssetProvider::getInlinePolyfillContents();
 */
class PolyfillAsset extends SiteAsset {
    /**
     * @inheritdoc
     */
    public function getWebPath(): string {
        return $this->makeAssetPath('dist', 'polyfills.min.js');
    }

    /**
     * @inheritdoc
     */
    public function isStatic(): bool {
        return true;
    }
}
