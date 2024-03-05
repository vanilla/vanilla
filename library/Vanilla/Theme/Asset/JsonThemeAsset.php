<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Asset;

use Garden\Web\Exception\ClientException;
use Vanilla\Theme\ThemeAssetFactory;
use Vanilla\Utility\ArrayUtils;
use function Vanilla\Web;

/**
 * JSON theme asset.
 */
class JsonThemeAsset extends ThemeAsset
{
    /** @var string JSON content of this asset. */
    protected $jsonString;

    /** @var array */
    protected $data;

    /** @var null|\Exception */
    protected $error = null;

    /**
     * Make sure the error isn't included when serializing.
     *
     * IF YOU HAVE TO CHANGE THIS DON'T FORGET NeonThemeAsset::__sleep().
     */
    public function __sleep()
    {
        return ["jsonString", "data"];
    }

    /**
     * Configure the JSON asset.
     *
     * @param string $data
     * @param string $url
     */
    public function __construct(string $data, string $url)
    {
        $this->url = $url;
        $decoded = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error = new ClientException("Error decoding JSON", 400, ["description" => json_last_error_msg()]);
            // It's a bad asset.
            // Replace the asset with some json containing the error message.
            $this->data = [
                "error" => "Error decoding JSON",
                "message" => json_last_error_msg(),
            ];
            $this->jsonString = json_encode($this->data);
        } else {
            $this->jsonString = $data;
            $this->data = $this->preservedOutputDecode($data);
            $this->ensureArray();
        }
    }

    /**
     * Get a variable.
     *
     * @param string $variableName
     * @param string $fallback
     *
     * @return string
     */
    public function variable(string $variableName, string $fallback): string
    {
        $value = $this->get($variableName, $fallback);
        if (is_array($value)) {
            return $fallback;
        } else {
            return $value;
        }
    }

    /**
     * Get a pixel value.
     *
     * @param string $variableName
     * @param int $fallbackPixels
     * @return int
     */
    public function pixelVariable(string $variableName, int $fallbackPixels): int
    {
        $value = $this->variable($variableName, $fallbackPixels);
        $value = str_replace("px", "", $value);
        $value = (int) $value;
        return $value;
    }

    /**
     * Get debug HTML for the asset.
     *
     * @return \Twig\Markup
     */
    public function debug(): \Twig\Markup
    {
        $vars = json_encode($this->preservedOutputDecode($this->jsonString), JSON_PRETTY_PRINT);
        $encodedVars = htmlspecialchars($vars);
        $html = "<pre style='max-width: 100%; overflow: auto; position: fixed; z-index: 10000; top: 40px; left: 40px; right: 40px; bottom: 40px; background: #fff; border: 3px #0c7cd5 solid;'>{$encodedVars}</pre>";
        return new \Twig\Markup($html, "utf-8");
    }

    /**
     * Render output in a way that tries to preserve arrays.
     *
     * @param string $jsonIn
     *
     * @return mixed
     */
    protected function preservedOutputDecode(string $jsonIn)
    {
        if (trim($jsonIn) === "[]") {
            return [];
        } else {
            $decoded = json_decode($jsonIn, true);
            return $this->fixEmptyArraysToObjects($decoded);
        }
    }

    /**
     * Make sure empty arrays are interpretted as empty objects.
     *
     * @param mixed $input
     * @return mixed
     */
    protected function fixEmptyArraysToObjects($input)
    {
        if (is_array($input) && empty($input)) {
            return new \stdClass();
        }

        if (is_iterable($input)) {
            foreach ($input as $key => &$value) {
                if (is_array($value)) {
                    setvalr($key, $input, $this->fixEmptyArraysToObjects($value));
                }
            }
        }

        return $input;
    }

    /**
     * The JSON asset must be an array.
     */
    protected function ensureArray()
    {
        if (!is_array($this->data) && !is_object($this->data)) {
            $this->data = ["value" => $this->data];
            $this->error = new ClientException("JSON asset must be an object or array.");
            $this->jsonString = json_encode($this->data);
        }
    }

    /**
     * @inheritdoc
     */
    public function getDefaultType(): string
    {
        return ThemeAssetFactory::ASSET_TYPE_JSON;
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string
    {
        return "application/json";
    }

    /**
     * @inheritdoc
     */
    public function getValue()
    {
        return json_decode($this->jsonString, true);
    }

    /**
     * Overridden so objects don't get coerced into arrays.
     *
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $result = [
            "url" => $this->getUrl(),
            "type" => $this->getDefaultType(),
            "content-type" => $this->getContentType(),
        ];

        if ($this->includeValueInJson) {
            $result["data"] = json_decode($this->jsonString);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function validate(): void
    {
        if ($this->error) {
            throw $this->error;
        }
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->jsonString;
    }

    /**
     * Pull a value out of the json.
     *
     * @param string $key A key in dot notation.
     * @param mixed $default The default value.
     * @return mixed
     */
    public function get(string $key, $default)
    {
        return valr($key, $this->data, $default);
    }

    /**
     * @return bool
     */
    public function canMerge(): bool
    {
        return true;
    }
}
