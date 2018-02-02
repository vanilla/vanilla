<?php
namespace VanillaTests\APIv2;

class MediaTest extends AbstractAPIv2Test {

    /**
     * Test scraping pages with /media/scrape.
     *
     * @dataProvider provideScrapeUrls
     * @param array $url
     * @param string $type
     * @param array $info
     */
    public function testScrape($url, $type, array $info) {
        $result = $this->api()->post('media/scrape', ['url' => $url]);
        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();
        $this->assertEquals($url, $body['url']);
        $this->assertEquals($type, $body['type']);

        // Body and type have been validated. Validate the remaining fields.
        unset($body['url'], $body['type']);
        $this->assertCount(count($info), $body);
        foreach ($body as $key => $value) {
            $this->assertEquals($info[$key], $value);
        }
    }

    /**
     * Provide scrape-able URLs in the testing environment and expected information to be returned by each.
     *
     * @return array
     */
    public function provideScrapeUrls() {
        $testBaseUrl = getenv('TEST_BASEURL');
        $urls = [
            [
                "{$testBaseUrl}/tests/fixtures/html/og.htm",
                'site',
                [
                    'name' => 'Online Community Software and Customer Forum Software by Vanilla Forums',
                    'body' => 'Engage your customers with a vibrant and modern online customer community forum. A customer community helps to increases loyalty, reduce support costs and deliver feedback.',
                    'photoUrl' => 'https://vanillaforums.com/images/metaIcons/vanillaForums.png',
                    'height' => null,
                    'width' => null,
                    'attributes' => null
                ]
            ]
        ];

        return $urls;
    }
}
