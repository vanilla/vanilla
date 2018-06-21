<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Embeds;

/**
 * Getty Embed.
 */
class GettyEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['gettyimages.ca', 'gty.im'];

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
        $oembedData= [];

        if ($this->isNetworkEnabled()) {

            preg_match(
                '/https?:\/\/www\.gettyimages\.(com|ca).*?(?<postID>\d+)(?!.*\d)/i',
                $url,
                $post
            );

            // The oembed is used only to pull the width and height of the object.
            $oembedData = $this->oembed("http://embed.gettyimages.com/oembed?url=http://gty.im/".$post['postID']);

            if ($oembedData) {
                $data = $oembedData;
            }
            $data['attributes'] = $this->parseResponseHtml($data['html']);
            $data['attributes']['post'] = $post['postID'];
        }
        return $data;
    }

    /**
     * @inheritdocs
     */
    public function renderData(array $data): string {

        $url = "//www.gettyimages.com/detail/".$data['attributes']['post'];
        $encodedData = json_encode($data);

        $result = <<<HTML
        <a id="{$data['attributes']['id']}" data-sig="{$data['attributes']['sig']}" data-h={$data['height']} data-w={$data['width']} 
        data-items="{$data['attributes']['items']}" data-capt="{$data['attributes']['isCaptioned']}" data-tld="{$data['attributes']['tld']}" data-is360="{$data['attributes']['is360']}"
        class='gie-single js-gettyEmbed' href="{$url}" target='_blank'> Embed from Getty Images</a>
HTML;
       return $result;

    }

    /**
     * Parses the oembed repsonse html for permalink and other data.
     *
     * @param string $html
     * @return array $data
     */
    public function parseResponseHtml(string $html): array {
        $data =[];

        preg_match(
            '/id:\'(?<id>[a-zA-Z0-9-_]+)\'/i', $html,
            $id
        );
        if ($id) {
            $data['id'] = $id['id'];
        }

        preg_match( '/sig:\'(?<sig>[a-zA-Z0-9-_]+=)\'/i', $html,$sig);
        if ($sig) {
            $data['sig'] = $sig['sig'];
        }

        preg_match( '/items:\'(?<item>[0-9-]+)\'/i', $html,$item);
        if ($item) {
            $data['items'] = $item['item'];
        }

        preg_match( '/caption: (?<isCaption>true|false)/i', $html,$isCaptioned);
        if ($isCaptioned) {
            $data['isCaptioned'] = $isCaptioned['isCaption'];
        }

        preg_match( '/is360: (?<is360>true|false)/i', $html,$is360);
        if ($is360) {
            $data['is360'] = $is360['is360'];
        }

        preg_match( '/tld:\'(?<tld>[a-zA-Z]+)\'/i', $html,$tld);
        if ($tld) {
            $data['tld'] = $tld['tld'];
        }
        return $data;
    }
}
