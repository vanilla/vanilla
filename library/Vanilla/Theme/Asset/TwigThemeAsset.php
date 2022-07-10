<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Asset;

use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Vanilla\Theme\ThemeAssetFactory;
use Vanilla\Web\TwigRenderTrait;

 /**
  * HTML theme asset.
  */
class TwigThemeAsset extends HtmlThemeAsset {

    use TwigRenderTrait;

    /** @var string Twig Template content of this asset. */
    private $template = "";

    /** @var string Type of asset. */
    protected $type = "html";

    /**
     * Configure the HTML asset.
     *
     * @param string $template
     * @param string $url
     */
    public function __construct(string $template, string $url) {
        $this->template = $template;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getDefaultType(): string {
        return ThemeAssetFactory::ASSET_TYPE_TWIG;
    }

    /**
     * @inheritdoc
     */
    public function getAllowedTypes(): array {
        return [ThemeAssetFactory::ASSET_TYPE_TWIG, ThemeAssetFactory::ASSET_TYPE_HTML];
    }

    /**
     * Represent the HTML asset as an array.
     *
     * @return array
     */
    public function asArray(): array {
        $result = [
            "url" => $this->url,
            "type" => $this->type,
        ];

        if ($this->includeValueInJson) {
            $result += [
                "template" => $this->template,
                "data" => $this->renderHtml([]),
            ];
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function render(string $asType = null): Data {
        switch ($asType) {
            case ThemeAssetFactory::ASSET_TYPE_TWIG:
                return parent::render($asType);
            default:
                return new Data($this->renderHtml());
        }
    }

    /**
     * @inheritdoc
     */
    public function validate(): void {
        $this->renderHtml();
    }

    /**
     * Render the template as HTML.
     *
     * @param array $data The data to render with.
     *
     * @return string
     */
    public function renderHtml(array $data = []): string {
        try {
            return $this->renderTwigFromString($this->getTemplate(), $data);
        } catch (\Exception $e) {
            return "<p>" . formatException($e) . "</p>";
        }
    }

    /**
     * Get the HTML content.
     *
     * @return string
     */
    public function getTemplate(): string {
        return $this->template;
    }

    /**
     * Return the HTML string content of the asset.
     *
     * @return string
     */
    public function __toString(): string {
        return $this->getTemplate();
    }
}
