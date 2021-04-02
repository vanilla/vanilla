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
class CssThemeAsset extends ThemeAsset {

    /** @var string CSS content of this asset. */
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
        return ThemeAssetFactory::ASSET_TYPE_CSS;
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string {
        return "text/css";
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
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
