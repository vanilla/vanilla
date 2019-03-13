<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Theme;

 /**
  * Image theme asset.
  */
class ImageAsset extends Asset {

    /** @var string Absolute URL to the image. */
    private $url;

    /** @var string Type of asset. */
    protected $type = "image";

    /**
     * Configure the image asset.
     *
     * @param string $url Absolute URL to the image asset.
     */
    public function __construct(string $url) {
        $this->url = $url;
    }

    /**
     * Represent the image asset as an array.
     *
     * @return array
     */
    public function asArray(): array {
        return [
            "type" => $this->type,
            "url" => $this->url,
        ];
    }

    /**
     * Get the absolute image URL.
     *
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }
}
