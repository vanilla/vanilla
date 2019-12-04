<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Asset;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use Vanilla\Web\Asset\WebpackAsset;
use VanillaTests\Fixtures\Request;

/**
 * Tests for the WebpackAsset class.
 */
class WebpackAssetTest extends TestCase {

    private $fs;

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        $this->fs = vfsStream::setup();
    }

    /**
     * Test that our file exists checks work properly.
     */
    public function testExists() {
        $fs =  vfsStream::create([
            "dist" => [
                "test" => [
                    'bootstrap.min.js' => "helloWorld",
                ],
            ],
        ]);
        $asset = new WebpackAsset(
            new Request(),
            ".min.js",
            "test",
            "bootstrap"
        );
        $url = $fs->url();
        $asset->setFsRoot($url);

        $this->assertTrue($asset->existsOnFs());

        $asset = new WebpackAsset(
            new Request(),
            ".min.js",
            "test",
            "badAsset"
        );
        $asset->setFsRoot($fs->url());
        $this->assertFalse($asset->existsOnFs());
    }

    /**
     * Test that web patches are properly generated.
     *
     * @param Request $req
     * @param string $buster
     * @param string $expected
     *
     * @dataProvider webPathProvider
     */
    public function testWebPath(Request $req, string $buster, string $expected) {
        $asset = new WebpackAsset(
            $req,
            WebpackAsset::SCRIPT_EXTENSION,
            "testSec",
            "test",
            $buster
        );
        $this->assertEquals($expected, $asset->getWebPath());
    }

    /**
     * Provider for for testWebPath
     */
    public function webPathProvider(): array {
        return [
            [
                (new Request())->setHost("http://example.com"),
                "",
                "/dist/testSec/test.min.js",
            ],
            [
                (new Request())->setHost("http://example.com")->setAssetRoot("/someRoot"),
                "",
                "/someRoot/dist/testSec/test.min.js",
            ],
            [
                (new Request())->setHost("me.com"),
                "cacheBuster",
                "/dist/testSec/test.min.js?h=cacheBuster",
            ],
            [
                (new Request())->setHost("me.com")
                    ->setPath("/path-should-be-ignored")
                    ->setRoot("/root-should-be-ignored")
                    ->setAssetRoot("/assetRoot"),
                "cacheBuster",
                "/assetRoot/dist/testSec/test.min.js?h=cacheBuster",
            ],
        ];
    }
}
