<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\Formatting\FormatCompatibilityService;
use Vanilla\Formatting\Formats\RichFormat;
use VanillaTests\Fixtures\EmbeddedContent\LegacyEmbedFixtures;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Test for FormatCompatibilityServiceTest.
 */
class FormatCompatibilityServiceTest extends SharedBootstrapTestCase {

    /**
     * Tests for converting of old rich embed formats.
     */
    public function testRichEmbeds() {
        $discussionData = json_decode(LegacyEmbedFixtures::discussion(), true);
        $commentData = json_decode(LegacyEmbedFixtures::comment(), true);
        $normalInsert = [
            'insert' => 'Hello world\n\n\n\n',
        ];
        $initialValue = [
            $this->makeEmbedInsert($discussionData),
            $normalInsert,
            $this->makeEmbedInsert($commentData),
            $normalInsert,
        ];

        $expected = [
            $this->makeEmbedInsert(new QuoteEmbed($discussionData)),
            $normalInsert,
            $this->makeEmbedInsert(new QuoteEmbed($commentData)),
            $normalInsert,
        ];

        /** @var FormatCompatibilityService $service */
        $service = \Gdn::getContainer()->get(FormatCompatibilityService::class);

        $this->assertEquals(json_encode($expected), $service->convert(json_encode($initialValue), RichFormat::FORMAT_KEY));
    }

    /**
     * Validate that non-rich formats are passed through unmodified.
     *
     * @param string $format
     *
     * @dataProvider formatProvider
     */
    public function testFormatPassthrough(string $format) {
        $garbageInput = "asd;fjkasdlasdf<Script></Script>fjnvz.kjm,cnv;=<ap>4asdfasdxf</ap>";
        /** @var FormatCompatibilityService $service */
        $service = \Gdn::getContainer()->get(FormatCompatibilityService::class);
        $this->assertEquals($garbageInput, $service->convert($garbageInput, $format));
    }

    /**
     * @return array
     */
    public function formatProvider(): array {
        return [
            ['non-existant-format'],
            ['BBCode'],
            ['Markdown'],
            ['Text'],
            ['TextEx'],
            ['Html'],
            ['Wysiwyg'],
        ];
    }

    /**
     * Utilty to create an embed insert.
     *
     * @param mixed $data
     * @return array
     */
    private function makeEmbedInsert($data): array {
        $value = [
            'insert' => [
                'embed-external' => [
                    'data' => $data,
                ],
                'other-garbage' => true,
            ],
            'other-garbage' => true,
        ];
        return $value;
    }
}
