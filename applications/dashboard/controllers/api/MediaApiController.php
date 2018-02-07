<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

/**
 * API Controller for `/media`.
 */
class MediaApiController extends AbstractApiController {

    /** @var WebScraper */
    private $webScraper;

    /**
     * MediaApiController constructor.
     *
     * @param WebScraper $webScraper
     */
    public function __construct(WebScraper $webScraper) {
        $this->webScraper = $webScraper;
    }

    /**
     * Scrape information from a URL.
     *
     * @param array $body The request body.
     * @return array
     * @throws Exception
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\HttpException
     * @throws \Vanilla\Exception\PermissionException
     */
    public function post_scrape(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'url:s' => 'The URL to scrape.',
            'force:b?' => [
                'default' => false,
                'description' => 'Force the scrape even if the result is cached.'
            ]
        ], 'in');
        $out = $this->schema([
            'url:s'	=> 'The URL that was scraped.',
            'type:s' => [
                'description' => 'The type of site. This determines how the embed is rendered.',
                'enum' => ['getty', 'image', 'imgur', 'instagram', 'pinterest', 'site', 'smashcast',
                    'soundcloud', 'twitch', 'twitter', 'vimeo', 'vine', 'wistia', 'youtube']
            ],
            'name:s|n' => 'The title of the page/item/etc. if any.',
            'body:s|n' => 'A paragraph summarizing the content, if any. This is not what is what gets rendered to the page.',
            'photoUrl:s|n' => 'A photo that goes with the content.',
            'height:i|n' => 'The height of the image/video/etc. if applicable. This may be the photoUrl, but might exist even when there is no photoUrl in the case of a video without preview image.',
            'width:i|n' => 'The width of the image/video/etc. if applicable.',
            'attributes:o|n' => 'Any additional attributes required by the the specific embed.',
        ], 'out');

        $body = $in->validate($body);

        $pageInfo = $this->webScraper->getPageInfo($body['url'], $body['force']);

        $result = $out->validate($pageInfo);
        return $result;
    }
}
