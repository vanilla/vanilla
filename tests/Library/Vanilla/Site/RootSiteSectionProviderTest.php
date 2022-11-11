<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Site;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Site\RootSiteSection;
use Vanilla\Site\RootSiteSectionProvider;
use VanillaTests\MinimalContainerTestCase;
use Vanilla\Site\SiteSectionModel;

/**
 * Tests for RootSiteSectionProvider.
 */
class RootSiteSectionProviderTest extends MinimalContainerTestCase
{
    const LOCALE_KEY = "en_US";

    /** @var SiteSectionModel $model */
    private static $siteSectionModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setConfig("Garden.Locale", self::LOCALE_KEY);
        $config = self::container()->get(ConfigurationInterface::class);
        $router = self::container()->get(\Gdn_Router::class);
        $provider = new RootSiteSectionProvider(new RootSiteSection($config, $router));
        $this->rootSiteSectionProvider = $provider;
        static::container()->setInstance(SiteSectionProviderInterface::class, $provider);
        $model = new SiteSectionModel($config, $router);
        $model->addProvider($provider);
        self::$siteSectionModel = $model;
    }

    /**
     * Test for the getAll.
     */
    public function testGetAll()
    {
        $this->assertCount(1, self::$siteSectionModel->getAll());
        $this->assertInstanceOf(RootSiteSection::class, self::$siteSectionModel->getAll()[0]);
    }

    /**
     * Test for the getForLocale.
     */
    public function testGetForLocale()
    {
        $this->assertCount(1, self::$siteSectionModel->getForLocale(self::LOCALE_KEY));
        $this->assertCount(0, self::$siteSectionModel->getForLocale("notlocalekey"));
    }

    /**
     * Test for the getByBasePath.
     */
    public function testGetByBasePath()
    {
        $this->assertInstanceOf(
            RootSiteSection::class,
            self::$siteSectionModel->getByBasePath(RootSiteSection::EMPTY_BASE_PATH)
        );

        $this->assertNull(self::$siteSectionModel->getByBasePath("asdf"));
    }
}
