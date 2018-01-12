<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Model;

use PHPUnit\Framework\TestCase;
use Garden\Container\Container;
use VanillaTests\Bootstrap;
use VanillaTests\TestInstallModel;

/**
 * Test basic Vanilla installation.
 */
class InstallTest extends TestCase {
    /**
     * Test installing Vanilla with the {@link \Vanilla\Models\InstallModel}.
     */
    public function testInstall() {
        $bootstrap = new Bootstrap();
        $dic = new Container();
        $bootstrap->run($dic);


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
