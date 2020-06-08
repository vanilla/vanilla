<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Asset;

use Garden\Web\Data;
use Nette\Neon\Neon;
use Vanilla\Theme\ThemeAssetFactory;

/**
 * JSON theme asset.
 */
class NeonThemeAsset extends JsonThemeAsset {

    /** @var string */
    protected $neonString;

    /**
     * Make sure the error isn't included when serializing.
     */
    public function __sleep() {
        return ['jsonString', 'data', 'neonString'];
    }

    /**
     * Configure the JSON asset.
     *
     * @param string $data
     * @param string $url
     */
    public function __construct($data, string $url) {
        $this->url = $url;
        $this->neonString = $data;
        try {
            $this->data = Neon::decode($data);
            $this->jsonString = json_encode($this->fixEmptyArraysToObjects($this->data));
        } catch (\Exception $e) {
            // It's a bad asset.
            // Replace the asset with some json containing the error message.
            $this->data = [
                "error" => "Error decoding NE-ON",
                "message" => $e->getMessage(),
            ];
            $this->error = $e;
            $this->jsonString = json_encode($this->data);
        }
        $this->ensureArray();
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string {
        return "text/neon";
    }

    /**
     * @inheritdoc
     */
    public function getAllowedTypes(): array {
        return [ThemeAssetFactory::ASSET_TYPE_JSON, ThemeAssetFactory::ASSET_TYPE_NEON];
    }

    /**
     * Render the asset.
     *
     * @param string $asType The type to render the asset as.
     *
     * @return Data
     */
    public function render(string $asType = null): Data {
        switch ($asType) {
            case ThemeAssetFactory::ASSET_TYPE_NEON:
                $data = new Data($this->neonString);
                $data->setHeader('Content-Type', $this->getContentType());
                return $data;
            case ThemeAssetFactory::ASSET_TYPE_JSON:
            default:
                $data = new Data($this->jsonString);
                $data->setHeader('Content-Type', parent::getContentType());
                return $data;
        }
    }
}
