<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;

use Exception;
use Garden\Http\HttpRequest;
use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\WebScraper;

class PageInfoTest extends TestCase {

    /** @var string Directory of test HTML files. */
    const HTML_DIR = PATH_ROOT.'/tests/fixtures/html';

    /**
     * Provide data for testing the PageInfo::fetch method.
     *
     * @return array
     */
    public function provideFetchData(): array {
        $data = [
            [
                'og.htm',
                [
                    'Title' => 'Online Community Software and Customer Forum Software by Vanilla Forums',
                    'Description' => 'Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.',
                    'Images' => ['https://vanillaforums.com/images/metaIcons/vanillaForums.png']
                ]
            ],
            [
                'plain.htm',
                [
                    'Title' => 'I am a standard title.',
                    'Description' => 'I am a standard description.',
                    'Images' => []
                ]
            ],
            [
                'no-description.htm',
                [
                    'Title' => 'I am a standard title.',
                    'Description' => 'I am a description. Instead of being part of the document head, I am inside the page contents. This is not ideal and is only a fallback for pages without proper meta descriptors.',
                    'Images' => []
                ]
            ]
        ];
        return $data;
    }

    /**
     * Test the PageInfo::fetch method.
     *
     * @param string $file
     * @param array $expected
     * @throws Exception if there was an error loading the file.
     * @dataProvider provideFetchData
     */
    public function testFetch(string $file, array $expected) {
        $pageInfo = new WebScraper(new HttpRequest());
        $url = 'file://'.self::HTML_DIR."/{$file}";
        $expected['Url'] = $url;
        $result = $pageInfo->pageInfo($url);
        $this->assertEquals($expected, $result);
    }
}
