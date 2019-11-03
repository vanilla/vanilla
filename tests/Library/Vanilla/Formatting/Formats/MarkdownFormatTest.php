<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use PHP_CodeSniffer\Standards\MySource\Tests\PHP\EvalObjectFactoryUnitTest;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Formats\MarkdownFormat;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;

/**
 * Tests for the MarkdownFormat.
 */
class MarkdownFormatTest extends AbstractFormatTestCase {

    /**
     * @inheritDoc
     */
    protected function prepareFormatter(): FormatInterface {
        return self::container()->get(MarkdownFormat::class);
    }

    /**
     * @inheritDoc
     */
    protected function prepareFixtures(): array {
        return (new FormatFixtureFactory('markdown'))->getAllFixtures();
    }

    /**
     * Test disallowing spoilers within a quote.
     */
    public function testMarkdownSpoilerBug() {
        $md = <<<EOT
> [spoiler]
> 
> [/spoiler]
EOT;
        $expected = <<<EOT
<blockquote class="UserQuote"><div class="QuoteText">
  <p>[spoiler]</p>
  
  <p>[/spoiler]</p>
</div></blockquote>

EOT;

        $formatter = $this->prepareFormatter();
        $actual = $formatter->renderHTML($md);

        $this->assertEquals($expected, $actual);
    }

    public function testMultilineSpoiler() {
        $md = <<<EOT
[spoiler]
s
[/spoiler]
EOT;

        $expected = <<<EOT
<div class="Spoiler">
s
</div>

EOT;

        $formatter = $this->prepareFormatter();
        $actual = $formatter->renderHTML($md);

        $this->assertEquals($expected, $actual);
    }
}
