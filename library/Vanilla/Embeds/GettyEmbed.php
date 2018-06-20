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
                '/https?:\/\/www\.gettyimages\.(com|ca)\/[a-zA-Z0-9]+\/(?<postID>[0-9]+)/i',
                $url,
                $post
            );

            // The oembed is used only to pull the width and height of the object.
            $oembedData = $this->oembed("http://embed.gettyimages.com/oembed?url=http://gty.im/".$post['postID']);

            if ($oembedData) {
                $data = $oembedData;
            }
            $data['attributes'] = $this->parseResponseHtml($data['html']);
        }
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {

        $result = <<<HTML

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
            $data['isCaptioned'] = $isCaptioned['isCaptioned'];
        }

        preg_match( '/is360: (?<is360>true|false)/i', $html,$is360);
        if ($is360) {
            $data['is360'] = $is360['is360'];
        }

        preg_match( '/tld:\'(?<tld>[a-zA-Z]+)\'/i', $html,$tld);
        if ($is360) {
            $data['tld'] = $tld['tld'];
        }

        return $data;
    }
}
