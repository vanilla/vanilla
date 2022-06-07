<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Vanilla\Utility\DebugUtils;
use VanillaTests\VanillaTestCase;

/**
 * Tests for the `DebugUtils` class.
 */
class DebugUtilsTest extends VanillaTestCase
{
    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        DebugUtils::setDebug(false);
    }

    /**
     * Test a basic call to `DebugUtils::renderException()`.
     */
    public function testRenderException(): void
    {
        $ex = new \Exception("foo");

        $actual = DebugUtils::renderException($ex, "test", DebugUtils::WRAP_HTML_NONE);
        $expected = <<<EOT
test

foo
EOT;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test a basic call to `DebugUtils::renderException()`.
     */
    public function testRenderExceptionInDebug(): void
    {
        $ex = new \Exception("foo");

        DebugUtils::setDebug(true);
        $actual = DebugUtils::renderException($ex, "test", DebugUtils::WRAP_HTML_NONE);
        $expected = <<<EOT
test

foo
EOT;
        $this->assertStringStartsWith($expected, $actual);
        $this->assertStringContainsString(__FUNCTION__, $actual);
    }

    /**
     * A call to `DebugUtils::wrapHtmlComment()` should escape comment markers.
     */
    public function testWrapHtmlCommentEscaped(): void
    {
        $actual = DebugUtils::wrapMessage("foo --> <script>", DebugUtils::WRAP_HTML_COMMENT);
        $expected = <<<EOT

<!--
foo ~~> <script>
-->

EOT;

        $this->assertSame($expected, $actual);
    }

    /**
     * A call to `DebugUtils::wrapDebug()` should escape HTML.
     */
    public function testWrapDebugEscaped(): void
    {
        $actual = DebugUtils::wrapMessage("foo </pre> hack", DebugUtils::WRAP_HTML);
        $expected = <<<EOT

<pre class="debug-dontUseCssOnMe">foo &lt;/pre&gt; hack</pre>

EOT;

        $this->assertSame($expected, $actual);
    }

    /**
     * Smoke test `DebugUtils::stackTraceString()`.
     */
    public function testStackTraceString(): void
    {
        $this->assertSame("", DebugUtils::stackTraceString([], 2));

        $a = DebugUtils::stackTraceString(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 2);
        $this->assertCount(2, explode("\n", $a));

        try {
            throw new \Exception(__FUNCTION__);
        } catch (\Exception $ex) {
            $b = DebugUtils::stackTraceString($ex->getTrace(), 2);
        }
        $this->assertSame($a, $b);
    }

    /**
     * @return void
     */
    public function testIsTestMode(): void
    {
        $this->assertTrue(DebugUtils::isTestMode());
    }
}
