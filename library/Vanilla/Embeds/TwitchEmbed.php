<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Embeds;

use Exception;

/**
 * Twitch Embed.
 */
class TwitchEmbed extends VideoEmbed {

    const DEFAULT_HEIGHT = 300;

    const DEFAULT_WIDTH = 400;

    /** @inheritdoc */
    protected $domains = ['www.twitch.tv', 'clips.twitch.tv'];

    /*** @var string */
    private $linkType;

    /**
     * TwitchEmbed constructor.
     */
    public function __construct()
    {
        parent::__construct('twitch', 'video');
        $this->linkType = '';
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url)
    {
        $data = [];

        if ($this->isNetworkEnabled()) {
            $videoID = $this->parseURL($url);
            if (!$videoID) {
                throw new Exception('Unable to find Twitch Post', 400);
            }
            
            $oembedData = $this->oembed("https://api.twitch.tv/v4/oembed?url=" . urlencode($url));
            if ($oembedData) {
                $data = $this->normalizeOembed($oembedData);
            }
            $queryInfo = $this->getQueryInformation($url) ?? '';
            $embedUrl = $this->getEmbedUrl($videoID, $queryInfo);
            $data['attributes']['videoID'] = $videoID;
            $data['attributes']['embedUrl'] = $embedUrl;
        }
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $attributes = $data['attributes'] ?? [];
        $embedUrl = $attributes['embedUrl'] ?? '';
        $height = (int)$data['height'] ?? self::DEFAULT_HEIGHT;
        $width = (int)$data['width'] ?? self::DEFAULT_WIDTH;
        $name = $data['name'] ?? '';
        $photoURL = $data['photoUrl'] ?? '';

        $result = $this->videoCode($embedUrl, $name, $photoURL, $width, $height);
        return $result;
    }

    /**
     * @param $url
     * @return null
     */
    private function parseURL($url) {

        $domain = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $videoID = null;

        if ($domain == "clips.twitch.tv") {
            $this->linkType = 'clip';
            preg_match('/\/(?<id>[a-zA_Z0-9_-]+)/i',$path,$clipID);
            if ($clipID['id']) {
                $videoID = $clipID['id'];
            }
        }

        if ($domain == "www.twitch.tv") {
            preg_match('/(\/(?<isVideoOrCollection>\w+)\/)?(?<id>[a-zA-Z0-9]+)/i',$path, $linkType);
            if ($linkType['isVideoOrCollection']) {
                $this->linkType = ($linkType['isVideoOrCollection'] == 'videos') ? 'video':'collection';
            } else {
                $this->linkType = 'channel';
            }
            $videoID = $linkType['id'];
        }
        return $videoID;
    }

    /**
     * @param $url
     * @return array
     */
    private function getQueryInformation($url) {
        $query = [];
        $queryString = parse_url($url, PHP_URL_QUERY);
        if ($queryString) {
            parse_str($queryString, $query);
        }
        return $query;
    }

    /**
     * @param $videoID
     * @param $queryInfo
     * @return string
     */
    private function getEmbedUrl($videoID, $queryInfo) {
        $embedURL = '';

        $t = $queryInfo['t'];
        $autoplay = $queryInfo['autoplay'];
        $muted = $queryInfo['muted'];

        if ($this->linkType == 'clip' || $this->linkType == 'collections') {
            $embedURL = "https://clips.twitch.tv/embed?clip=".$videoID;
        }
        if ($this->linkType == 'channel') {
            $embedURL = "https://player.twitch.tv/?channel=".$videoID;
        }
        if ($this->linkType == 'video') {
            $embedURL = "https://player.twitch.tv/?video=v".$videoID;
            if ($autoplay) {
                $embedURL .="&".$autoplay;
            }
            if ($t) {
                $embedURL .="&".$t;
            }
            if ($muted) {
               $embedURL .="&".$muted;
            }
        }
        return $embedURL;
    }

}
