<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Embeds;

use InvalidArgumentException;

/**
 * YouTube embed.
 */
class YouTubeEmbed extends Embed {

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
        } elseif (array_key_exists('t', $query) && preg_match('/((?P<minutes>\d*)m)?((?P<seconds>\d*)s)?/', $query['t'], $timeParts)) {
            $minutes = $timeParts['minutes'] ? (int)$timeParts['minutes'] : 0;
            $seconds = $timeParts['seconds'] ? (int)$timeParts['seconds'] : 0;
            $start = ($minutes * 60) + $seconds;
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
        $data['attributes'] = $attributes;

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $height = $data['height'] ?? self::DEFAULT_HEIGHT;
        $width = $data['width'] ?? self::DEFAULT_WIDTH;

        $attributes = $data['attributes'] ?? [];
        $listID = $attributes['listID'] ?? null;
        $start = $attributes['start'] ?? null;
        $videoID = $attributes['videoID'] ?? null;
        $rel = $attributes['rel'] ?? null;

        $attrHeight = htmlspecialchars($height);
        $attrWidth = htmlspecialchars($width);

        if ($listID) {
            if ($videoID) {
                $embedUrl = "https://www.youtube.com/embed/{$videoID}?list={$listID}";
            } else {
                $embedUrl = "https://www.youtube.com/embed/videoseries?list={$listID}";
            }

            $attrEmbedUrl = htmlspecialchars($embedUrl);

            $result = <<<HTML
<iframe width="{$attrWidth}" height="{$attrHeight}" src="{$attrEmbedUrl}" frameborder="0" allowfullscreen></iframe>
HTML;
        } elseif ($videoID) {
            $data = "{$videoID}?autoplay=1";
            $embedUrl = "https://www.youtube.com/watch?v={$videoID}";
            $imageUrl = "https://img.youtube.com/vi/{$videoID}/0.jpg";

            // Show related videos?
            if ($rel !== null) {
                $data .= '&rel='.(int)$rel;
            }
            // Seek to start time.
            if ($start) {
                $data .= "&start={$start}";
                $embedUrl .= "#t={$start}";
            }

            $attrData = htmlspecialchars($data);
            $attrEmbedUrl = htmlspecialchars($embedUrl);
            $attrImageUrl = htmlspecialchars($imageUrl);

            $result = <<<HTML
<span class="VideoWrap">
    <span class="Video YouTube" data-youtube="youtube-{$attrData}">
        <span class="VideoPreview">
            <a href="{$attrEmbedUrl}">
                <img src="{$attrImageUrl}" width="{$attrWidth}" height="{$attrHeight}" border="0" />
            </a>
        </span>
        <span class="VideoPlayer"></span>
    </span>
</span>
HTML;
        } else {
            throw new InvalidArgumentException('Unable to generate YouTube markup.');
        }

        return $result;
    }
}
