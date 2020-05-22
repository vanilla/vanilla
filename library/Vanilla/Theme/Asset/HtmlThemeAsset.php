<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Asset;

 use Vanilla\Theme\ThemeAssetFactory;

 /**
  * HTML theme asset.
  */
class HtmlThemeAsset extends ThemeAsset {

    /** @var string HTML content of this asset. */
    private $html = "";

    /** @var string Type of asset. */
    protected $type = "html";

    /**
     * Configure the HTML asset.
     *
     * @param string $html
     * @param string $url
     */
    public function __construct(string $html, string $url) {
        $this->html = $html;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getDefaultType(): string {
        return ThemeAssetFactory::ASSET_TYPE_HTML;
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string {
        return "text/html";
    }

    /**
     * Render the template as HTML.
     *
     * @param array $data The data to render with.
     *
     * @return string
     */
    public function renderHtml(array $data = []): string {
        return $this->html;
    }

    /**
     * Get the HTML content.
     *
     * @return string
     */
    public function getValue(): string {
        return $this->html;
    }

    /**
     * Return the HTML string content of the asset.
     *
     * @return string
     */
    public function __toString(): string {
        return $this->html;
    }
}
