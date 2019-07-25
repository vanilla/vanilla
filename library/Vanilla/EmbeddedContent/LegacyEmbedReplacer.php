<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

use Gdn_Format;

/**
 * Class for rendering the old style of embeds.
 * These are essentially some string replacement and regexes.
 *
 * @deprecated Use EmbedService instead.
 */
class LegacyEmbedReplacer {

    /** @var EmbedConfig */
    private $embedConfig;

    /**
     * DI.
     *
     * @param EmbedConfig $embedConfig
     */
    public function __construct(EmbedConfig $embedConfig) {
        $this->embedConfig = $embedConfig;
    }

    /**
     * Transform url to embedded representation.
     *
     * Takes a url and tests to see if we can embed it in a post. If so, returns the the embed code. Otherwise,
     * returns an empty string.
     *
     * @param string $url The url to test whether it's embeddable.
     * @return string The embed code for the given url.
     */
    public function replaceUrl(string $url) {
        if (!$this->embedConfig->areEmbedsEnabled()) {
            return '';
        }

        list($width, $height) = $this->embedConfig->getLegacyEmbedSize();

        $urlParts = parse_url($url);
        parse_str($urlParts['query'] ?? '', $query);
        // There's the possibility the query string could be encoded, resulting in parameters that begin with "amp;"
        foreach ($query as $key => $val) {
            $newKey = stringBeginsWith($key, 'amp;', false, true);
            if ($newKey !== $key) {
                $query[$newKey] = $val;
                unset($query[$key]);
            }
        }

        $embeds = $this->getEmbedRegexes();
        $key = '';
        $matches = [];

        foreach ($embeds as $embedKey => $value) {
            foreach ($value['regex'] as $regex) {
                if (preg_match($regex, $url, $matches)) {
                    $key = $embedKey;
                    break;
                }
            }
            if ($key !== '') {
                break;
            }
        }

        if (!c('Garden.Format.' . $key, true)) {
            return '';
        }

        switch ($key) {
            case 'YouTube':
                // Supported youtube embed urls:
                //
                // http://www.youtube.com/playlist?list=PL4CFF79651DB8159B
                // https://www.youtube.com/playlist?list=PL4CFF79651DB8159B
                // https://www.youtube.com/watch?v=sjm_gBpJ63k&list=PL4CFF79651DB8159B&index=1
                // http://youtu.be/sjm_gBpJ63k
                // https://www.youtube.com/watch?v=sjm_gBpJ63k
                // http://youtu.be/GUbyhoU81sQ?t=1m8s
                // https://m.youtube.com/watch?v=iAEKPcz9www
                // https://youtube.com/watch?v=iAEKPcz9www
                // https://www.youtube.com/watch?v=p5kcBxL7-qI
                // https://www.youtube.com/watch?v=bG6b3V2MNxQ#t=33

                $videoId = $matches['videoId'] ?? false;
                $listId = $matches['listId'] ?? false;

                if (!empty($listId)) {
                    // Playlist.
                    if (empty($videoId)) {
                        // Playlist, no video.
                        $result = <<<EOT
<iframe width="{$width}" height="{$height}" src="https://www.youtube.com/embed/videoseries?list={$listId}" frameborder="0" allowfullscreen></iframe>
EOT;
                    } else {
                        // Video in a playlist.
                        $result = <<<EOT
<iframe width="{$width}" height="{$height}" src="https://www.youtube.com/embed/{$videoId}?list={$listId}" frameborder="0" allowfullscreen></iframe>
EOT;
                    }
                } else {
                    // Regular ol' youtube video embed.
                    $minutes = $matches['minutes'] ?? false;
                    $seconds = $matches['seconds'] ?? false;
                    $fullUrl = $videoId . '?autoplay=1';
                    if (!empty($minutes) || !empty($seconds)) {
                        $time = $minutes * 60 + $seconds;
                        $fullUrl .= '&start=' . $time;
                    }

                    // Jump to start time.
                    if ($start = $matches['start'] ?? false) {
                        $fullUrl .= '&start=' . $start;
                        $start = '#t=' . $start;
                    }

                    if (array_key_exists('rel', $query)) {
                        $fullUrl .= "&rel={$query['rel']}";
                    }

                    $result = '<span class="VideoWrap">';
                    $result .= '<span class="Video YouTube" data-youtube="youtube-' . $fullUrl . '">';
                    $result .= '<span class="VideoPreview"><a href="https://www.youtube.com/watch?v=' . $videoId . $start . '">';
                    $result .= '<img src="https://img.youtube.com/vi/' . $videoId . '/0.jpg" width="'
                            . $width . '" height="' . $height . '" border="0" /></a></span>';
                    $result .= '<span class="VideoPlayer"></span>';
                    $result .= '</span>';

                }
                $result .= '</span>';

                return $result;
                break;

            case 'Vimeo':
                $id = $matches[1];

                return <<<EOT
<iframe
    src="https://player.vimeo.com/video/{$id}"
    width="{$width}"
    height="{$height}"
    frameborder="0"
    webkitallowfullscreen
    mozallowfullscreen
    allowfullscreen
></iframe>
EOT;
                break;

            case 'Gifv':
                $id = $matches[1];
                $modernBrowser = t('Your browser does not support HTML5 video!');

                return <<<EOT
<div class="imgur-gifv VideoWrap">
<video poster="https://i.imgur.com/{$id}h.jpg" preload="auto" autoplay="autoplay" muted="muted" loop="loop">
<source src="https://i.imgur.com/{$id}.webm" type="video/webm">
<source src="https://i.imgur.com/{$id}.mp4" type="video/mp4">
<p>{$modernBrowser} https://i.imgur.com/{$id}.gifv</p>
</video>
</div>
EOT;
                break;

            case 'Twitter':
                return <<<EOT
<div class="twitter-card js-twitterCard" data-tweeturl="{$matches[0]}" data-tweetid="{$matches[1]}"><a
href="{$matches[0]}"
class="tweet-url" rel="nofollow">{$matches[0]}</a></div>
EOT;
                break;

            case 'Vine':
                return <<<EOT
<div class="vine-video VideoWrap">
   <iframe class="vine-embed" src="https://vine.co/v/{$matches[1]}/embed/simple" width="320" height="320" frameborder="0"></iframe>
</div>
EOT;
                break;

            case 'Instagram':
                return <<<EOT
<div class="instagram-video VideoWrap">
    <iframe
        src="https://instagram.com/p/{$matches[1]}/embed/"
        width="412"
        height="510"
        frameborder="0"
        scrolling="no"
        allowtransparency="true"
    ></iframe>
</div>
EOT;
                break;

            case 'Pinterest':
                return <<<EOT
<a data-pin-do="embedPin" href="https://pinterest.com/pin/{$matches[1]}/" class="pintrest-pin" rel="nofollow"></a>
EOT;
                break;

            case 'Getty':
                return <<<EOT
<iframe
    src="https://embed.gettyimages.com/embed/{$matches[1]}"
    width="{$matches[2]}"
    height="{$matches[3]}"
    frameborder="0"
    scrolling="no"
></iframe>
EOT;
                break;

            case 'Twitch':
                return <<<EOT
<iframe
    src="https://player.twitch.tv/?channel={$matches[1]}&autoplay=false"
    height="360"
    width="640"
    frameborder="0"
    scrolling="no"
    autoplay="false"
    allowfullscreen="true"
></iframe>
EOT;
                break;

            case 'TwitchRecorded':
                return <<<EOT
<iframe
    src="https://player.twitch.tv/?video={$matches[1]}&autoplay=false"
    height="360"
    width="640"
    frameborder="0"
    scrolling="no"
    autoplay="false"
    allowfullscreen="true"
></iframe>
EOT;
                break;

            case 'Soundcloud':
                $frameSrc = "https://w.soundcloud.com/player/?url="
                . "https%3A//soundcloud.com/{$matches[1]}/{$matches[2]}&amp;"
                . "color=ff5500&amp;auto_play=false&amp;hide_related=false&amp;"
                . "show_comments=true&amp;show_user=true&amp;show_reposts=false";
                return <<<EOT
<iframe
    width="100%"
    height="166"
    scrolling="no"
    frameborder="no"
    src="$frameSrc"
></iframe>
EOT;
                break;

            case 'Wistia':
                if (!($matches['videoID'] ?? false)) {
                    break;
                }
                $wistiaClass = "wistia_embed wistia_async_{$matches['videoID']} videoFoam=true allowThirdParty=false";

                if (!empty($matches['time'])) {
                    $wistiaClass .= " time={$matches['time']}";
                }

                return <<<EOT
<script charset="ISO-8859-1" src="https://fast.wistia.com/assets/external/E-v1.js" async></script>
<div
    class="wistia_responsive_padding"
    style="padding:56.25% 0 0 0;position:relative;"
>
    <div
        class="wistia_responsive_wrapper"
        style="height:100%;left:0;position:absolute;top:0;width:100%;"
    >
        <div class="{$wistiaClass}" style="height:100%;width:100%">&nbsp;</div>
    </div>
</div>
EOT;
        }

        return '';
    }

    /**
     * Strips out embed/iframes we support and replaces with placeholder.
     *
     * This allows later parsing to insert a sanitized video video embed normally.
     * Necessary for backwards compatibility from when we allowed embed & object tags.
     *
     * This is not an HTML filter; it enables old YouTube videos to theoretically work,
     * it doesn't effectively block YouTube iframes or objects.
     *
     * @param string $content
     * @return string HTML
     */
    public function unembedContent(string $content): string {
        if ($this->embedConfig->isYoutubeEnabled()) {
            $content = preg_replace(
                '`<iframe.*src="https?://.*youtube\.com/embed/([a-z0-9_-]*)".*</iframe>`i',
                "\nhttps://www.youtube.com/watch?v=$1\n",
                $content
            );
            $content = preg_replace(
                '`<object.*value="https?://.*youtube\.com/v/([a-z0-9_-]*)[^"]*".*</object>`i',
                "\nhttps://www.youtube.com/watch?v=$1\n",
                $content
            );
        }
        if ($this->embedConfig->isVimeoEnabled()) {
            $content = preg_replace(
                '`<iframe.*src="((https?)://.*vimeo\.com/video/([0-9]*))".*</iframe>`i',
                "\n$2://vimeo.com/$3\n",
                $content
            );
            $content = preg_replace(
                '`<object.*value="((https?)://.*vimeo\.com.*clip_id=([0-9]*)[^"]*)".*</object>`i',
                "\n$2://vimeo.com/$3\n",
                $content
            );
        }
        if ($this->embedConfig->isGettyEnabled()) {
            $content = preg_replace(
                '`<iframe.*src="(https?:)?//embed\.gettyimages\.com/embed/([\w=?&+-]*)" width="([\d]*)" height="([\d]*)".*</iframe>`i',
                "\nhttp://embed.gettyimages.com/$2/$3/$4\n",
                $content
            );
        }

        return $content;
    }

    /**
     * Get the regexes for the different embeds.
     *
     * For each embed, add a key, a string to test the url against using strpos, and the regex for the url to parse.
     * The is an array of strings. If there are more than one way to match the url, you can add multiple regex strings
     * in the regex array. This is useful for backwards-compatibility when a service updates its url structure.
     *
     * @return array
     */
    private function getEmbedRegexes(): array {
        return [
            'YouTube' => [
                'regex' => [
                    // Warning: Very long regex.
                    '/https?:\/\/(?:(?:www.)|(?:m.))?(?:(?:youtube.com)|(?:youtu.be))\/(?:(?:playlist?)'
                    . '|(?:(?:watch\?v=)?(?P<videoId>[\w-]{11})))(?:\?|\&)?'
                    . '(?:list=(?P<listId>[\w-]*))?(?:t=(?:(?P<minutes>\d*)m)?(?P<seconds>\d*)s)?(?:#t=(?P<start>\d*))?/i'
                ],
            ],
            'Twitter' => [
                'regex' => ['/https?:\/\/(?:www\.)?twitter\.com\/(?:#!\/)?(?:[^\/]+)\/status(?:es)?\/([\d]+)/i'],
            ],
            'Vimeo' => [
                'regex' => ['/https?:\/\/(?:www\.)?vimeo\.com\/(?:channels\/[a-z0-9]+\/)?(\d+)/i'],
            ],
            'Vine' => [
                'regex' => ['/https?:\/\/(?:www\.)?vine\.co\/(?:v\/)?([\w]+)/i'],
            ],
            'Instagram' => [
                'regex' => ['/https?:\/\/(?:www\.)?instagr(?:\.am|am\.com)\/p\/([\w-]+)/i'],
            ],
            'Pinterest' => [
                'regex' => [
                    '/https?:\/\/(?:www\.)?pinterest\.com\/pin\/([\d]+)/i',
                    '/https?:\/\/(?:www\.)?pinterest\.ca\/pin\/([\d]+)/i',
                ],
            ],
            'Getty' => [
                'regex' => ['/https?:\/\/embed.gettyimages\.com\/([\w=?&;+-_]*)\/([\d]*)\/([\d]*)/i'],
            ],
            'Twitch' => [
                'regex' => ['/https?:\/\/(?:www\.)?twitch\.tv\/([\w]+)$/i'],
            ],
            'TwitchRecorded' => [
                'regex' => ['/https?:\/\/(?:www\.)?twitch\.tv\/videos\/(\w+)$/i'],
            ],
            'Soundcloud' => [
                'regex' => ['/https?:(?:www\.)?\/\/soundcloud\.com\/([\w=?&;+-_]*)\/([\w=?&;+-_]*)/i'],
            ],
            'Gifv' => [
                'regex' => ['/https?:\/\/i\.imgur\.com\/([a-z0-9]+)\.gifv/i'],
            ],
            'Wistia' => [
                'regex' => [
                    // Format1
                    '/https?:\/\/(?:[A-za-z0-9\-]+\.)?(?:wistia\.com|wi\.st)\/.*?'
                    . '\?wvideo=(?<videoID>([A-za-z0-9]+))(\?wtime=(?<time>((\d)+m)?((\d)+s)?))?/i',
                    // Format2
                    '/https?:\/\/([A-za-z0-9\-]+\.)?(wistia\.com|wi\.st)\/medias\/(?<videoID>[A-za-z0-9]+)'
                    . '(\?wtime=(?<time>((\d)+m)?((\d)+s)?))?/i',
                ],
            ],
        ];
    }
}
