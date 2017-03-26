<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

use VanillaTests\TestInstallModel;

class InstallTest extends \PHPUnit_Framework_TestCase {
    public function testInstall() {
        global $dic;

        /* @var TestInstallModel $installer */
        $installer = $dic->get(TestInstallModel::class);

        $installer->uninstall();
        $installer->install([
            'site' => ['title' => __METHOD__]
        ]);
    }
}
