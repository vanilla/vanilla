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
class WebpackAssetTest extends TestCase
{
    private $fs;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->fs = vfsStream::setup();
    }

    /**
     * Test that our file exists checks work properly.
     */
    public function testExists()
    {
        $fs = vfsStream::create([
            PATH_DIST_NAME => [
                "test" => [
                    "bootstrap.min.js" => "helloWorld",
                ],
            ],
        ]);
        $asset = new WebpackAsset(new Request(), "/dist/test/bootstrap.min.js");
        $url = $fs->url();
        $asset->setFsRoot($url);

        $this->assertTrue($asset->existsOnFs());

        $asset = new WebpackAsset(new Request(), "/dist/test/badAsset.min.js");
    }

    /**
     * Test our static property.
     */
    public function testStatic()
    {
        $asset = new WebpackAsset(new Request(), "/dist/test/something.min.js");

        $this->assertTrue($asset->isStatic());
    }

    /**
     * Test that web patches are properly generated.
     *
     * @param Request $req
     * @param string $path
     * @param string $expected
     *
     * @dataProvider webPathProvider
     */
    public function testWebPath(Request $req, string $path, string $expected)
    {
        $asset = new WebpackAsset($req, $path);
        $this->assertEquals($expected, $asset->getWebPath());
    }

    /**
     * Provider for testWebPath
     */
    public function webPathProvider(): array
    {
        $assetPath = "/dist/testSec/test.min.js";
        return [
            [new Request(), $assetPath, "http://example.com$assetPath"],
            [(new Request())->setAssetRoot("/someRoot"), $assetPath, "http://example.com/someRoot$assetPath"],
            [(new Request())->setHost("me.com"), $assetPath, "http://me.com$assetPath"],
            [
                (new Request())
                    ->setHost("me.com")
                    ->setPath("/path-should-be-ignored")
                    ->setRoot("/root-should-be-ignored")
                    ->setAssetRoot("/assetRoot"),
                $assetPath,
                "http://me.com/assetRoot$assetPath",
            ],
        ];
    }
}
