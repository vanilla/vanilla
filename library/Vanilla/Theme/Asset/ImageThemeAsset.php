<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\Asset;

use Vanilla\Web\MimeTypeDetector;

/**
 * Image theme asset.
 */
class ImageThemeAsset extends ThemeAsset
{
    /** @var string Type of asset. */
    protected $type = "image";

    /**
     * Configure the image asset.
     *
     * @param string $url Absolute URL to the image asset.
     */
    public function __construct(string $url)
    {
        $this->url = asset($url, true, true);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultType(): string
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string
    {
        $path = parse_url($this->getUrl(), PHP_URL_PATH);
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        $mimeType = MimeTypeDetector::getMimesForExtension($ext);
        return $mimeType[0];
    }

    /**
     * Represent the asset as an array.
     *
     * @return array
     */
    public function asArray(): array
    {
        return [
            "url" => $this->getValue(),
            "type" => $this->getDefaultType(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getValue()
    {
        return $this->url;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->url;
    }
}
