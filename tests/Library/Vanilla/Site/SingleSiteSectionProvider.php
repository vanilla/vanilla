<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Site;

use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SingleSiteSectionProvider;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for SingleSiteSectionProvider.
 */
class SingleSiteSectionProviderTests extends MinimalContainerTestCase {

    const LOCALE_KEY = 'AB-df';

    /** @var SingleSiteSectionProvider */
    private $provider;

    /**
     * @inheritdoc
     */
    public function setUp() {
        parent::setUp();
        $this->setConfig('Garden.Locale', self::LOCALE_KEY);
        $this->provider = self::container()->get(SingleSiteSectionProvider::class);
    }

    /**
     * Test for the getAll.
     */
    public function testGetAll() {
        $this->assertCount(1, $this->provider->getAll());
        $this->assertInstanceOf(DefaultSiteSection::class, $this->provider->getAll()[0]);
    }

    /**
     * Test for the getForLocale.
     */
    public function testGetForLocale() {
        $this->assertCount(1, $this->provider->getForLocale(self::LOCALE_KEY));
        $this->assertCount(0, $this->provider->getForLocale('notlocalekey'));
    }

    /**
     * Test for the getByID.
     */
    public function getGetByID() {
        $this->assertInstanceOf(
            DefaultSiteSection::class,
            $this->provider->getByID(DefaultSiteSection::DEFAULT_ID)
        );

        $this->assertNull(
            $this->provider->getByID(5)
        );
    }

    /**
     * Test for the getByBasePath.
     */
    public function getByBasePath() {
        $this->assertInstanceOf(
            DefaultSiteSection::class,
            $this->provider->getByBasePath(DefaultSiteSection::EMPTY_BASE_PATH)
        );

        $this->assertNull(
            $this->provider->getByBasePath('asdf')
        );
    }
}
