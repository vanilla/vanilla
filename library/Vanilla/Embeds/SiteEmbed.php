<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Embeds;

use Exception;

/**
 * Generic site embed.
 */
class SiteEmbed extends AbstractEmbed {

    protected $type = 'site';

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $result = [
            'url' => $url,
            'name' => null,
            'body' => null,
            'photoUrl' => null,
            'media' => []
        ];

        $pageInfo = fetchPageInfo($url, 3, false, true);

        if ($pageInfo['Exception']) {
            throw new Exception($pageInfo['Exception']);
        }

        $result['name'] = $pageInfo['Title'] ?: null;
        $result['body'] = $pageInfo['Description'] ?: null;
        $result['photoUrl'] = !empty($pageInfo['Images']) ? reset($pageInfo['Images']) : null;
        $result['media'] = $pageInfo['Media'];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        return '';
    }
}
