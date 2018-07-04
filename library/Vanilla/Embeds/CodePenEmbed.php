<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Embeds;

/**
 * CodePen Embed.
 */
class CodePenEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['codepen.io'];

    /**
     * CodePenEmbed constructor.
     */
    public function __construct() {
        parent::__construct('codepen', 'image');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = [];
        $oembedData = [];

        if ($this->isNetworkEnabled()) {
            $oembedData = $this->oembed("https://codepen.io/api/oembed?url=".urlencode($url)."&format=json");
        }

        if ($oembedData) {
            if (array_key_exists('html', $oembedData)) {
                $data['attributes'] = $this->parseHtml($oembedData['html']);
            }
            if (array_key_exists('height', $oembedData)) {
                $data['height'] = $oembedData['height'];
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $height = $data['height'] ?? '';
        $embedUrl = $data['attributes']['embedUrl'] ?? '';
        $style = $data['attributes']['style'] ?? '';
        $id = $data['attributes']['id'];

        $encodedHeight = htmlspecialchars($height);
        $encodedEmbedUrl = htmlspecialchars($embedUrl);
        $encodedStyle = htmlspecialchars($style);
        $encodedId = htmlspecialchars($id);

        $result = <<<HTML
<div class="embedExternal embedCodePen">
    <div class="embedExternal-content">
        <iframe class="cp_embed_iframe" scrolling="no" id="{$encodedId}" height="{$encodedHeight}" src="{$encodedEmbedUrl}" style="{$encodedStyle}"></iframe>
    </div>
</div>
HTML;
        return $result;
    }

    /**
     * Parse the oembed reponse for the required data.
     *
     * @param string $html The data returned from the oembed call.
     * @return array
     */
    private function parseHtml($html): array {
        $data = [];

        if (preg_match('/id="(?<id>[a-zA-Z0-9_-]+)"/i', $html, $id)) {
            $data['id'] = $id['id'];
        }

        if (preg_match('/src="(?<url>https:\/\/codepen.io\/.*)\?/i', $html, $url)) {
            $data['embedUrl'] = $url['url'];
            //set the default theme
            $data['embedUrl'] .= '?theme-id=0';
        }

        if (preg_match('/style="(?<style>.*?)"/i', $html, $style)) {
            $data['style'] = $style['style'];
        }

        return $data;
    }
}
