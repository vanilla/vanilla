<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Asset;

use Vanilla\Theme\ThemeAssetFactory;

 /**
  * JSON theme asset.
  */
class JsonThemeAsset extends ThemeAsset {

    /** @var string JSON content of this asset. */
    protected $jsonString;

    /** @var array */
    protected $data;

    /**
     * Configure the JSON asset.
     *
     * @param string $data
     * @param string $url
     */
    public function __construct(string $data, string $url) {
        $this->url = $url;
        $decoded = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // It's a bad asset.
            // Replace the asset with some json containing the error message.
            $this->data = [
                "error" => "Error decoding JSON",
                "message" => json_last_error_msg(),
            ];
            $this->jsonString = json_encode($this->data);
        } else {
            $this->jsonString = $data;
            $this->data = $decoded;
        }
        $this->ensureArray();
    }

    /**
     * The JSON asset must be an array.
     */
    protected function ensureArray() {
        if (!is_array($this->data)) {
            $this->data = [ 'value' => $this->data ];
        }
    }

    /**
     * @inheritdoc
     */
    public function getDefaultType(): string {
        return ThemeAssetFactory::ASSET_TYPE_JSON;
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string {
        return "application/json";
    }

    /**
     * @inheritdoc
     */
    public function getValue(): array {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string {
        return $this->jsonString;
    }

    /**
     * Pull a value out of the json.
     *
     * @param string $key A key in dot notation.
     * @param mixed $default The default value.
     * @return mixed
     */
    public function get(string $key, $default) {
        return valr($key, $this->data, $default);
    }
}
