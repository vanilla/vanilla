<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Theme;

 /**
  * HTML theme asset.
  */
class HtmlAsset extends Asset {

    /** @var string HTML content of this asset. */
    private $data = "";

    /** @var string Type of asset. */
    protected $type = "html";

    /**
     * Configure the HTML asset.
     *
     * @param string $data
     */
    public function __construct(string $data) {
        $this->data = $data;
    }

    /**
     * Represent the HTML asset as an array.
     *
     * @return array
     */
    public function asArray(): array {
        return [
            "data" => $this->data,
            "type" => $this->type,
        ];
    }

    /**
     * Get the HTML content.
     *
     * @return string
     */
    public function getData(): string {
        return $this->data;
    }

    /**
     * Return the HTML string content of the asset.
     *
     * @return string
     */
    public function __toString() {
        return $this->data;
    }
}
