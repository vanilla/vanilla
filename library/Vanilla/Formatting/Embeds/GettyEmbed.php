<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

use Exception;

/**
 * Getty Embed.
 */
class GettyEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['gettyimages.ca', 'gty.im', 'gettyimages.com'];

    /**
     * GettyEmbed constructor.
     */
    public function __construct() {
        parent::__construct('getty', 'image');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = [];

        if ($this->isNetworkEnabled()) {
            preg_match(
                '/https?:\/\/www\.gettyimages\.(com|ca).*?(?<postID>\d+)(?!.*\d)/i',
                $url,
                $post
            );
            if (!$post) {
                throw new Exception('Unable to get post ID.', 400);
            }

            $oembedData = $this->oembed("http://embed.gettyimages.com/oembed?url=http://gty.im/" . $post['postID']);
            if ($oembedData) {
                $data = $oembedData;
                $data['attributes'] = array_key_exists('html', $data) ? $this->parseResponseHtml($data['html']) : [];

                if (array_key_exists('postID', $post)) {
                    $data['attributes']['postID'] = $post['postID'];
                }
            }
            return $data;
        }
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $url = "//www.gettyimages.com/detail/".$data['attributes']['postID'];
        $encodedURL = htmlspecialchars($url);
        $encodeID = htmlspecialchars($data['attributes']['id']);

        $height = $data['height'] ?? '';
        $sig = $data['attributes']['sig'] ?? '';
        $width = $data['width'] ?? '';
        $items = $data['attributes']['items'] ?? '';
        $capt = $data['attributes']['isCaptioned']?? '';
        $tld = $data['attributes']['tld'] ?? '';
        $i360 = $data['attributes']['is360'] ?? '';

        $encodedHeight = htmlspecialchars($height);
        $encodedSig = htmlspecialchars($sig);
        $encodedWidth = htmlspecialchars($width);
        $encodedItems = htmlspecialchars($items);
        $encodedCapt  = htmlspecialchars($capt);
        $encodedTld = htmlspecialchars($tld);
        $encodedI360 = htmlspecialchars($i360);
        $originalUrl = htmlspecialchars($data['url']);

        $result = <<<HTML
<div class="embedExternal embedGetty">
    <div class="embedExternal-content">
        <a
            class="embedExternal-content gie-single js-gettyEmbed"
            href="{$encodedURL}"
            id="{$encodeID}"
            data-height="{$encodedHeight}"
            data-width="{$encodedWidth}"
            data-sig="{$encodedSig}"
            data-items="{$encodedItems}"
            data-capt="{$encodedCapt}"
            data-tld="{$encodedTld}"
            data-i360="{$encodedI360}">
            $originalUrl
        </a>
    </div>
</div>
HTML;
        return $result;
    }

    /**
     * Parses the oembed response html for embed attributes.
     *
     * @param string $html
     * @return array $data
     */
    private function parseResponseHtml(string $html): array {
        $data =[];

        if (preg_match('/id:\'(?<id>[a-zA-Z0-9-_]+)\'/i', $html, $id)) {
            $data['id'] = $id['id'];
        }

        if (preg_match('/sig:\'(?<sig>[a-zA-Z0-9-_]+=)\'/i', $html, $sig)) {
            $data['sig'] = $sig['sig'];
        }

        if (preg_match('/items:\'(?<item>[0-9-]+)\'/i', $html, $item)) {
            $data['items'] = $item['item'];
        }

        if (preg_match('/caption: (?<isCaption>true|false)/i', $html, $isCaptioned)) {
            $data['isCaptioned'] = $isCaptioned['isCaption'];
        }

        if (preg_match('/is360: (?<is360>true|false)/i', $html, $is360)) {
            $data['is360'] = $is360['is360'];
        }

        if (preg_match('/tld:\'(?<tld>[a-zA-Z]+)\'/i', $html, $tld)) {
            $data['tld'] = $tld['tld'];
        }
        return $data;
    }
}
