<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Asset;

 use Garden\Web\Data;
 use Garden\Web\Exception\ClientException;

 /**
  * Basic theme asset.
  */
abstract class ThemeAsset implements \JsonSerializable {

    protected $includeValueInJson = true;

    /** @var string */
    protected $url = '';

    /**
     * Get the type of the asset.
     *
     * @return string
     */
    abstract public function getDefaultType(): string;

    /**
     * Get the HTTP content-type of the asset.
     *
     * @return string
     */
    abstract public function getContentType(): string;

    /**
     * Get the value of the asset.
     *
     * This may be an array, string, boolean, etc.
     *
     * @return mixed
     */
    abstract public function getValue();

    /**
     * Validate the asset contents.
     *
     * @return void
     *
     * @throws ClientException If the asset is invalid.
     */
    public function validate(): void {
        return;
    }

    /**
     * Get the value of the asset.
     *
     * This may be an array, string, boolean, etc.
     *
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * Serialize the asset value to a string.
     *
     * @return string
     */
    abstract public function __toString(): string;

    /**
     * @param bool $includeValueInJson
     */
    public function setIncludeValueInJson(bool $includeValueInJson): void {
        $this->includeValueInJson = $includeValueInJson;
    }

    /**
     * Get all allowed types for the asset.
     *
     * @return array
     */
    public function getAllowedTypes(): array {
        return [$this->getDefaultType()];
    }

    /**
     * Render the asset.
     *
     * @param string $asType The type to render the asset as.
     *
     * @return Data
     */
    public function render(string $asType = null): Data {
        $result = new Data($this->__toString());
        $result->setHeader('Content-Type', $this->getContentType());
        return $result;
    }

    /**
     * Represent the asset as an array.
     *
     * @return array
     */
    public function asArray(): array {
        $result = [
            'url' => $this->getUrl(),
            'type' => $this->getDefaultType(),
            'content-type' => $this->getContentType(),
        ];

        if ($this->includeValueInJson) {
            $result['data'] = $this->getValue();
        }
        return $result;
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize() {
        return $this->asArray();
    }
}
