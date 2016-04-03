<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;


use Vanilla\Addon;

/**
 * Tests for the {@link Addon} class.
 */
class AddonTest extends \PHPUnit_Framework_TestCase {
    /**
     * Test {@link Addon::scanInfo()}.
     *
     * @param string $dir The root-relative directory of the addon to scan.
     * @dataProvider provideAddonDirectories
     */
    public function testScanInfo($dir) {
        $addon = new Addon($dir);
        $this->assertNotNull($addon->getInfo());

        $issues = $addon->check();
        $this->assertEmpty($issues, implode(' ', $issues));
    }

    /**
     * Test addon class scanning.
     *
     * @param string $subdir The root-relative directory of the addon to scan.
     * @dataProvider provideAddonDirectories
     */
    public function testScanClasses($subdir) {
        $addon = new Addon($subdir);
        $classes = $addon->getClasses();
        foreach ($classes as $classKey => $info) {
            list($fullName, $subpath) = $info;

            $this->assertFileExists($addon->path($subpath));
        }
    }

    public function testExport() {
        $dirs = $this->provideAddonDirectories();
        $addons = [];
        $themes = [];
        $locales = [];
        foreach ($dirs as $row) {
            list($subdir) = $row;
            
            $addon = new Addon($subdir);

            switch ($addon->getType()) {
                case Addon::TYPE_LOCALE:
                    $locales[$addon->getKey()] = $addon;
                    break;
                case Addon::TYPE_THEME:
                    $themes[$addon->getKey()] = $addon;
                    break;
                default:
                    $addons[$addon->getKey()] = $addon;
            }
        }

        $arrays = ['addons' => $addons, 'locales' => $locales, 'themes' => $themes];

        foreach ($arrays as $key => $array) {
            $varString = "<?php return ".var_export($array, true).";\n";
            file_put_contents(PATH_CACHE."/$key.php", $varString);
        }
    }

    /**
     * @depends testExport
     */
    public function testImport() {
        $path = PATH_CACHE.'/addons.php';
        $addons = require $path;

        $this->assertNotEmpty($addons);
    }

    /**
     * Provide all of the addon directories.
     *
     * @return array[array] Returns an test data provider array.
     */
    public function provideAddonDirectories() {
        $baseDirs = ['/applications', '/locales', '/plugins', '/themes'];

        $result = [];
        foreach ($baseDirs as $dir) {
            $paths = glob(PATH_ROOT."$dir/*", GLOB_ONLYDIR);
            foreach ($paths as $path) {
                $key = $dir.'/'.basename($path);
                $result[$key] = [$key];
            }
        }

        return $result;
    }
}
