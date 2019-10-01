<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Model;

use PHPUnit\Framework\TestCase;
use VanillaTests\BootstrapTrait;
use VanillaTests\TestInstallModel;

/**
 * Test basic Vanilla installation.
 */
class InstallTest extends TestCase {
    use BootstrapTrait {
        setupBeforeClass as private bootstrapBeforeClass;
        teardownAfterClass as private bootstrapAfterClass;
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass() {
        self::bootstrapBeforeClass();
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass() {
        /* @var TestInstallModel $installer */
        $installer = self::container()->get(TestInstallModel::class);
        $installer->uninstall();

        self::bootstrapAfterClass();
    }

    /**
     * Test installing Vanilla with the {@link \Vanilla\Models\InstallModel}.
     */
    public function testInstall() {
        /* @var TestInstallModel $installer */
        $installer = self::container()->get(TestInstallModel::class);

        $result = $installer->install([
            'site' => ['title' => __METHOD__]
        ]);

        $this->assertArrayHasKey('version', $result);
        $this->assertGreaterThan(0, $result['adminUserID']);

        /* @var \Gdn_Configuration $config */
        $config = self::container()->get(\Gdn_Configuration::class);
        $this->assertNotEmpty($config->get('Garden.UpdateToken'));
    }
}
