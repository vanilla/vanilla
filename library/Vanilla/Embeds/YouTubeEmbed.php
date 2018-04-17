<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Embeds;

/**
 * YouTube embed.
 */
class YouTubeEmbed extends AbstractEmbed {

    protected $type = 'youtube';

    /** @var string[] Valid domains for this embed type. */
    protected $domains = ['youtube.com', 'youtube.ca', 'youtu.be'];

    /**
     * Attempt to parse the contents of a URL for data relevant to this embed type.
     *
     * @param string $url Target URL.
     * @return array|null An array of data if successful. Otherwise, null.
     */
    public function matchUrl(string $url) {
        // Get info from the URL.
        preg_match(
            '/https?:\/\/(?:(?:www.)|(?:m.))?(?:(?:youtube.(ca|com))|(?:youtu.be))\/(?:(?:playlist?)|(?:(?:watch\?v=)?(?P<videoId>[\w-]{11})))(?:\?|\&)?(?:list=(?P<listId>[\w-]*))?(?:t=(?:(?P<minutes>\d*)m)?(?P<seconds>\d*)s)?(?:#t=(?P<start>\d*))?/i',
            $url,
            $urlParts
        );

        $videoID = array_key_exists('videoId', $urlParts) ? $urlParts['videoId'] : null;

        // Figure out the start time.
        $start = null;
        if (array_key_exists('start', $urlParts)) {
            $start = $urlParts['start'];
        } elseif (array_key_exists('minutes', $urlParts) || array_key_exists('seconds', $urlParts)) {
            $minutes = $urlParts['minutes'] ? intval($urlParts['minutes']) : 0;
            $seconds = $urlParts['seconds'] ? intval($urlParts['seconds']) : 0;
            $start = ($minutes * 60) + $seconds;
        }

        $oembed = $this->oembed("https://www.youtube.com/oembed?url=https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3D{$videoID}");
        if (!empty($oembed)) {
            $data = $this->normalizeOembed($oembed);
            $listID = $urlParts['listId'] ?? null;
            $attributes = $data['attributes'] ?? [];
            $attributes['videoID'] = $videoID;
            $attributes['listID'] = $listID ?: null;
            $attributes['start'] = $start;
            $data['attributes'] = $attributes;
        } else {
            $data = null;
        }

        return $data;
    }

    /**
     * Generate markup to render this embed.
     *
     * @param array $data Structured data for this embed type.
     * @return string Embed code.
     */
    public function renderContent(array $data): string {
        return '';
    }
}
