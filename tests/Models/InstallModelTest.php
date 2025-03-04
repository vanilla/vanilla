<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Model;

use VanillaTests\SiteTestCase;
use VanillaTests\TestInstallModel;

/**
 * Test basic Vanilla installation.
 */
class InstallModelTest extends SiteTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass(): void
    {
        /* @var TestInstallModel $installer */
        $installer = self::container()->get(TestInstallModel::class);
        $installer->uninstall();
    }

    /**
     * Test installing Vanilla with the {@link \Vanilla\Models\InstallModel}.
     */
    public function testInstall()
    {
        /* @var TestInstallModel $installer */
        $installer = self::container()->get(TestInstallModel::class);

        $result = $installer->install([
            "site" => ["title" => __METHOD__],
        ]);

        $this->assertArrayHasKey("version", $result);
        $this->assertGreaterThan(0, $result["adminUserID"]);

        /* @var \Gdn_Configuration $config */
        $config = self::container()->get(\Gdn_Configuration::class);
        $this->assertNotEmpty($config->get("Garden.UpdateToken"));
    }
}
