<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

use Exception;

/**
 * Class for parsing twitch URLs, fetching twitch OEmbed data, and rendering server side HTML for a twitch video embed
 */
class TwitchEmbed extends VideoEmbed {

    const DEFAULT_HEIGHT = 300;

    const DEFAULT_WIDTH = 400;

    /** @inheritdoc */
    protected $domains = ['www.twitch.tv', 'clips.twitch.tv'];

    /** @var string */
    public $urlType;

    public function __construct()
    {
        parent::__construct('twitch', 'video');
        $this->urlType = '';
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = [];
        $oembedData =[];
        $videoID = $this->getUrlInformation($url);

        if (!$videoID) {
            throw new Exception('Unable to find Twitch Post', 400);
        }

        if ($this->isNetworkEnabled()) {
            $oembedData = $this->oembed("https://api.twitch.tv/v4/oembed?url=" . urlencode($url));
            if (!empty($oembedData)) {
                $data = $this->normalizeOembed($oembedData);
            }
        }

        $queryInfo = $this->getQueryInformation($url) ?? '';
        $embedUrl = $this->getEmbedUrl($videoID, $queryInfo);
        if (!$embedUrl) {
            throw new Exception('Unable to find Twitch Post', 400);
        }
        $data['attributes'] = $data['attributes'] ?? [];
        $data['attributes']['videoID'] = $videoID;
        $data['attributes']['embedUrl'] = $embedUrl;

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

    /**
     * Assigns the link type and retrieves the video id.
     *
     * @param string $url The posted url.
     *
     * @return string $videoID The id of the posted media.
     */
    public function getUrlInformation(string $url): string {

        $domain = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $videoID = null;

        if ($domain == "clips.twitch.tv") {
            $this->urlType = 'clip';
            preg_match('/\/(?<id>[a-zA_Z0-9_-]+)/i', $path,$clipID);
            if ($clipID['id']) {
                $videoID = $clipID['id'];
            }
        }

        if ($domain == "www.twitch.tv") {
            preg_match('/(\/(?<isVideoOrCollection>\w+)\/)?(?<id>[a-zA-Z0-9]+)/i', $path, $linkType);
            if ($linkType['isVideoOrCollection']) {
                $this->urlType = ($linkType['isVideoOrCollection'] == 'videos') ? 'video' : 'collection';
            } else {
                $this->urlType = 'channel';
            }
            $videoID = $linkType['id'];
        }
        return $videoID;
    }

    /**
     * Gets any query string attached to link.
     *
     * @param  string $url The posted url.
     *
     * @return array $query The query parameters of the posted url.
     */
    private function getQueryInformation(string $url): array {
        $query = [];
        $queryString = parse_url($url, PHP_URL_QUERY);
        if ($queryString) {
            parse_str($queryString, $query);
        }
        return $query;
    }

    /**
     * Assigns a embed url based on the linktype and query string.
     *
     * @param string $videoID The id of the posted media.
     * @param array $queryInfo The query parameters of the posted url.
     *
     * @return string $embedUrl The url used to generate the embed.
     */
    public function getEmbedUrl($videoID, $queryInfo = null): string {
        $embedURL = '';
        $t ='';
        $autoplay ='';
        $muted ='';
        if ($queryInfo) {
            if (array_key_exists('t', $queryInfo)) {
                $t = $this->filterQueryTime($queryInfo['t']);
            }
            if (array_key_exists('autoplay', $queryInfo)) {
                $autoplay = $this->filtersBooleanString($queryInfo['autoplay']);
            }
            if (array_key_exists('muted', $queryInfo)) {
                $muted = $this->filtersBooleanString($queryInfo['muted']);
            }
        }

        if ($this->urlType == 'clip' ) {
            $embedURL = "https://clips.twitch.tv/embed?clip=".$videoID;
        }

        if ($this->urlType == 'channel') {
            $embedURL = "https://player.twitch.tv/?channel=".$videoID;
        }

        if ($this->urlType == 'collection') {
            $embedURL = "https://player.twitch.tv/?collection=".$videoID;
        }

        if ($this->urlType == 'video') {
            $embedURL = "https://player.twitch.tv/?video=v".$videoID;
            if ($autoplay) {
                $embedURL.="&autoplay=".$autoplay;
            }
            if ($t) {
                $embedURL.="&t=".$t;
            }
            if ($muted) {
               $embedURL.="&muted=".$muted;
            }
        }
        return $embedURL;
    }

    /**
     * Filters the time parameter of the query string to ensure the time is valid.
     *
     * @param string $time The time parameter from the query string.
     *
     * @return string $validTime The filtered time string.
     */
    private function filterQueryTime($time): string {
        $validTime = '';
        if (preg_match('/\b[0-9]{1,2}h[0-9]{1,2}m[0-9]{1,2}s/i', $time, $match)) {
            $validTime = $match[0];
        }
        return $validTime;
    }

    /**
     * Filters a query parameter to ensure it's true or false.
     *
     * @param string $param Parameter from a query string.
     *
     * @return string $param A filter parameter.
     */
    private function filtersBooleanString($param): string {
        $param = ($param === "true") ? $param : "false";
        return $param;
    }
}
