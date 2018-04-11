<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2;

use Gdn_Upload;
use Garden\Http\HttpResponse;
use Vanilla\UploadedFile;
use VanillaTests\Fixtures\Uploader;
use WebScraper;

/**
 * Test the /api/v2/media endpoints.
 */
class MediaTest extends AbstractAPIv2Test {

    /** @var string */
    private $baseUrl = '/media';

    /**
     * Test posting.
     *
     * @return array ['uploadedFile' => UploadedFile, 'responseBody' => $body]
     */
    public function testPost() {
        Uploader::resetUploads();
        $photo = Uploader::uploadFile('photo', PATH_ROOT.'/tests/fixtures/apple.jpg');

        $row = [
            'file' => $photo,
            'type' => 'image',
        ];
        $result = $this->api()->post(
            $this->baseUrl,
            $row
        );

        $this->assertEquals(201, $result->getStatusCode());

        $this->validateMedia($photo, $result);

        return [
            'uploadedFile' => $photo,
            'responseBody' => $result->getBody(),
        ];
    }

    /**
     * Get a media.
     */
    public function testGet() {
        $postResult = $this->testPost();
        $mediaID = $postResult['responseBody']['mediaID'];

        $result = $this->api()->get($this->baseUrl.'/'.$mediaID);

        $this->assertEquals(200, $result->getStatusCode());

        $this->validateMedia($postResult['uploadedFile'], $result);
    }

    /**
     * Get a media by URL.
     */
    public function testGetByUrl() {
        $postResult = $this->testPost();

        $result = $this->api()->get($this->baseUrl.'/by-url', ['url' => $postResult['responseBody']['url']]);

        $this->assertEquals(200, $result->getStatusCode());

        $this->validateMedia($postResult['uploadedFile'], $result);
    }

    /**
     * Delete a media.
     */
    public function testDelete() {
        $postResult = $this->testPost();
        $mediaID = $postResult['responseBody']['mediaID'];

        $result = $this->api()->delete($this->baseUrl.'/'.$mediaID);

        $this->assertEquals(204, $result->getStatusCode());

        try {
            $this->api()->get("{$this->baseUrl}/$mediaID");
            $this->fail("The media did not get deleted.");
        } catch (\Exception $ex) {
            $this->assertEquals(404, $ex->getCode());
            return;
        }
        $this->fail("Something odd happened while deleting a media.");
    }

    /**
     * Delete a media by URL.
     */
    public function testDeleteByURL() {
        $postResult = $this->testPost();
        $mediaID = $postResult['responseBody']['mediaID'];

        $result = $this->api()->delete($this->baseUrl.'/by-url', ['url' => $postResult['responseBody']['url']]);

        $this->assertEquals(204, $result->getStatusCode());

        try {
            $this->api()->get("{$this->baseUrl}/$mediaID");
            $this->fail("The media did not get deleted.");
        } catch (\Exception $ex) {
            $this->assertEquals(404, $ex->getCode());
            return;
        }
        $this->fail("Something odd happened while deleting a media.");
    }

    /**
     * Validate a media.
     *
     * @param UploadedFile $uploadedFile
     * @param HttpResponse $result
     */
    private function validateMedia(UploadedFile $uploadedFile, HttpResponse $result) {
        $body = $result->getBody();
        $this->assertInternalType('array', $body);

        $this->assertArrayHasKey('mediaID', $body);
        $this->assertTrue(is_int($body['mediaID']));

        $urlPrefix = Gdn_Upload::urls('');
        $this->assertArrayHasKey('url', $body);
        $this->assertStringStartsWith($urlPrefix, $body['url']);
        $filename = PATH_UPLOADS.substr($body['url'], strlen($urlPrefix));

        $this->assertArrayHasKey('name', $body);
        $this->assertEquals($uploadedFile->getClientFilename(), $body['name']);

        $this->assertArrayHasKey('type', $body);
        $this->assertEquals($uploadedFile->getClientMediaType(), $body['type']);

        $this->assertArrayHasKey('size', $body);
        $this->assertEquals($uploadedFile->getSize(), $body['size']);

        $this->assertArrayHasKey('width', $body);
        $this->assertArrayHasKey('height', $body);
        if (strpos($body['type'], 'image/') === 0) {
            $imageInfo = getimagesize($filename);
            $this->assertEquals($imageInfo[0], $body['width']);
            $this->assertEquals($imageInfo[1], $body['height']);
        } else {
            $this->assertEquals(null, $body['width']);
            $this->assertEquals(null, $body['height']);
        }

        $this->assertArrayHasKey('foreignType', $body);
        $this->assertEquals('embed', $body['foreignType']);

        $this->assertArrayHasKey('foreignID', $body);
        $this->assertEquals(self::$siteInfo['adminUserID'], $body['foreignID']);
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
     * Provide data to verify a parsed URL matches a type.
     *
     * @return array
     */
    public function provideTypeUrls() {
        $urls = [
            ['https://vine.co/v/abc123', 'vine'],
            ['https://embed.gettyimages.com/embed/1234567890', 'getty'],
            ['https://imgur.com/example', 'imgur'],
            ['https://imgur.com/example.jpg', 'imgur'],
            ['https://i.imgur.com/example', 'imgur'],
            ['https://m.imgur.com/example', 'imgur'],
            ['https://www.pinterest.com/pin/1234567890', 'pinterest'],
            ['https://vimeo.com/251083506', 'vimeo'],
            ['https://youtube.com/watch?v=example', 'youtube'],
            ['https://youtube.ca/watch?v=example', 'youtube']
        ];

        return $urls;
    }

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
     * Verify a URL matches a specific type.
     *
     * @param string $url
     * @param string $type
     * @dataProvider provideTypeUrls
     */
    public function testUrlType($url, $type) {
        $result = $this->api()->post('media/scrape', ['url' => $url]);
        $this->assertEquals(201, $result->getStatusCode());

        $body = $result->getBody();
        $this->assertEquals($url, $body['url']);
        $this->assertEquals($type, $body['type']);
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
