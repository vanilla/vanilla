<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Assets;

class PolyfillAsset extends AbstractAsset {
    public function getWebPath(): string {
        return $this->makeAssetPath(
            'js',
            'webpack',
            'polyfills.min.js' . '?h='.$this->cacheBuster->value()
        );
    }
}
