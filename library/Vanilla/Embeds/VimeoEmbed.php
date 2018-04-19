<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Embeds;

/**
 * Vimeo embed.
 */
class VimeoEmbed extends AbstractEmbed {

    const DEFAULT_HEIGHT = 270;

    const DEFAULT_WIDTH = 640;

    /** @inheritdoc */
    protected $type = 'vimeo';

    /** @inheritdoc */
    protected $domains = ['vimeo.com'];

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = null;

        if ($this->isNetworkEnabled()) {
            $oembed = $this->oembed("https://vimeo.com/api/oembed.json?url=" . urlencode($url));
            if ($oembed) {
                $oembed = $this->normalizeOembed($oembed);
                $data = $oembed;
            }
        }

        preg_match(
            '/https?:\/\/(?:www\.)?vimeo\.com\/(?:channels\/[a-z0-9]+\/)?(?<videoID>\d+)/i',
            $url,
            $matches
        );
        if (array_key_exists('videoID', $matches)) {
            $data = $data ?: [];
            if (!array_key_exists('attributes', $data)) {
                $data['attributes'] = [];
            }
            $data['attributes']['videoID'] = $matches['videoID'];
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $attributes = $data['attributes'] ?? [];
        $videoID = $attributes['videoID'] ?? null;
        $height = $data['height'] ?? self::DEFAULT_HEIGHT;
        $width = $data['width'] ?? self::DEFAULT_WIDTH;

        $videoIDEncoded = htmlspecialchars($videoID);
        $heightEncoded = htmlspecialchars($height);
        $widthEncoded = htmlspecialchars($width);

        $result = <<<HTML
<iframe src="https://player.vimeo.com/video/{$videoIDEncoded}" width="{$widthEncoded}" height="{$heightEncoded}" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
HTML;
        return $result;
    }
}
