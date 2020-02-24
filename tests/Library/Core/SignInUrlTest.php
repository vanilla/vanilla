<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for signInUrl()
 */
class SignInUrlTest extends TestCase {

    use SiteTestTrait;

    /**
     * Test target parameter.
     *
     * @param string $target
     * @param string $expected
     * @dataProvider provideSignInUrlTargets
     */
    public function testSignInUrlTarget(string $target, string $expected): void {
        $actual = signInUrl($target);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide targets for verifying signInUrl handling.
     *
     * @return array
     */
    public function provideSignInUrlTargets(): array {
        $result = [
            ["foo", "/entry/signin?Target=foo"],
            ["entry/register", "/entry/signin"],
            ["discussion/1/the-word-entry-is-in-this-discussion-name", "/entry/signin?Target=discussion%2F1%2Fthe-word-entry-is-in-this-discussion-name"],
        ];
        return $result;
    }
}
