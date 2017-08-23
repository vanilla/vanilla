<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;

use Ebi\Ebi;
use PHPUnit\Framework\TestCase;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\EbiBridge;
use Vanilla\EbiTemplateLoader;

class EbiIntegrationTest extends TestCase {
    /**
     * Clear the cache before doing tests.
     */
    public static function setUpBeforeClass() {
        \Gdn_FileSystem::removeFolder(PATH_ROOT.'/tests/cache/am');
    }

    public function testLoadThemedComponent() {
        $loader = $this->createTemplateLoader();

        $template = $loader->load('test:hello');
        $this->assertEquals("<b>Hello Theme</b>\n", $template);
    }

    public function testLoadExplicitComponent() {
        $loader = $this->createTemplateLoader();

        $template = $loader->load('test-addon:hello');
        $this->assertEquals("<b>Hello Addon</b>\n", $template);
    }

    public function testLoadParentComponent() {
        $loader = $this->createTemplateLoader();

        $template = $loader->load('what');
        $this->assertEquals("Basic Parent What\n", $template);
    }

    public function testHelperLoading() {
        $bridge = $this->createBridge();
        /* @var EbiTemplateLoader $loader */
        $loader = $bridge->getEbi()->getTemplateLoader();
        $testAddon = $loader->getAddonManager()->lookupAddon('test');
        $this->assertNotNull($testAddon);

        $viewPath = $testAddon->path('/views/test-helpers.html');
        $this->expectOutputString('<div>Just Test</div><div>Parent</div><div>Child</div>');
        $bridge->render($viewPath, null, $testAddon);
    }

    protected function createTemplateLoader() {
        $loader = new EbiTemplateLoader($this->createAddonManager());
        return $loader;
    }

    protected function createBridge() {
        $ebi = new Ebi(
            $this->createTemplateLoader(),
            PATH_ROOT.'/tests/cache/ebi'
        );
        $bridge = new EbiBridge($ebi);
        return $bridge;
    }

    protected function createAddonManager() {
        $root = '/tests/fixtures';

        $manager = new AddonManager(
            [
                Addon::TYPE_ADDON => "$root/addons",
                Addon::TYPE_THEME => "$root/themes",
                Addon::TYPE_LOCALE => "$root/locales"
            ],
            PATH_ROOT.'/tests/cache/am/ebi'
        );

        $manager->setTheme($manager->lookupTheme('basic'));

        return $manager;
    }
}
