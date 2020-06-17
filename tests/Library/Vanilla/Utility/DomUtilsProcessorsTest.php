<?php


namespace VanillaTests\Library\Vanilla\Utility;


use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Html\Processor\StripImagesProcessor;

class DomUtilsProcessorsTest extends DomUtilsTest {

    /**
     * {@inheritDoc}
     */
    public function testStripImages(string $input, string $expected): void {
        $dom = new HtmlDocument($input);

        // This assertion tests against bugs in the HtmlDocument class itself.
        $this->assertHtmlStringEqualsHtmlString($input, $dom->getInnerHtml(), "The HtmlDocument didn't parse the string properly.");

        $dom->applyProcessors([StripImagesProcessor::class]);

        $actual = $dom->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }
}