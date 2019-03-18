<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Theme;

 /**
  * JSON theme asset.
  */
class JsonAsset extends Asset {

    /** @var string JSON content of this asset. */
    private $data;

    /** @var string Type of asset. */
    protected $type = "json";

    /**
     * Configure the JSON asset.
     *
     * @param string $data
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Represent the JSON asset as an array.
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
     * Get the JSON content.
     *
     * @return string
     */
    public function getData(): string {
        return $this->data;
    }
}
