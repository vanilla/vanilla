<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use Gdn_Configuration;
use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for signInUrl()
 */
class SignInUrlTest extends TestCase {

    use SiteTestTrait {
        SiteTestTrait::setUpBeforeClass as siteSetUpBeforeClass;
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void {
        self::siteSetUpBeforeClass();

        /** @var Gdn_Configuration $config */
        $config = self::container()->get(Gdn_Configuration::class);
    }

    /**
     * Test where target starts with 'entry'.
     */
    public function testSignInUrlWithEntryTarget() {
        $expected = '/entry/signin';
        $actual = signInUrl('entry/autosignedout');
        $this->assertSame($expected, $actual);
    }

    /**
     * Test where target doesn't contain 'entry'.
     */
    public function testSignInUrlWithNoEntry() {
        $expected = '/entry/signin?Target=foo';
        $actual = signInUrl('foo');
        $this->assertSame($expected, $actual);
    }
}
