<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

use Exception;

/**
 * Class for parsing wistia URLs, fetching wistia OEmbed data, and rendering server side HTML for a wistia video embed.
 */
class WistiaEmbed extends VideoEmbed {

    const DEFAULT_HEIGHT = 270;

    const DEFAULT_WIDTH = 640;

    /** @inheritdoc */
    protected $type = 'wistia';

    /** @inheritdoc */
    protected $domains = ['wistia.com', 'wi.st'];

    public function __construct() {
        parent::__construct('wistia', 'video');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = null;
        preg_match(
            '/https?:\/\/(.+)?(wistia\.com|wi\.st)\/(medias|embed)\/(?<postID>.*)/i',
            $url,
            $post
        );
        if (!$post['postID']) {
            throw new Exception("Unable to find video", 400);
        }

        if ($this->isNetworkEnabled()) {
            if (array_key_exists('postID', $post)) {
                $oembed = $this->oembed("http://fast.wistia.com/oembed.json?url=" . urlencode($url));
                if ($oembed) {
                    $oembed = $this->normalizeOembed($oembed);
                    $data = $oembed;
                }
            }
        }
        $data = $data ?: [];
        if (!array_key_exists('attributes', $data)) {
            $data['attributes'] = [];
        }
        $data['attributes']['postID'] = $post['postID'];
        $data['attributes']['embedUrl'] = "https://fast.wistia.net/embed/iframe/" . $data['attributes']['postID'];
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $attributes = $data['attributes'] ?? [];
        $embedUrl = $attributes['embedUrl'] ?? '';
        $height = filter_var($data['height'], FILTER_VALIDATE_INT) ? $data['height'] : self::DEFAULT_HEIGHT;
        $width = filter_var($data['width'], FILTER_VALIDATE_INT) ? $data['width'] : self::DEFAULT_WIDTH;
        $name = $data['name'] ?? '';
        $photoURL = $data['photoUrl'] ?? '';

        $result = $this->videoCode($embedUrl, $name, $photoURL, $width, $height);
        return $result;
    }
}
