<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

use Garden\Http\HttpRequest;

/**
 * Generic image embed.
 */
class ImageEmbed extends Embed {

    /** Valid image extensions. */
    const IMAGE_EXTENSIONS = ['bmp', 'gif', 'jpeg', 'jpg', 'png', 'svg', 'tif', 'tiff'];

    /**
     * ImageEmbed constructor.
     */
    public function __construct() {
        parent::__construct('image', 'image');
    }

    /**
     * @inheritdoc
     */
    public function canHandle(string $domain, string $url = null): bool {
        $result = $this->isImageUrl($url);
        return $result;
    }

    /**
     * Is this an image URL?
     *
     * @param string $url Target URL.
     * @return bool
     */
    private function isImageUrl(string $url): bool {
        $result = false;

        // Attempt to determine if this looks like an image URL.
        $path = parse_url($url, PHP_URL_PATH);
        if ($path !== false) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $result = $extension && in_array(strtolower($extension), self::IMAGE_EXTENSIONS);
        }

        // It's possible this is an extension-less image. Try a HEAD request to get the content type.
        if ($result === false && $this->isNetworkEnabled()) {
            $head = $this->httpRequest($url, '', [], HttpRequest::METHOD_HEAD);
            if ($head->getStatusCode() === 200) {
                $contentType = $head->getHeaderLines('content-type');
                $contentType = reset($contentType);
                if ($contentType && substr($contentType, 0, 6) === 'image/') {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $height = null;
        $width = null;

        if ($this->isNetworkEnabled()) {
            // Make sure the URL is valid.
            $urlParts = parse_url($url);
            if ($urlParts === false || !in_array(val('scheme', $urlParts), $this->getUrlSchemes())) {
                throw new Exception('Invalid URL.', 400);
            }

            $result = getimagesize($url);
            if (is_array($result) && count($result) >= 2) {
                list($width, $height) = $result;
            }
        }

        $data = [
            'photoUrl' => $url,
            'width' => $width,
            'height' => $height
        ];
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $source = $data['url'] ?? null;
        $sourceEncoded = htmlspecialchars($source);
        $sanitizedHref = \Gdn_Format::sanitizeUrl($sourceEncoded);

        // Yes we actually want target blank on these, even if we don't want it on normal links.
        $result = <<<HTML
<div class="embedExternal embedImage">
    <div class="embedExternal-content">
        <a class="embedImage-link" href="{$sanitizedHref}" rel="nofollow noopener" target="_blank">
            <img class="embedImage-img" src="{$sourceEncoded}">
        </a>
    </div>
</div>
HTML;
        return $result;
    }
}
