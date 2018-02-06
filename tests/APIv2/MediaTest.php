<?php
namespace VanillaTests\APIv2;

use WebScraper;

class MediaTest extends AbstractAPIv2Test {

    /**
     * Called after each test is executed.
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function tearDown() {
        // Make sure fetching page info is re-disabled, after every test.
        $this->setDisableFetch(true);
    }

    /**
     * Test scraping pages with /media/scrape.
     *
     * @dataProvider provideScrapeUrls
     * @param array $url
     * @param string $type
     * @param array $info
     * @param bool $enableFetch
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function testScrape($url, $type, array $info, $enableFetch = false) {
        // Fetching is disabled by default in tests. Should it be enabled for this test?
        if ($enableFetch) {
            $this->setDisableFetch(false);
        }
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
                    'attributes' => []
                ],
                true
            ],
            [
                'https://embed.gettyimages.com/embed/1234567890',
                'getty',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'mediaID' => '1234567890'
                    ],
                ]
            ],
            [
                'https://smashcast.tv/foobar',
                'smashcast',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'channelID' => 'foobar'
                    ],
                ]
            ],
            [
                'https://www.smashcast.tv/foobar',
                'smashcast',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'channelID' => 'foobar'
                    ],
                ]
            ],
            [
                'https://example.com/image.bmp',
                'image',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => 'https://example.com/image.bmp',
                    'height' => null,
                    'width' => null,
                    'attributes' => [],
                ]
            ],
            [
                'https://example.com/image.gif',
                'image',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => 'https://example.com/image.gif',
                    'height' => null,
                    'width' => null,
                    'attributes' => [],
                ]
            ],
            [
                'https://example.com/image.jpg',
                'image',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => 'https://example.com/image.jpg',
                    'height' => null,
                    'width' => null,
                    'attributes' => [],
                ]
            ],
            [
                'https://example.com/image.jpeg',
                'image',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => 'https://example.com/image.jpeg',
                    'height' => null,
                    'width' => null,
                    'attributes' => [],
                ]
            ],
            [
                'https://example.com/image.png',
                'image',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => 'https://example.com/image.png',
                    'height' => null,
                    'width' => null,
                    'attributes' => [],
                ]
            ],
            [
                'https://example.com/image.svg',
                'image',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => 'https://example.com/image.svg',
                    'height' => null,
                    'width' => null,
                    'attributes' => [],
                ]
            ],
            [
                'https://example.com/image.tif',
                'image',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => 'https://example.com/image.tif',
                    'height' => null,
                    'width' => null,
                    'attributes' => [],
                ]
            ],
            [
                'https://example.com/image.tiff',
                'image',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => 'https://example.com/image.tiff',
                    'height' => null,
                    'width' => null,
                    'attributes' => [],
                ]
            ],
            [
                'https://i.imgur.com/foobar.gifv',
                'imgur',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'mediaID' => 'foobar'
                    ],
                ]
            ],
            [
                'https://www.instagram.com/p/foo-bar',
                'instagram',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'mediaID' => 'foo-bar'
                    ],
                ]
            ],
            [
                'https://instagram.com/p/foo-bar',
                'instagram',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'mediaID' => 'foo-bar'
                    ],
                ]
            ],
            [
                'https://instagr.am/p/foo-bar',
                'instagram',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'mediaID' => 'foo-bar'
                    ],
                ]
            ],
            [
                'https://www.pinterest.com/pin/1234567890',
                'pinterest',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'pinID' => '1234567890'
                    ],
                ]
            ],
            [
                'https://pinterest.com/pin/1234567890',
                'pinterest',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'pinID' => '1234567890'
                    ],
                ]
            ],
            [
                'https://soundcloud.com/example-user/foo-bar',
                'soundcloud',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'user' => 'example-user',
                        'track' => 'foo-bar'
                    ],
                ]
            ],
            [
                'https://twitch.tv/foobar',
                'twitch',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'channel' => 'foobar'
                    ],
                ]
            ],
            [
                'https://twitter.com/example-user/status/1234567890',
                'twitter',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'statusID' => '1234567890'
                    ],
                ]
            ],
            [
                'https://vimeo.com/1234567890',
                'vimeo',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => '1234567890'
                    ],
                ]
            ],
            [
                'https://vine.co/v/hzxpjd6b9d9',
                'vine',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => 'hzxpjd6b9d9'
                    ],
                ]
            ],
            [
                'https://example.wistia.com/medias/foobar',
                'wistia',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => 'foobar',
                        'time' => null
                    ],
                ]
            ],
            [
                'https://example.wistia.com/?wvideo=foobar',
                'wistia',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => 'foobar',
                        'time' => null
                    ],
                ]
            ],
            [
                'https://example.wi.st/?wvideo=foobar',
                'wistia',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => 'foobar',
                        'time' => null
                    ],
                ]
            ],
            [
                'https://example.wistia.com/medias/foobar',
                'wistia',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => 'foobar',
                        'time' => null
                    ],
                ]
            ],
            [
                'https://example.wi.st/medias/foobar',
                'wistia',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => 'foobar',
                        'time' => null
                    ],
                ]
            ],
            [
                'https://example.wistia.com/?wvideo=foobar&wtime=3m30s',
                'wistia',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => 'foobar',
                        'time' => '3m30s'
                    ],
                ]
            ],
            [
                'https://example.wistia.com/medias/foobar?wtime=3m30s',
                'wistia',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => 'foobar',
                        'time' => '3m30s'
                    ],
                ]
            ],
            [
                'https://www.youtube.com/watch?v=9bZkp7q19f0',
                'youtube',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                            'videoID' => '9bZkp7q19f0',
                            'listID' => null,
                            'start' => null,
                        ],
                ]
            ],
            [
                'https://youtu.be/9bZkp7q19f0',
                'youtube',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => '9bZkp7q19f0',
                        'listID' => null,
                        'start' => null,
                    ],
                ]
            ],
            [
                "https://www.youtube.com/watch?v=9bZkp7q19f0&t=3m2s",
                'youtube',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => '9bZkp7q19f0',
                        'listID' => null,
                        'start' => 182,
                    ],
                ]
            ],
            [
                "https://youtu.be/9bZkp7q19f0?t=3m2s",
                'youtube',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => null,
                    'height' => null,
                    'width' => null,
                    'attributes' => [
                        'videoID' => '9bZkp7q19f0',
                        'listID' => null,
                        'start' => 182,
                    ],
                ]
            ]
        ];

        return $urls;
    }

    /**
     * Set the "disable fetch" flag of the web scraper to avoid attempting to download documents.
     *
     * @param bool $disableFetch
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    private function setDisableFetch($disableFetch) {
        /** @var WebScraper $webScraper */
        $webScraper = static::container()->get(WebScraper::class);
        $webScraper->setDisableFetch($disableFetch);
    }
}
