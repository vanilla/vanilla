<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Gdn_Upload;
use Garden\Http\HttpResponse;
use Vanilla\Attributes;
use Vanilla\Formatting\Embeds\EmbedManager;
use Vanilla\UploadedFile;
use VanillaTests\Fixtures\Uploader;

/**
 * Test the /api/v2/media endpoints.
 */
class MediaTest extends AbstractAPIv2Test {

    /** @var string */
    private $baseUrl = '/media';

    /**
     * Test updating a media item's attachment state.
     */
    public function testPatchAttachment() {
        $row = $this->testPost();
        $mediaID = $row["responseBody"]["mediaID"];

        // Attachments default as "embed" and to the current user. Try attaching to a discussion.
        $updatedAttachment = [
            "foreignID" => 1,
            "foreignType" => "discussion",
        ];
        $result = $this->api()->patch(
            "{$this->baseUrl}/{$mediaID}/attachment",
            $updatedAttachment
        );
        $this->assertArraySubset($updatedAttachment, $result->getBody());
    }

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
        $empty = new Attributes();
        $urls = [
            [
                'https://example.com/image.bmp',
                'image',
                [
                    'name' => null,
                    'body' => null,
                    'photoUrl' => 'https://example.com/image.bmp',
                    'height' => null,
                    'width' => null,
                    'attributes' => $empty,
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
                    'attributes' => $empty,
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
                    'attributes' => $empty,
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
                    'attributes' => $empty,
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
                    'attributes' => $empty,
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
                    'attributes' => $empty,
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
                    'attributes' => $empty,
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
                    'attributes' => $empty,
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
                        'videoID' => '1234567890',
                        'embedUrl' => 'https://player.vimeo.com/video/1234567890?autoplay=1'
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
                        'embedUrl' => 'https://www.youtube.com/embed/9bZkp7q19f0?feature=oembed&autoplay=1',
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
                        'embedUrl' => 'https://www.youtube.com/embed/9bZkp7q19f0?feature=oembed&autoplay=1',
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
                        'start' => 182,
                        'embedUrl' => 'https://www.youtube.com/embed/9bZkp7q19f0?feature=oembed&autoplay=1&start=182',
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
                        'start' => 182,
                        'embedUrl' => 'https://www.youtube.com/embed/9bZkp7q19f0?feature=oembed&autoplay=1&start=182',
                    ],
                ]
            ],
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
            ['https://vimeo.com/251083506', 'vimeo'],
            ['https://youtube.com/watch?v=example', 'youtube'],
            ['https://youtube.ca/watch?v=example', 'youtube'],
            ['https://www.instagram.com/p/BizC-PPFK1m', 'instagram'],
            ['https://soundcloud.com/syrebralvibes/the-eden-project-circles', 'soundcloud'],
            ['https://www.twitch.tv/videos/276279462', 'twitch'],
            ['https://clips.twitch.tv/SarcasticDependableCormorantBudStar', 'twitch'],
            ['https://clips.twitch.tv/SarcasticDependableCormorantBudStar', 'twitch'],
            ['https://imgur.com/gallery/10HROiq', 'imgur'],
            ['https://www.gettyimages.ca/license/905559076', 'getty'],
            ['https://www.gettyimages.ca/event/denmark-v-australia-group-c-2018-fifa-world-cup-russia-775137961#denmarks-forward-pione-sisto-controls-the-ball-during-the-russia-2018-picture-id980320266', 'getty'],
            ['https://www.gettyimages.com/license/460707851', 'getty'],
            ['http://gty.im/460707851', 'getty'],
            ['https://giphy.com/gifs/super-smash-bros-ultimate-jwSlQZnsymUW49NC3R', 'giphy'],
            ['https://gph.is/2sTbvh0', 'giphy'],
            ['https://media.giphy.com/media/2vqIyGV2S3HTGafbKo/giphy.gif', 'giphy'],
            ['https://vanillaforums-1.wistia.com/medias/vjidqnyg0a', 'wistia'],
            ['https://codepen.io/cchabilall83/pen/gKymEp', 'codepen'],
        ];

        return $urls;
    }

    /**
     * Test scraping pages with /media/scrape.
     *
     * @dataProvider provideScrapeUrls
     * @param array $url
     * @param string $type
     * @param array $info
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @large
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
            $this->assertEquals(json_encode($info[$key]), json_encode($value));
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
}
