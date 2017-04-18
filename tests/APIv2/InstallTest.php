<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use Vanilla\AddonManager;
use VanillaTests\TestInstallModel;

/**
 * Test basic Vanilla installation.
 */
class InstallTest extends \PHPUnit_Framework_TestCase {
    /**
     * Test installing Vanilla with the {@link \Vanilla\Models\InstallModel}.
     */
    public function testInstall() {
        global $dic;

        $dic->setInstance(AddonManager::class, null);

        /* @var TestInstallModel $installer */
        $installer = $dic->get(TestInstallModel::class);

        $installer->uninstall();
        $result = $installer->install([
            'site' => ['title' => __METHOD__]
        ]);

        $this->assertArrayHasKey('version', $result);
        $this->assertGreaterThan(0, $result['adminUserID']);
    }
}
