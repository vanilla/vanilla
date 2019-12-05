<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Formatting\Formats\NotFoundFormat;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the notfound format.
 */
class NotFoundFormatTest extends MinimalContainerTestCase {

    /**
     * Make sure we can construct the format it dying.
     */
    public function testNotFatal() {
        $format = new NotFoundFormat('FAKE_FORMAT');
        $this->assertInstanceOf(NotFoundFormat::class, $format);
    }

    /**
     * Test the various methods string returns methods of the formatter.
     *
     * @param string $methodName
     * @param string $expected
     *
     * @dataProvider methodTesterProvider
     */
    public function testMethods(string $methodName, $expected) {
        $format = new NotFoundFormat('FAKE_FORMAT');

        if ($expected instanceof \Exception) {
            $this->expectException($expected);
            $format->{$methodName}('');
        } else {
            $this->assertEquals($expected, $format->{$methodName}(''));
        }
    }

    /**
     * @return array
     */
    public function methodTesterProvider(): array {
        $stringError = "No formatter is installed for the format FAKE_FORMAT";
        $htmlError = <<<HTML
<div class='DismissMessage Warning userContent-error'>
           <span>
            <span class='icon icon-warning-sign userContent-errorIcon'><span class="sr-only">Warning</span></span>
            $stringError
       </span>
    </div>

HTML;

        return [
            ['renderHtml', $htmlError],
            ['renderQuote', $htmlError],
            ['renderExcerpt', $stringError],
            ['renderPlainText', $stringError],
            ['parseImageUrls', []],
            ['parseHeadings', []],
            ['parseAttachments', []],
            ['parseMentions', []],
        ];
    }
}
