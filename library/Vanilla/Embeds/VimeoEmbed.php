<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Embeds;

/**
 * Vimeo embed.
 */
class VimeoEmbed extends VideoEmbed {

    const DEFAULT_HEIGHT = 270;

    const DEFAULT_WIDTH = 640;

    /** @inheritdoc */
    protected $type = 'vimeo';

    /** @inheritdoc */
    protected $domains = ['vimeo.com'];

    /**
     * VimeoEmbed constructor.
     */
    public function __construct() {
        parent::__construct('vimeo', 'video');
    }

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
        $name = $data['name'] ?? '';
        $photoURL = $data['photoUrl'] ?? '';
        $embedUrl = "https://player.vimeo.com/video/{$videoID}?autoplay=1";

        $result = $this->videoCode($embedUrl, $name, $photoURL, $width, $height);
        return $result;
    }
}
