<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Logging;

use PHPUnit\Framework\TestCase;
use Vanilla\Logging\LoggerUtils;

/**
 * Tests for the `LoggerUtils` class.
 */
class LoggerUtilsTest extends TestCase {

    /**
     * A basic nested toest for `LoggerUtils::stringinfyDates()`.
     */
    public function testStringifyDates(): void {
        $dt = new \DateTimeImmutable('2020-05-24');
        $str = $dt->format(\DateTime::ATOM);

        $this->assertSame(['a' => ['b' => $str]], LoggerUtils::stringifyDates(['a' => ['b' => $dt]]));
    }
}
