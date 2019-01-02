<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

use InvalidArgumentException;

/**
 * YouTube embed.
 */
class YouTubeEmbed extends VideoEmbed {

    const DEFAULT_HEIGHT = 270;

    const DEFAULT_WIDTH = 480;

    /** @inheritdoc */
    protected $domains = ['youtube.com', 'youtube.ca', 'youtu.be'];

    /**
     * YouTubeEmbed constructor.
     */
    public function __construct() {
        parent::__construct('youtube', 'video');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        // Get info from the URL.
        $path = parse_url($url, PHP_URL_PATH);
        $query = [];
        $queryString = parse_url($url, PHP_URL_QUERY);
        if ($queryString) {
            parse_str($queryString, $query);
        }
        $fragment = parse_url($url, PHP_URL_FRAGMENT);

        $videoID = preg_match('/^\/?(?P<videoID>[\w-]{11})$/', $path, $pathParts) ? $pathParts['videoID'] : $query['v'] ?? null;

        // Figure out the start time.
        $start = null;
        if (preg_match('/t=(?P<start>\d+)/', $fragment, $timeParts)) {
            $start = $timeParts['start'];
        } elseif (array_key_exists('t', $query) && preg_match('/^(?:(?P<ticks>\d+)|(?:(?P<minutes>\d*)m)?(?:(?P<seconds>\d*)s)?)$/', $query['t'], $timeParts)) {
            if (array_key_exists('ticks', $timeParts) && $timeParts['ticks'] !== '') {
                $start = $timeParts['ticks'];
            } else {
                $minutes = $timeParts['minutes'] ? (int)$timeParts['minutes'] : 0;
                $seconds = $timeParts['seconds'] ? (int)$timeParts['seconds'] : 0;
                $start = ($minutes * 60) + $seconds;
            }
        }

        if ($this->isNetworkEnabled()) {
            $oembed = $this->oembed("https://www.youtube.com/oembed?url=https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3D{$videoID}");
            if (!empty($oembed)) {
                $data = $this->normalizeOembed($oembed);
            }
        }

        $data = $data ?? [];
        $attributes = $data['attributes'] ?? [];
        if ($videoID) {
            $attributes['videoID'] = $videoID;
        }
        if ($start) {
            $attributes['start'] = $start;
        }
        if (array_key_exists('listID', $query)) {
            $attributes['listID'] = $query['listID'];
        }
        if (array_key_exists('rel', $query)) {
            $attributes['rel'] = (bool)$query['rel'];
        }
        $attributes['embedUrl'] = $this->embedUrl($attributes);
        $data['attributes'] = $attributes;

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $attributes = $data['attributes'] ?? [];
        $height = filter_var($data['height'], FILTER_VALIDATE_INT) ? $data['height'] : self::DEFAULT_HEIGHT;
        $width = filter_var($data['width'], FILTER_VALIDATE_INT) ? $data['width'] : self::DEFAULT_WIDTH;
        $name = $data['name'] ?? '';
        $videoID = $attributes['videoID'] ?? null;
        $embedUrl = $attributes['embedUrl'] ?? $this->embedUrl($attributes);
        $photoUrl = "https://img.youtube.com/vi/{$videoID}/0.jpg";

        return $this->videoCode($embedUrl, $name, $photoUrl, $width, $height);
    }

    private function embedUrl(array $attributes) {
        $listID = $attributes['listID'] ?? null;
        $start = $attributes['start'] ?? null;
        $videoID = $attributes['videoID'] ?? null;
        $rel = $attributes['rel'] ?? null;

        if ($listID !== null) {
            if ($videoID !== null) {
                return "https://www.youtube.com/embed/{$videoID}?list={$listID}";
            } else {
                return "https://www.youtube.com/embed/videoseries?list={$listID}";
            }
        } elseif ($videoID !== null) {
            $params = "feature=oembed&autoplay=1";
            // Show related videos?
            if ($rel !== null) {
                $params .= '&rel='.(int)$rel;
            }
            // Seek to start time.
            if ($start) {
                $params .= "&start={$start}";
            }

            return "https://www.youtube.com/embed/{$videoID}?{$params}";
        } else {
            throw new InvalidArgumentException('Unable to generate YouTube markup.');
        }
    }
}
