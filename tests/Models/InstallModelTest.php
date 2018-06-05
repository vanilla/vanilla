<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Model;

use PHPUnit\Framework\TestCase;
use VanillaTests\BootstrapTrait;
use VanillaTests\TestInstallModel;

/**
 * Test basic Vanilla installation.
 */
class InstallTest extends TestCase {
    use BootstrapTrait;

    /**
     * Test installing Vanilla with the {@link \Vanilla\Models\InstallModel}.
     */
    public function testInstall() {
        /* @var TestInstallModel $installer */
        $installer = self::container()->get(TestInstallModel::class);

        $installer->uninstall();
        $result = $installer->install([
            'site' => ['title' => __METHOD__]
        ]);

        $this->assertArrayHasKey('version', $result);
        $this->assertGreaterThan(0, $result['adminUserID']);
    }
}
