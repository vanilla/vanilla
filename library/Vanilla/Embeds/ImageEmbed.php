<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Embeds;

/**
 * Generic image embed.
 */
class ImageEmbed extends AbstractEmbed {

    protected $type = 'image';

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

        $result = <<<HTML
<div class="embed-image embed embedImage">
    <img class="embedImage-img" src="{$sourceEncoded}">
</div>
HTML;
        return $result;
    }
}
