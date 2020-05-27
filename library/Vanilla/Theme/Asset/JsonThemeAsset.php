<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Asset;

use Garden\Web\Exception\ClientException;
use Vanilla\Theme\ThemeAssetFactory;

 /**
  * JSON theme asset.
  */
class JsonThemeAsset extends ThemeAsset {

    /** @var string JSON content of this asset. */
    protected $jsonString;

    /** @var array */
    protected $data;

    /** @var null|\Exception */
    protected $error = null;

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
            $this->error = new ClientException('Error decoding JSON', 400, ['description' => json_last_error_msg()]);
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
    public function validate(): void {
        if ($this->error) {
            throw $this->error;
        }
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
