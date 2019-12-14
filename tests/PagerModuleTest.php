<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PagerModule;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the `PagerModule`.
 */
class PagerModuleTest extends TestCase {
    use SiteTestTrait;

    /**
     * A simple slash should always format properly.
     */
    public function testFormatUrlSlash(): void {
        $url = PagerModule::formatUrl('/', '');
        $this->assertSame('/', $url);
    }

    /**
     * An empty page parameter should trim a trailing slash.
     */
    public function testFormatUrlEmptyPage(): void {
        $url = PagerModule::formatUrl('/discussions/{Page}', '');
        $this->assertSame('/discussions', $url);
    }

    /**
     * If the URL format has a trailing slash then it should not be trimmed.
     */
    public function testFormatUrlTrailing(): void {
        $url = PagerModule::formatUrl('/discussions/', '');
        $this->assertSame('/discussions/', $url);
    }

    /**
     * The page parameter should show up in a formatted URL.
     */
    public function testFormatUrlRegular(): void {
        $url = PagerModule::formatUrl('/discussions/{Page}', 'p2');
        $this->assertSame('/discussions/p2', $url);
    }
}
