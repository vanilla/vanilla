<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 2018-06-05
 * Time: 5:55 PM
 */

namespace Vanilla\Embeds;


class SoundCloudEmbed extends Embed {

    /** @inheritdoc */
    protected $type = 'soundcloud';

    /** @inheritdoc */
    protected $domains = ['soundcloud.com'];

    public function __construct() {
        parent::__construct('soundcloud', 'image');
    }
//https://soundcloud.com/octobersveryown/drake-im-upset
    /**
     * @inheritdoc
     *
     */
    function matchUrl(string $url) {
        $data = null;
        $encodedUrl = urlencode($url);

        if ($this->isNetworkEnabled()) {
            $oembedData = $this->oembed("https://soundcloud.com/oembed?url=" . $encodedUrl . "&format=json");

            if (array_key_exists('html', $oembedData)) {
                $data = $this->parseResponseHtml($oembedData['html']);
            }
        }

        $data['height'] = $oembedData['height'];

        return $data;
    }

    /**
     * @inheritdoc
     *
     */
    public function renderData(array $data): string {
        $result = <<<HTML
<div class="embed-image embed embedImage">
<iframe width="100%" height="{$data['height']}" scrolling="no" frameborder="no" 
    src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/{$data['attributes']['track']}&show_artwork={$data['attributes']['showArtwork']}&visual=true"></iframe>
</div>
HTML;

         return $result;


    }

    public function parseResponseHtml(string $html): array {
        $data = [];
        preg_match('/(visual=(?<visual>true))/i', $html,$showVisual );
        $data['attributes']['visual'] = $showVisual['visual'];
        preg_match('/(show_artwork=(?<artwork>true))i', $html,$showArtwork );
        $data['attributes']['showArtwork'] = $showArtwork['artwork'];
        preg_match('/(?<=2F)(?<track>\d+)(&)/', $html, $trackNumber);
        $data['attributes']['track'] = $trackNumber['track'];

        return $data;

    }

}
