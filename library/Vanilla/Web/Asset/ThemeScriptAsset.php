<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

use Vanilla\Contracts\Web\AssetInterface;
use Vanilla\Theme\Asset\JavascriptThemeAsset;

/**
 * An asset representing a script containing data for a particular locale.
 */
class ThemeScriptAsset implements AssetInterface {

    /** @var JavascriptThemeAsset */
    private $asset;

    /**
     * Constructor.
     *
     * @param JavascriptThemeAsset $asset
     */
    public function __construct(JavascriptThemeAsset $asset) {
        $this->asset = $asset;
    }

    /**
     * @inheritdoc
     */
    public function getWebPath(): string {
        return $this->asset->getUrl();
    }

    /**
     * @inheritdoc
     */
    public function isStatic(): bool {
        return true;
    }
}
