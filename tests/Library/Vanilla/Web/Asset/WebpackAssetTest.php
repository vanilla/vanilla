<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Asset;

use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use Vanilla\Web\Asset\WebpackAsset;
use VanillaTests\Fixtures\MockCacheBusterInterface;
use VanillaTests\Fixtures\Request;

class WebpackAssetTest extends TestCase {

    private $fs;

    public function setUp() {
        $this->fs = vfsStream::setup();
    }

    public function testExists() {
        $fs = $this->getFsWithFile("bootstrap.min.js");
        $asset = new WebpackAsset(
            new Request(),
            new MockCacheBusterInterface(),
            ".min.js",
            "test",
            "bootstrap"
        );
        $asset->setFsRoot($fs->url());

        $this->assertTrue($asset->existsOnFS());

        $asset = new WebpackAsset(
            new Request(),
            new MockCacheBusterInterface(),
            ".min.js",
            "test",
            "badAsset"
        );
        $asset->setFsRoot($fs->url());
        $this->assertFalse($asset->existsOnFS());
    }

    /**
     * @param Request $req
     * @param MockCacheBusterInterface $buster
     * @param string $expected
     *
     * @dataProvider webPathProvider
     */
    public function testWebPath(Request $req, MockCacheBusterInterface $buster, string $expected) {
        $asset = new WebpackAsset($req, $buster, WebpackAsset::SCRIPT_EXTENSION, "testSec", "test");
        $this->assertEquals($expected, $asset->getWebPath());
    }

    public function webPathProvider(): array {
        return [
            [
                (new Request()),
                new MockCacheBusterInterface(),
                "http://example.com/dist/testSec/test.min.js",
            ],
            [
                (new Request())->setAssetRoot("/someRoot"),
                new MockCacheBusterInterface(),
                "http://example.com/someRoot/dist/testSec/test.min.js",
            ],
            [
                (new Request())->setHost("me.com"),
                new MockCacheBusterInterface("cacheBuster"),
                "http://me.com/dist/testSec/test.min.js?h=cacheBuster",
            ],
        ];
    }

    private function getFsWithFile(string $fileName): vfsStreamDirectory {
        $structure = [
            "dist" => [
                "test" => [
                    $fileName => "helloWorld",
                ],
            ],
        ];

        return vfsStream::create($structure);
    }
}
