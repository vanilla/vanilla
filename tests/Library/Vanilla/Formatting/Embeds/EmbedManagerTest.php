<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Embeds;

use Exception;
use Garden\Http\HttpRequest;
use Vanilla\Formatting\Embeds\CodePenEmbed;
use Vanilla\Formatting\Embeds\GettyEmbed;
use Vanilla\Formatting\Embeds\GiphyEmbed;
use Vanilla\Formatting\Embeds\ImgurEmbed;
use Vanilla\Formatting\Embeds\SoundCloudEmbed;
use Vanilla\Formatting\Embeds\WistiaEmbed;
use Vanilla\Formatting\Embeds\TwitchEmbed;
use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Formatting\Embeds\EmbedManager;
use Vanilla\Formatting\Embeds\InstagramEmbed;
use Vanilla\Formatting\Embeds\LinkEmbed;
use Vanilla\Formatting\Embeds\ImageEmbed;
use Vanilla\Formatting\Embeds\TwitterEmbed;
use Vanilla\Formatting\Embeds\YouTubeEmbed;
use Vanilla\Formatting\Embeds\VimeoEmbed;
use VanillaTests\Fixtures\PageScraper;
use VanillaTests\Fixtures\NullCache;

class EmbedManagerTest extends SharedBootstrapTestCase {

    /**
     * @var string $playButtonSVG HTML for video embes play button svg tag
     */
    private $playButtonSVG =
'<svg class="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24">
    <title>Play Video</title>
    <path class="embedVideo-playIconPath embedVideo-playIconPath-circle" style="fill: currentColor; stroke-width: .3;" d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"></path>
    <polygon class="embedVideo-playIconPath embedVideo-playIconPath-triangle" style="fill: currentColor; stroke-width: .3;" points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"></polygon>
</svg>';

    /**
     *
     * Create a new EmbedManager instance.
     *
     * @return EmbedManager
     */
    private function createEmbedManager(): EmbedManager {
        $embedManager = new EmbedManager(new NullCache(), new ImageEmbed);
        $embedManager->setDefaultEmbed(new LinkEmbed(new PageScraper(new HttpRequest())))
            ->addEmbed(new TwitterEmbed())
            ->addEmbed(new YouTubeEmbed())
            ->addEmbed(new VimeoEmbed())
            ->addEmbed(new InstagramEmbed())
            ->addEmbed(new ImgurEmbed())
            ->addEmbed(new SoundCloudEmbed())
            ->addEmbed(new TwitchEmbed())
            ->addEmbed(new GettyEmbed())
            ->addEmbed(new GiphyEmbed())
            ->addEmbed(new WistiaEmbed())
            ->addEmbed(new CodePenEmbed())
            ->addEmbed(new ImageEmbed(), EmbedManager::PRIORITY_LOW)
            ->setNetworkEnabled(false);
        return $embedManager;
    }

    /**
     * Provide parameters for verifying rendered data.
     *
     * @return array
     */
    public function provideRenderedData() {
        // @codingStandardsIgnoreStart
        $data = [
            [
                [
                    "url" => "https://codepen.io/slafleche/pen/qYrgVx",
                    "type" => "codepen",
                    "name" => null,
                    "body" => null,
                    "photoUrl" => null,
                    "height" => 300,
                    "width" => null,
                    "attributes" => [
                        "id" => "cp_embed_qYrgVx",
                        "embedUrl" => "https://codepen.io/slafleche/embed/preview/qYrgVx?theme-id=0",
                        "style" => [
                            "width" => "100",
                            "overflow" => "hidden",
                        ],
                    ]
                ],
'<div class="embedExternal embedCodePen">
    <div class="embedExternal-content">
        <iframe scrolling="no" id="cp_embed_qYrgVx" height="300" src="https://codepen.io/slafleche/embed/preview/qYrgVx?theme-id=0" style="width: 100%; overflow: hidden;"></iframe>
    </div>
</div>',
            ],
            [
                [
                    "url" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "type" => "image",
                    "name" => null,
                    "body" => null,
                    "photoUrl" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "height" => 630,
                    "width" => 1200,
                    "attributes" => []
                ],
'<div class="embedExternal embedImage">
    <div class="embedExternal-content">
        <a class="embedImage-link" href="https://vanillaforums.com/images/metaIcons/vanillaForums.png" rel="nofollow noopener" target="_blank">
            <img class="embedImage-img" src="https://vanillaforums.com/images/metaIcons/vanillaForums.png">
        </a>
    </div>
</div>'
            ],
            [
                [
                    "url" => "https://www.gettyimages.ca/license/460707851",
                    "type" => "getty",
                    "name" => null,
                    "body" => null,
                    "photoUrl" => null,
                    "height" => 337,
                    "width" => 508,
                    "attributes" => [
                        'id' => "CdkwD1KlQeN8rV9xoKzSAg",
                        'sig' => "OSznWQvhySQdibOA7WcaeKbc1T3SnuazaIvfwlTLyq0=",
                        'items' => "460707851",
                        'isCaptioned' => false,
                        'is360' => false,
                        'tld'=> "com",
                        'postID' => "460707851",
                    ]
                ],
'<div class="embedExternal embedGetty">
    <div class="embedExternal-content">
        <a
            class="embedExternal-content gie-single js-gettyEmbed"
            href="//www.gettyimages.com/detail/460707851"
            id="CdkwD1KlQeN8rV9xoKzSAg"
            data-height="337"
            data-width="508"
            data-sig="OSznWQvhySQdibOA7WcaeKbc1T3SnuazaIvfwlTLyq0="
            data-items="460707851"
            data-capt=""
            data-tld="com"
            data-i360="">
            https://www.gettyimages.ca/license/460707851
        </a>
    </div>
</div>',
            ],
            [
                [
                    "url" => "https://vanillaforums.com",
                    "type" => "link",
                    "name" => "Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 1",
                    "body" => "Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.",
                    "photoUrl" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "height" => null,
                    "width" => null,
                    "attributes" => []
                ],
                '<div class="embedExternal embedText embedLink">
    <div class="embedExternal-content embedText-content embedLink-content">
        <a class="embedLink-link" href="https://vanillaforums.com" rel="noopener noreferrer">
            <article class="embedText-body">
                <img src=\'https://vanillaforums.com/images/metaIcons/vanillaForums.png\' class=\'embedLink-image\' aria-hidden=\'true\'>
                <div class="embedText-main">
                    <div class="embedText-header">
                        <h3 class="embedText-title">Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 1</h3>
                        
                        <span class="embedLink-source metaStyle">https://vanillaforums.com</span>
                    </div>
                    <div class="embedLink-excerpt">Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.</div>
                </div>
            </article>
        </a>
    </div>
</div>',
            ],
            [
                [
                    "url" => "https://vanillaforums.com",
                    "type" => "link",
                    "name" => "Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 2",
                    "body" => "Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.",
                    "height" => null,
                    "width" => null,
                    "attributes" => []
                ],
                '<div class="embedExternal embedText embedLink">
    <div class="embedExternal-content embedText-content embedLink-content">
        <a class="embedLink-link" href="https://vanillaforums.com" rel="noopener noreferrer">
            <article class="embedText-body">
                
                <div class="embedText-main">
                    <div class="embedText-header">
                        <h3 class="embedText-title">Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 2</h3>
                        
                        <span class="embedLink-source metaStyle">https://vanillaforums.com</span>
                    </div>
                    <div class="embedLink-excerpt">Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.</div>
                </div>
            </article>
        </a>
    </div>
</div>',
            ],
            [
                [
                    "url" => "https://vanillaforums.com",
                    "type" => "link",
                    "name" => "Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 3",
                    "body" => "Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.",
                    "photoUrl" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "userPhoto" => "https://secure.gravatar.com/avatar/b0420af06d6fecc16fc88a88cbea8218/?default=https%3A%2F%2Fvanillicon.com%2Fb0420af06d6fecc16fc88a88cbea8218_100.png&rating=g&size=100",
                    "userName" => "Linc",
                    "height" => null,
                    "width" => null,
                    "attributes" => []
                ],
                '<div class="embedExternal embedText embedLink">
    <div class="embedExternal-content embedText-content embedLink-content">
        <a class="embedLink-link" href="https://vanillaforums.com" rel="noopener noreferrer">
            <article class="embedText-body">
                <img src=\'https://vanillaforums.com/images/metaIcons/vanillaForums.png\' class=\'embedLink-image\' aria-hidden=\'true\'>
                <div class="embedText-main">
                    <div class="embedText-header">
                        <h3 class="embedText-title">Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 3</h3>
                        
                        <span class="embedLink-source metaStyle">https://vanillaforums.com</span>
                    </div>
                    <div class="embedLink-excerpt">Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.</div>
                </div>
            </article>
        </a>
    </div>
</div>',
            ],
            [
                [
                    "url" => "https://vanillaforums.com",
                    "type" => "link",
                    "name" => "Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 4",
                    "body" => "Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.",
                    "photoUrl" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "timestamp" => "2018-07-30",
                    "humanTime" => "July 30th 2018",
                    "height" => null,
                    "width" => null,
                    "attributes" => []
                ],
                '<div class="embedExternal embedText embedLink">
    <div class="embedExternal-content embedText-content embedLink-content">
        <a class="embedLink-link" href="https://vanillaforums.com" rel="noopener noreferrer">
            <article class="embedText-body">
                <img src=\'https://vanillaforums.com/images/metaIcons/vanillaForums.png\' class=\'embedLink-image\' aria-hidden=\'true\'>
                <div class="embedText-main">
                    <div class="embedText-header">
                        <h3 class="embedText-title">Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 4</h3>
                        <time class="embedLink-dateTime metaStyle" dateTime="2018-07-30">July 30th 2018</time>
                        <span class="embedLink-source metaStyle">https://vanillaforums.com</span>
                    </div>
                    <div class="embedLink-excerpt">Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.</div>
                </div>
            </article>
        </a>
    </div>
</div>',
            ],
            [
                [
                    "url" => "https://vanillaforums.com",
                    "type" => "link",
                    "name" => "Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 5",
                    "body" => "Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.",
                    "photoUrl" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "timestamp" => "2018-07-30",
                    "humanTime" => "July 30th 2018",
                    "height" => null,
                    "width" => null,
                    "attributes" => []
                ],
                '<div class="embedExternal embedText embedLink">
    <div class="embedExternal-content embedText-content embedLink-content">
        <a class="embedLink-link" href="https://vanillaforums.com" rel="noopener noreferrer">
            <article class="embedText-body">
                <img src=\'https://vanillaforums.com/images/metaIcons/vanillaForums.png\' class=\'embedLink-image\' aria-hidden=\'true\'>
                <div class="embedText-main">
                    <div class="embedText-header">
                        <h3 class="embedText-title">Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 5</h3>
                        <time class="embedLink-dateTime metaStyle" dateTime="2018-07-30">July 30th 2018</time>
                        <span class="embedLink-source metaStyle">https://vanillaforums.com</span>
                    </div>
                    <div class="embedLink-excerpt">Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.</div>
                </div>
            </article>
        </a>
    </div>
</div>',
            ],
            [
                [
                    "url" => "https://vanillaforums.com",
                    "type" => "link",
                    "name" => "Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 6",
                    "body" => "Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.",
                    "height" => null,
                    "width" => null,
                    "attributes" => []
                ],
                '<div class="embedExternal embedText embedLink">
    <div class="embedExternal-content embedText-content embedLink-content">
        <a class="embedLink-link" href="https://vanillaforums.com" rel="noopener noreferrer">
            <article class="embedText-body">
                
                <div class="embedText-main">
                    <div class="embedText-header">
                        <h3 class="embedText-title">Online Community Software and Customer Forum Software by Vanilla Forums - Embed Link 6</h3>
                        
                        <span class="embedLink-source metaStyle">https://vanillaforums.com</span>
                    </div>
                    <div class="embedLink-excerpt">Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.</div>
                </div>
            </article>
        </a>
    </div>
</div>',
            ],
            [
                [
                    'url' =>'https://www.instagram.com/p/BizC-PPFK1m',
                    'type' =>'instagram',
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'permaLink' => 'https://www.instagram.com/p/BizC-PPFK1m',
                        'isCaptioned' => true,
                        'versionNumber' => "8"
                    ],
                ],
'<div class="embedExternal embedInstagram">
    <div class="embedExternal-content">
        <blockquote class="instagram-media" data-instgrm-captioned data-instgrm-permalink="https://www.instagram.com/p/BizC-PPFK1m" data-instgrm-version="8">
            <a href="https://www.instagram.com/p/BizC-PPFK1m">https://www.instagram.com/p/BizC-PPFK1m</a>
        </blockquote>
    </div>
</div>'
            ],
            [
                [
                    'url' =>'https://imgur.com/gallery/10HROiq',
                    'type' =>'imgur',
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'postID' => '10HROiq',
                        'isAlbum' => false,
                    ],
                ],
'<div class="embedExternal embedImgur">
    <div class="embedExternal-content">
        <blockquote class="imgur-embed-pub" lang="en" data-id="10HROiq"><a href="https://imgur.com/10HROiq">https://imgur.com/10HROiq</a></blockquote>
    </div>
</div>'
            ],
            [
                [
                    'url' =>'https://imgur.com/gallery/OsirufX',
                    'type' =>'imgur',
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'postID' => 'OsirufX',
                        'isAlbum' => true,
                    ],
                ],
'<div class="embedExternal embedImgur">
    <div class="embedExternal-content">
        <blockquote class="imgur-embed-pub" lang="en" data-id="a/OsirufX"><a href="https://imgur.com/OsirufX">https://imgur.com/OsirufX</a></blockquote>
    </div>
</div>'
            ],
            [
                [
                    "url" => "https://soundcloud.com/syrebralvibes/the-eden-project-circles",
                    "type" => "soundcloud",
                    "name" => null,
                    "body" => null,
                    "photoUrl" => null,
                    "height" => 400,
                    "width" => null,
                    "attributes" => [
                        "visual" => "true",
                        "showArtwork" => "true",
                        "postID" => "2F174656930",
                        "embedUrl" => "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/tracks/"
                    ],
                ],
'<div class="embedExternal embedSoundCloud">
    <div class="embedExternal-content">
        <iframe width="100%" scrolling="no" frameborder="no"
            src="https://w.soundcloud.com/player/?url=https://api.soundcloud.com/tracks/2F174656930&amp;show_artwork=true&amp;visual=true">
        </iframe>
    </div>
</div>'
            ],
            [
                [
                    "url" => "https://soundcloud.com/uiceheidd",
                    "type" => "soundcloud",
                    "name" => null,
                    "body" => null,
                    "photoUrl" => null,
                    "height" => 300,
                    "width" => null,
                    "attributes" => [
                        "visual" => "true",
                        "showArtwork" => "true",
                        "postID" => "330864225",
                        "embedUrl" => "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/users/"
                    ],
                ],
                '<div class="embedExternal embedSoundCloud">
    <div class="embedExternal-content">
        <iframe width="100%" scrolling="no" frameborder="no"
            src="https://w.soundcloud.com/player/?url=https://api.soundcloud.com/users/330864225&amp;show_artwork=true&amp;visual=true">
        </iframe>
    </div>
</div>'
            ],
            [
                [
                    "url" => "https://soundcloud.com/uiceheidd/sets/juicewrld-the-mixtape",
                    "type" => "soundcloud",
                    "name" => null,
                    "body" => null,
                    "photoUrl" => null,
                    "height" => 300,
                    "width" => null,
                    "attributes" => [
                        "visual" => "true",
                        "showArtwork" => "true",
                        "postID" => "23ff550",
                        "embedUrl" => "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/playlists/"
                    ],
                ],
                '<div class="embedExternal embedSoundCloud">
    <div class="embedExternal-content">
        <iframe width="100%" scrolling="no" frameborder="no"
            src="https://w.soundcloud.com/player/?url=https://api.soundcloud.com/playlists/23ff550&amp;show_artwork=true&amp;visual=true">
        </iframe>
    </div>
</div>'
            ],
            [
                [
                    "url" => "https://www.twitch.tv/videos/276279462",
                    "type" => "twitch",
                    "name" => "20k Fortnite Friday Duos with @hysteria | 2 MINUTE STREAM DELAY",
                    "body" => null,
                    "photoUrl" => "https://static-cdn.jtvnw.net/s3_vods/8a24223c5b12ff7427a8_ninja_29190875424_893099877/thumb/thumb0-640x360.jpg",
                    "height" => 281,
                    "width" => 500,
                    "attributes" => [
                        "videoID" => "276279462",
                        "embedUrl" => "https://player.twitch.tv/?video=v276279462",
                    ],
                ],
                '<div class="embedExternal embedVideo">
    <div class="embedExternal-content">
        <div class="embedVideo-ratio" style="padding-top: 56.2%;">
            <button type="button" data-url="https://player.twitch.tv/?video=v276279462" aria-label="20k Fortnite Friday Duos with @hysteria | 2 MINUTE STREAM DELAY" class="embedVideo-playButton js-playVideo" title="20k Fortnite Friday Duos with @hysteria | 2 MINUTE STREAM DELAY">
                <img class="embedVideo-thumbnail" src="https://static-cdn.jtvnw.net/s3_vods/8a24223c5b12ff7427a8_ninja_29190875424_893099877/thumb/thumb0-640x360.jpg" role="presentation" alt="A thumnail preview of a video"/>
                <span class="videoEmbed-scrim"/>
                '.$this->playButtonSVG.'
            </button>
        </div>
    </div>
</div>'
           ],
            [
                [
                    "url" => "https://giphy.com/gifs/super-smash-bros-ultimate-jwSlQZnsymUW49NC3R",
                    "type" => "giphy",
                    "name" => null,
                    "body" => null,
                    "photoUrl" => null,
                    "height" => 270,
                    "width" => 480,
                    "attributes" => [
                        "postID" => "jwSlQZnsymUW49NC3R",
                    ],
                ],
'<div class="embedExternal embedGiphy">
    <div class="embedExternal-content" style="width: 480px">
        <div class="embedExternal-ratio" style="padding-bottom: 56.25%">
            <iframe class="giphy-embed embedGiphy-iframe" src="https://giphy.com/embed/jwSlQZnsymUW49NC3R"></iframe>
        </div>
    </div>
</div>'
            ],
            [
                [
                    "url" => "https://twitter.com/jack/status/20",
                    "type" => "twitter",
                    "name" => null,
                    "body" => null,
                    "photoUrl" => null,
                    "height" => null,
                    "width" => null,
                    "attributes" => [
                        "statusID" => "20"
                    ]
                ],
'<div class="embedExternal embedTwitter">
    <div class="embedExternal-content js-twitterCard" data-tweeturl="https://twitter.com/jack/status/20" data-tweetid="20">
        <a href="https://twitter.com/jack/status/20" class="tweet-url" rel="nofollow">https://twitter.com/jack/status/20</a>
    </div>
</div>'
            ],
            [
                [
                    "url" => "https://www.youtube.com/watch?v=9bZkp7q19f0",
                    "type" => "youtube",
                    "name" => "YouTube",
                    "body" => null,
                    "photoUrl" => "https://i.ytimg.com/vi/9bZkp7q19f0/hqdefault.jpg",
                    "height" => 270,
                    "width" => 480,
                    "attributes" => [
                        "thumbnail_width" => 480,
                        "thumbnail_height" => 360,
                        "videoID" => "9bZkp7q19f0"
                    ]
                ],
'<div class="embedExternal embedVideo">
    <div class="embedExternal-content">
        <div class="embedVideo-ratio is16by9" style="">
            <button type="button" data-url="https://www.youtube.com/embed/9bZkp7q19f0?feature=oembed&amp;autoplay=1" aria-label="YouTube" class="embedVideo-playButton js-playVideo" title="YouTube">
                <img class="embedVideo-thumbnail" src="https://img.youtube.com/vi/9bZkp7q19f0/0.jpg" role="presentation" alt="A thumnail preview of a video"/>
                <span class="videoEmbed-scrim"/>
                '.$this->playButtonSVG.'
            </button>
        </div>
    </div>
</div>'
            ],
            [
                [
                    "url" => "https://vimeo.com/264197456",
                    "type" => "vimeo",
                    "name" => "Vimeo",
                    "body" => null,
                    "photoUrl" => "https://i.vimeocdn.com/video/694532899_640.jpg",
                    "height" => 272,
                    "width" => 640,
                    "attributes" => [
                        "thumbnail_width" => 640,
                        "thumbnail_height" => 272,
                        "videoID" => "264197456",
                        "embedUrl" => "https://player.vimeo.com/video/264197456?autoplay=1",
                    ]
                ],
'<div class="embedExternal embedVideo">
    <div class="embedExternal-content">
        <div class="embedVideo-ratio" style="padding-top: 42.5%;">
            <button type="button" data-url="https://player.vimeo.com/video/264197456?autoplay=1" aria-label="Vimeo" class="embedVideo-playButton js-playVideo" title="Vimeo">
                <img class="embedVideo-thumbnail" src="https://i.vimeocdn.com/video/694532899_640.jpg" role="presentation" alt="A thumnail preview of a video"/>
                <span class="videoEmbed-scrim"/>
                '.$this->playButtonSVG.'
            </button>
        </div>
    </div>
</div>'
            ],
            [
                [
                    "url" => "https://dave.wistia.com/medias/0k5h1g1chs",
                    "type" => "wistia",
                    "name" => "Lenny Delivers a Video - oEmbed",
                    "body" => null,
                    "photoUrl" => "https://embed-ssl.wistia.com/deliveries/99f3aefb8d55eef2d16583886f610ebedd1c6734.jpg?image_crop_resized=960x540",
                    "height" => 540,
                    "width" => 960,
                    "attributes" => [
                        "thumbnail_width" => 540,
                        "thumbnail_height" => 960,
                        "videoID" => "0k5h1g1chs",
                        "embedUrl" => "https://fast.wistia.net/embed/iframe/0k5h1g1chs",
                    ]
                ],
'<div class="embedExternal embedVideo">
    <div class="embedExternal-content">
        <div class="embedVideo-ratio is16by9" style="">
            <button type="button" data-url="https://fast.wistia.net/embed/iframe/0k5h1g1chs" aria-label="Lenny Delivers a Video - oEmbed" class="embedVideo-playButton js-playVideo" title="Lenny Delivers a Video - oEmbed">
                <img class="embedVideo-thumbnail" src="https://embed-ssl.wistia.com/deliveries/99f3aefb8d55eef2d16583886f610ebedd1c6734.jpg?image_crop_resized=960x540" role="presentation" alt="A thumnail preview of a video"/>
                <span class="videoEmbed-scrim"/>
                '.$this->playButtonSVG.'
            </button>
        </div>
    </div>
</div>'
            ]
        ];
        // @codingStandardsIgnoreEnd
        return $data;
    }

    /**
     * Verify rendered data results.
     *
     * @param array $data
     * @param string $expected
     * @throws Exception if a default embed type is needed, but hasn't been configured.
     * @dataProvider provideRenderedData
     */
    public function testRenderData(array $data, string $expected) {
        $embedManager = $this->createEmbedManager();
        $actual = $embedManager->renderData($data);
        $this->assertEquals($expected, $actual);
    }
}
