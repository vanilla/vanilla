<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;

/**
 * Add some basic tests for the `InstallModel` class.
 */
class InstallModelTest extends TestCase {
    use SiteTestTrait;

    /**
     * {@inheritDoc}
     */
    public static function setupBeforeClass(): void {
        // Do nothing to override the trait.
    }

    /**
     * Smoke test a site installation.
     */
    public function testInstall(): void {
        $this->assertEmpty(self::$siteInfo);
        $this->setupBeforeClassSiteTestTrait();
        $this->assertNotEmpty(self::$siteInfo);
    }
}
