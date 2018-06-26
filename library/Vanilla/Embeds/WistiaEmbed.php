<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Embeds;

use Exception;

/**
 * Wistia embed.
 */
class WistiaEmbed extends VideoEmbed {

    const DEFAULT_HEIGHT = 270;

    const DEFAULT_WIDTH = 640;

    /** @inheritdoc */
    protected $type = 'wistia';

    /** @inheritdoc */
    protected $domains = ['wistia.com', 'wi.st'];

    /**
     * VimeoEmbed constructor.
     */
    public function __construct() {
        parent::__construct('wistia', 'video');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = null;

        if ($this->isNetworkEnabled()) {
            preg_match(
                '/https?:\/\/(.+)?(wistia\.com|wi\.st)\/(medias|embed)\/(?<postID>.*)/i',
                $url,
                $post
            );

            if (array_key_exists('postID', $post)) {
                $oembed = $this->oembed("http://fast.wistia.com/oembed.json?url=" . urlencode($url));
                if ($oembed) {
                    $oembed = $this->normalizeOembed($oembed);
                    $data = $oembed;
                }
                $data = $data ?: [];
                if (!array_key_exists('attributes', $data)) {
                    $data['attributes'] = [];
                }
                $data['attributes']['postID'] = $post['postID'];
                $data['attributes']['embedUrl'] = "https://fast.wistia.net/embed/iframe/" . $data['attributes']['postID'];
            } else {
                throw new Exception("Unable to find video", 400);
            }
        }
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $attributes = $data['attributes'] ?? [];
        $embedUrl = $attributes['embedUrl'] ?? '';
        $convertedHeight = (int)$data['height'];
        $convertedWidth = (int)$data['width'];
        $height = $convertedHeight ?? self::DEFAULT_HEIGHT;
        $width = $convertedWidth ?? self::DEFAULT_WIDTH;
        $name = $data['name'] ?? '';
        $photoURL = $data['photoUrl'] ?? '';

        $result = $this->videoCode($embedUrl, $name, $photoURL, $width, $height);
        return $result;
    }
}
