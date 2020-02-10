<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Site;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SingleSiteSectionProvider;
use VanillaTests\MinimalContainerTestCase;
use Vanilla\Site\SiteSectionModel;

/**
 * Tests for SingleSiteSectionProvider.
 */
class SingleSiteSectionProviderTest extends MinimalContainerTestCase {

    const LOCALE_KEY = 'en_US';

    /** @var SiteSectionModel $model */
    private static $siteSectionModel;

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->setConfig('Garden.Locale', self::LOCALE_KEY);
        $config = self::container()->get(ConfigurationInterface::class);
        $router = self::container()->get(\Gdn_Router::class);
        $provider = new SingleSiteSectionProvider(new DefaultSiteSection($config, $router));
        static::container()->setInstance(SiteSectionProviderInterface::class, $provider);
        $model = new SiteSectionModel($config, $router);
        $model->addProvider($provider);
        self::$siteSectionModel = $model;
    }

    /**
     * Test for the getAll.
     */
    public function testGetAll() {
        $this->assertCount(1, self::$siteSectionModel->getAll());
        $this->assertInstanceOf(DefaultSiteSection::class, self::$siteSectionModel->getAll()[0]);
    }

    /**
     * Test for the getForLocale.
     */
    public function testGetForLocale() {
        $this->assertCount(1, self::$siteSectionModel->getForLocale(self::LOCALE_KEY));
        $this->assertCount(0, self::$siteSectionModel->getForLocale('notlocalekey'));
    }

    /**
     * Test for the getByBasePath.
     */
    public function getByBasePath() {
        $this->assertInstanceOf(
            DefaultSiteSection::class,
            self::$siteSectionModel->getByBasePath(DefaultSiteSection::EMPTY_BASE_PATH)
        );

        $this->assertNull(
            self::$siteSectionModel->getByBasePath('asdf')
        );
    }
}
