<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Formatting;

use PHPUnit\Framework\TestCase;
use Vanilla\Formatting\FormatRegexReplacements;

class FormatRegexTest extends TestCase
{
    /**
     * Tests adding pattern-replacement pairs and calling replace.
     *
     * @return void
     */
    public function testAddReplace()
    {
        $regex = new FormatRegexReplacements();
        $regex->addReplacement("/ba/", "da");
        $regex->addReplacement("/ha/", "he");
        $body = "bababa hahaha";
        $this->assertSame("dadada hehehe", $regex->replace($body));
    }
}
