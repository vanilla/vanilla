<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

/**
 * Soundcloud Embed.
 */
class SoundCloudEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['soundcloud.com'];

    /**
     * SoundCloudEmbed constructor.
     */
    public function __construct() {
        parent::__construct('soundcloud', 'audio');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = [];
        $oembedData =[];
        $encodedUrl = urlencode($url);

        if ($this->isNetworkEnabled()) {
            $oembedData = $this->oembed("https://soundcloud.com/oembed?url=".$encodedUrl."&format=json");
            if (array_key_exists('html', $oembedData)) {
                $data = $this->parseResponseHtml($oembedData['html']);
            }
        }
        if (array_key_exists('height', $oembedData)) {
            $data['height'] = $oembedData['height'];
        }

        return $data;
    }
    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {

        $postID = htmlspecialchars($data['attributes']['postID']);
        $showArtwork = htmlspecialchars($data['attributes']['showArtwork']);
        $visual = htmlspecialchars($data['attributes']['visual']);
        $url = htmlspecialchars(\Gdn_Format::sanitizeUrl($data['attributes']['embedUrl']));

        $result = <<<HTML
<div class="embedExternal embedSoundCloud">
    <div class="embedExternal-content">
        <iframe width="100%" scrolling="no" frameborder="no"
            src="{$url}{$postID}&amp;show_artwork={$showArtwork}&amp;visual={$visual}">
        </iframe>
    </div>
</div>
HTML;
         return $result;
    }

    /**
     * Parses the oembed repsonse html for permalink and other data.
     * SoundCloud embed types that are supports include tracks, sets and users.
     * example urls:
     * https://soundcloud.com/thisforbaby/lil-baby-ft-drake-yes-indeed
     * https://soundcloud.com/uiceheidd
     * https://soundcloud.com/liluzivert/sets/luv-is-rage-2-1
     * @param string $html The html snippet send from the oembed call.
     * @return array $data
     */
    public function parseResponseHtml(string $html): array {
        $data = [];
        preg_match('/(visual=(?<visual>true))/i', $html, $showVisual);
        if ($showVisual) {
            $data['attributes']['visual'] = $showVisual['visual'];
        }
        preg_match('/(show_artwork=(?<artwork>true))/i', $html, $showArtwork);
        if ($showArtwork) {
            $data['attributes']['showArtwork'] = $showArtwork['artwork'];
        }
        preg_match('/(?<=%2Ftracks%2F)(?<track>\d+)(&)/', $html, $trackNumber);
        preg_match('/(?<=2Fplaylists%2F)(?<playListID>[a-zA-Z0-9]+)(&)/', $html, $playList);
        preg_match('/(?<=%2Fusers%2F)(?<userID>\d+)(&)/', $html, $user);

        if ($trackNumber) {
            $data['attributes']['postID'] = $trackNumber['track'] ?? "";
            $data['attributes']['embedUrl'] = "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/tracks/";
        } elseif ($playList) {
            $data['attributes']['postID'] = $playList['playListID'] ?? "";
            $data['attributes']['embedUrl'] = "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/playlists/";
        } elseif ($user) {
            $data['attributes']['postID'] = $user['userID'] ?? "";
            $data['attributes']['embedUrl'] = "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/users/";
        } else {
            $data['attributes']['postID'] = "";
            $data['attributes']['embedUrl'] = "";
        }

        if (!$data['attributes']['postID']) {
            throw new Exception('Unable to get track ID.', 400);
        }

        return $data;
    }
}
