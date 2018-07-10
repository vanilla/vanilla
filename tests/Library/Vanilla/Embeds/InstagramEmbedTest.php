<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Embeds;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Embeds\InstagramEmbed;

class InstagramEmbedTest extends SharedBootstrapTestCase {

    /**
     * Test the parseResponseHtmlTest method.
     *
     * @param array $data Html from Instagrams oembed response.
     * @param array $expected The parsed results expected.
     * @dataProvider urlProvider
     */
    public function parseResponseHtmlTest($data, $expected) {
        $instaGramEmbed = new InstagramEmbed();
        $parsedHtml = $instaGramEmbed->parseResponseHtml($data);
        $this->assertEquals($expected, $parsedHtml);
    }

    /**
     * Data Provider for parseResponseHtmltest.
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
                        'version' => '8',
                    ],
                ],
            ],
        ];

        return $data;
    }
}