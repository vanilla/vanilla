<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Site;

use Vanilla\Site\RootSiteSection;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for RootSiteSection.
 */
class RootSiteSectionTest extends MinimalContainerTestCase
{
    /**
     * Test that values are correctly pulled from the config.
     */
    public function testPullsConfigValues()
    {
        $expectedName = "test Expected title!!";
        $expectedLocale = "pt-br";
        $this->setConfigs([
            "Garden.Title" => $expectedName,
            "Garden.Locale" => $expectedLocale,
        ]);

        /** @var RootSiteSection $section */
        $section = self::container()->get(RootSiteSection::class);
        $this->assertEquals($expectedName, $section->getSectionName());
        $this->assertEquals($expectedLocale, $section->getContentLocale());
    }

    /**
     * Test that RootSiteSection's `getLayoutIdLookupParams()` function returns the appropriate values.
     */
    public function testGetLayoutIdLookupParams()
    {
        $expectedValues = [
            "layoutViewType" => "home",
            "recordType" => "root",
            "recordID" => RootSiteSection::DEFAULT_CATEGORY_ID,
        ];

        $returnedValues = self::container()
            ->get(RootSiteSection::class)
            ->getLayoutIdLookupParams("home", "anything", "anything");

        $this->assertEquals($expectedValues, $returnedValues);
    }

    /**
     * Test that RootSiteSection's `getAttributes()` function returns the appropriate values.
     */
    public function testGetAttributes()
    {
        $attributes = self::container()
            ->get(RootSiteSection::class)
            ->getAttributes();

        $this->assertEquals(["categoryID" => RootSiteSection::DEFAULT_CATEGORY_ID], $attributes);
    }

    /**
     * Test that RootSiteSection's `getSectionID()` function returns the appropriate value.
     */
    public function testGetSectionID()
    {
        $this->assertEquals(
            0,
            self::container()
                ->get(RootSiteSection::class)
                ->getSectionID()
        );
    }

    /**
     * Test that RootSiteSection's `getCategoryID()` function returns the appropriate value.
     */
    public function testGetCategoryID()
    {
        $this->assertEquals(
            -2,
            self::container()
                ->get(RootSiteSection::class)
                ->getCategoryID()
        );
    }

    /**
     * Test that RootSiteSection's `getSectionGroup()` function returns the appropriate value.
     */
    public function testGetSectionGroup()
    {
        $this->assertEquals(
            RootSiteSection::DEFAULT_SECTION_GROUP,
            self::container()
                ->get(RootSiteSection::class)
                ->getSectionGroup()
        );
    }

    /**
     * Test that RootSiteSection's `getSectionThemeID()` function returns the appropriate value.
     */
    public function testGetSectionThemeID()
    {
        $this->assertEquals(
            null,
            self::container()
                ->get(RootSiteSection::class)
                ->getSectionThemeID()
        );
    }
}
