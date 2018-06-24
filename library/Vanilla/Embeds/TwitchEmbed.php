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
class TwitchEmbed extends Embed
{

    /** @inheritdoc */
    protected $domains = ['www.twitch.tv', 'clips.twitch.tv', 'player.twitch.tv'];

    private $linkType;

    /**
     * InstagramEmbed constructor.
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
        $domain = parse_url($url, PHP_URL_HOST);
        $data = [];


        if ($this->isNetworkEnabled()) {
            $videoID = $this->parseURL($url);

            if (!$videoID) {
                throw new Exception
            }
            $oembedData = $this->oembed("https://api.twitch.tv/v4/oembed?url=" . urlencode($url));

            $queryInfo = $this->getQueryInformation($url);
            $embedUrl = $this->getEmbedUrl($videoID, $queryInfo);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string
    {


        $result = <<<HTML

HTML;

        return $result;
    }

    private function parseURL($url) {
        // Get info from the URL.
        $domain = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $videoID = null;

        if ($domain == "clips.twitch.tv") {
            $this->linkType = 'clip';
            preg_match('\/(?<id>[a-zA_Z0-9_-]+)/i',$path,$clipID);
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

    private function getQueryInformation($url) {
        $query = [];
        $queryString = parse_url($url, PHP_URL_QUERY);
        if ($queryString) {
            parse_str($queryString, $query);
        }
        return $query;
    }

    private function getEmbedUrl($videoID, $queryInfo){
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
                $params .= '&rel=' . (int)$rel;
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

    //            https://clips.twitch.tv/SarcasticDependableCormorantBudStar
    //https://clips.twitch.tv/embed?clip=SarcasticDependableCormorantBudStar

    //            https://www.twitch.tv/iddqd
    // src="https://player.twitch.tv/?channel=ninja"
//            https://www.twitch.tv/videos/276279462?t=00h00m05s&autoplay=true&muted=true;
    // src="https://player.twitch.tv/?autoplay=false&video=v276279462
    //// https://player.twitch.tv/?autoplay=false&t=0-1h59m59s&video=v276279462
}
