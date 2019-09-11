<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Site;

use Vanilla\Site\DefaultSiteSection;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for DefaultSiteSection.
 */
class DefaultSiteSectionTest extends MinimalContainerTestCase {

    /**
     * Test that values are correctly pulled from the config.
     */
    public function testPullsConfigValues() {
        $expectedName = "test Expected title!!";
        $expectedLocale = "pt-br";
        $this->setConfigs([
            'Garden.Title' => $expectedName,
            'Garden.Locale' => $expectedLocale,
        ]);

        /** @var DefaultSiteSection $section */
        $section = self::container()->get(DefaultSiteSection::class);
        $this->assertEquals($expectedName, $section->getSectionName());
        $this->assertEquals($expectedLocale, $section->getContentLocale());
    }
}
