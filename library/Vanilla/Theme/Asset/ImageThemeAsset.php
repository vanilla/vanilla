<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Asset;

 use Mimey\MimeTypes;

 /**
  * Image theme asset.
  */
class ImageThemeAsset extends ThemeAsset {

    /** @var string Type of asset. */
    protected $type = "image";

    /**
     * Configure the image asset.
     *
     * @param string $url Absolute URL to the image asset.
     */
    public function __construct(string $url) {
        $this->url = asset($url, true, true);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultType(): string {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string {
        $mimeTypes = new MimeTypes();
        $path = parse_url($mimeTypes, PHP_URL_PATH);
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return $mimeTypes->getMimeType($ext);
    }

    /**
     * Represent the asset as an array.
     *
     * @return array
     */
    public function asArray(): array {
        return [
            'url' => $this->getValue(),
            'type' => $this->getDefaultType(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getValue() {
        return $this->url;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string {
        return $this->url;
    }
}
