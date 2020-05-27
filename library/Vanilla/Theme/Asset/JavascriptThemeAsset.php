<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Asset;

use Vanilla\Theme\ThemeAssetFactory;

/**
 * Style theme asset.
 */
class JavascriptThemeAsset extends ThemeAsset {

    /** @var string Javascript content of this asset. */
    private $data;

    /**
     * Configure the JSON asset.
     *
     * @param string $data
     * @param string $url
     */
    public function __construct(string $data, string $url) {
        $this->data = $data;
        $this->url = $url;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultType(): string {
        return ThemeAssetFactory::ASSET_TYPE_JS;
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string {
        return "application/javascript";
    }

    /**
     * @inheritdoc
     */
    public function getValue() {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string {
        return $this->data;
    }
}
