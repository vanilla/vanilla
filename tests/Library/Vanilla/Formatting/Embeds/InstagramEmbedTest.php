<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Embeds;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Formatting\Embeds\InstagramEmbed;

class InstagramEmbedTest extends SharedBootstrapTestCase {

    /**
     * Test the parseResponseHtml method.
     *
     * @param array $data Html from Instagrams oembed response.
     * @param array $expected The parsed results expected.
     * @dataProvider htmlProvider
     */
    public function testParseResponseHtml($data, $expected) {
        $instaGramEmbed = new InstagramEmbed();
        $parsedHtml = $instaGramEmbed->parseResponseHtml($data['sampleHTML']);
        $this->assertEquals($expected, $parsedHtml);
    }

    /**
     * Data Provider for testParseResponseHtml.
     *
     * @return array $data
     */
    public function htmlProvider() {
        $data = [
            [
                [
                    'sampleHTML' => '<blockquote class="instagram-media" data-instgrm-captioned data-instgrm-permalink="https://www.instagram.com/p/BlA5FivjLw6/" data-instgrm-version="8"',
                ],
                [
                    'attributes' => [
                        'permaLink' => 'https://www.instagram.com/p/BlA5FivjLw6',
                        'isCaptioned' => true,
                        'versionNumber' => '8',
                    ],
                ],
            ],
            [
                [
                    'sampleHTML' => '<blockquote class="instagram-media" data-instgrm-captioned data-instgrm-permalink="https://www.instagram.com/p/BlBgZY7Ayto/" data-instgrm-version="8"',
                ],
                [
                    'attributes' => [
                        'permaLink' => 'https://www.instagram.com/p/BlBgZY7Ayto',
                        'isCaptioned' => true,
                        'versionNumber' => '8',
                    ],
                ],
            ],
        ];

        return $data;
    }
}
