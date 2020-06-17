<?php
/**
 * @author Dani M. <dani.m@vanillaforums.com>
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Html\Processor\StripEmbedsProcessor;
use Vanilla\Formatting\Html\Processor\StripImagesProcessor;
use Vanilla\Formatting\Html\Processor\TrimWordsProcessor;

/**
 * Class DomUtilsProcessorsTest for testing DomUtils processors
 * @package VanillaTests\Library\Vanilla\Utility
 */
class DomUtilsProcessorsTest extends DomUtilsTest {


    /**
     * Test striping images with variable expected
     *
     * @param string $input
     * @param string $expected
     * @dataProvider provideStripImagesTests
     */
    public function testStripImages(string $input, string $expected): void {
        $dom = new HtmlDocument($input);

        // This assertion tests against bugs in the HtmlDocument class itself.
        $this->assertHtmlStringEqualsHtmlString($input, $dom->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");

        $dom->applyProcessors([StripImagesProcessor::class]);

        $actual = $dom->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test striping embeds
     *
     * @param string $input
     * @param string $expected
     * @dataProvider provideStripEmbedsTests
     */
    public function testStripEmbeds(string $input, string $expected): void {
        $dom = new HtmlDocument($input);

        // This assertion tests against bugs in the HtmlDocument class itself.
        $this->assertHtmlStringEqualsHtmlString($input, $dom->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");

        $dom->applyProcessors([StripEmbedsProcessor::class]);
        $actual = $dom->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test truncating words.
     *
     * @param int $wordCount
     * @param string $html
     * @param string $expected
     * @dataProvider provideTrimWordsTests
     */
    public function testTrimWords($wordCount, $html, $expected) {
        $domDocument = new HtmlDocument($html);

        // This assertion tests against bugs in the HtmlDocument class itself.
        $this->assertHtmlStringEqualsHtmlString($html, $domDocument->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");

        $domDocument->applyProcessors([TrimWordsProcessor::class]);
        $actual = $domDocument->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Provide tests for `TestTruncateWords()`.
     *
     * @return array
     */
    public function provideTrimWordsTests(): array {
        $r = [
            'text100Words' => [
                100,
                'Maecenas sed nisl maximus, commodo ante sit amet, elementum augue. In semper molestie odio eu gravida. '.
                'Pellentesque accumsan, dolor vitae scelerisque varius, nisi neque tristique ligula, at molestie metus '.
                'massa egestas enim. Aenean vel elit ipsum. Curabitur sit amet leo et urna pulvinar egestas in quis arcu. '.
                'Mauris lacus tellus, dignissim eu facilisis id, vulputate a felis. Vestibulum ante ipsum primis in faucibus '.
                'orci luctus et ultrices posuere cubilia curae; Curabitur gravida odio ut orci mattis suscipit. Integer quis '.
                'massa porttitor, rhoncus leo volutpat, rutrum augue. In at massa non neque posuere pretium ut in ex. Ut elementum, '.
                'tellus non vehicula',
                'Maecenas sed nisl maximus, commodo ante sit amet, elementum augue. In semper molestie odio eu gravida. '.
                'Pellentesque accumsan, dolor vitae scelerisque varius, nisi neque tristique ligula, at molestie metus '.
                'massa egestas enim. Aenean vel elit ipsum. Curabitur sit amet leo et urna pulvinar egestas in quis arcu. '.
                'Mauris lacus tellus, dignissim eu facilisis id, vulputate a felis. Vestibulum ante ipsum primis in faucibus '.
                'orci luctus et ultrices posuere cubilia curae; Curabitur gravida odio ut orci mattis suscipit. Integer quis '.
                'massa porttitor, rhoncus leo volutpat, rutrum augue. In at massa non neque posuere pretium ut in ex. Ut elementum, '.
                'tellus non'
            ],
            'textNested100words' => [
                100,
                '<p>Maecenas sed nisl maximus, commodo ante sit amet, elementum augue. In semper molestie odio eu gravida. '.
                'Pellentesque accumsan, dolor vitae scelerisque varius, nisi neque tristique ligula, at molestie metus '.
                'massa egestas enim. Aenean vel elit ipsum. Curabitur sit amet leo et urna pulvinar egestas in quis arcu. '.
                'Mauris lacus tellus, dignissim eu facilisis id, vulputate a felis. Vestibulum ante ipsum primis in faucibus '.
                'orci luctus et ultrices posuere cubilia curae; Curabitur gravida odio ut orci mattis suscipit. Integer quis '.
                'massa porttitor, rhoncus leo volutpat, rutrum augue. In at massa non neque posuere pretium ut in ex. Ut elementum, '.
                'tellus non vehicula</p>',
                '<p>Maecenas sed nisl maximus, commodo ante sit amet, elementum augue. In semper molestie odio eu gravida. '.
                'Pellentesque accumsan, dolor vitae scelerisque varius, nisi neque tristique ligula, at molestie metus '.
                'massa egestas enim. Aenean vel elit ipsum. Curabitur sit amet leo et urna pulvinar egestas in quis arcu. '.
                'Mauris lacus tellus, dignissim eu facilisis id, vulputate a felis. Vestibulum ante ipsum primis in faucibus '.
                'orci luctus et ultrices posuere cubilia curae; Curabitur gravida odio ut orci mattis suscipit. Integer quis '.
                'massa porttitor, rhoncus leo volutpat, rutrum augue. In at massa non neque posuere pretium ut in ex. Ut elementum, '.
                'tellus non</p>'
            ],
            'textHeavilyNested100words' => [
                100,
                '<div><div><p>Maecenas sed nisl maximus, commodo ante sit amet, elementum augue. In semper molestie odio eu gravida. '.
                'Pellentesque accumsan, dolor vitae scelerisque varius, nisi neque tristique ligula, at molestie metus '.
                'massa egestas enim. Aenean vel elit ipsum. Curabitur sit amet leo et urna pulvinar egestas in quis arcu. '.
                'Mauris lacus tellus, dignissim eu facilisis id, vulputate a felis. Vestibulum ante ipsum primis in faucibus '.
                'orci luctus et ultrices posuere cubilia curae; Curabitur gravida odio ut orci mattis suscipit. Integer quis '.
                'massa porttitor, rhoncus leo volutpat, rutrum augue. In at massa non neque posuere pretium ut in ex. Ut elementum, '.
                'tellus non vehicula</p></div></div>',
                '<div><div><p>Maecenas sed nisl maximus, commodo ante sit amet, elementum augue. In semper molestie odio eu gravida. '.
                'Pellentesque accumsan, dolor vitae scelerisque varius, nisi neque tristique ligula, at molestie metus '.
                'massa egestas enim. Aenean vel elit ipsum. Curabitur sit amet leo et urna pulvinar egestas in quis arcu. '.
                'Mauris lacus tellus, dignissim eu facilisis id, vulputate a felis. Vestibulum ante ipsum primis in faucibus '.
                'orci luctus et ultrices posuere cubilia curae; Curabitur gravida odio ut orci mattis suscipit. Integer quis '.
                'massa porttitor, rhoncus leo volutpat, rutrum augue. In at massa non neque posuere pretium ut in ex. Ut elementum, '.
                'tellus non</p></div></div>'
            ],
        ];

        return $r;
    }
}
