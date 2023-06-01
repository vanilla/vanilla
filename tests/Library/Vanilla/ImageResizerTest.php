<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use Vanilla\ImageResizer;

/**
 * Tests for the **ImageResizer** class.
 */
class ImageResizerTest extends TestCase
{
    protected static $cachePath = PATH_ROOT . "/tests/cache/image-resizer";

    /** @var ImageResizer */
    private $imageResizer;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->imageResizer = new ImageResizer();
    }

    /**
     * Clear the test cache before tests.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (file_exists(self::$cachePath)) {
            $files = glob(self::$cachePath . "/*.*");
            array_walk($files, "unlink");
        } else {
            mkdir(self::$cachePath, 0777, true);
        }
    }

    /**
     * Test resize calculations with cropping.
     *
     * @param array $source The source dimensions of the image.
     * @param array|null $expected The expected array.
     * @dataProvider provideCalculateSampleCropTests
     */
    public function testCalculateSampleCrop(array $source, array $expected = null): void
    {
        $opts = ["width" => 150, "height" => 100, "crop" => true];

        $this->assertCalculateResize($source, $opts, $expected ?: $opts);
    }

    /**
     * Calls **ImageResizer::calculateResize()** and asserts the result against an expected result.
     *
     * @param array $source The source argument for **calculateResize()**.
     * @param array $options The options argument for **calculateResize()**.
     * @param array $expected The expected result.
     * @param array|null $props Limit the comparison to just a few properties.
     */
    protected function assertCalculateResize(array $source, array $options, array $expected, ?array $props = null): void
    {
        $r = $this->imageResizer->calculateResize($source, $options);

        $fn = function ($a, $b) {
            if ($a[0] === "s" && $b[0] === "s") {
                return strcmp($a, $b);
            } elseif ($a[0] === "s") {
                return 1;
            } elseif ($b[0] === "s") {
                return -1;
            } else {
                return strcmp($a, $b);
            }
        };

        if ($props) {
            $props = array_fill_keys($props, 1);
            $r = array_intersect_key($r, $props);
            $expected = array_intersect_key($expected, $props);
        }

        uksort($r, $fn);
        uksort($expected, $fn);

        $this->assertEquals($expected, $r);
    }

    /**
     * Provide tests for **testCalculateSampleCrop()**.
     *
     * @return array Returns a data provider array.
     */
    public function provideCalculateSampleCropTests(): array
    {
        $ident = [
            "height" => 100,
            "width" => 150,
            "sourceX" => 0,
            "sourceY" => 0,
            "sourceHeight" => 100,
            "sourceWidth" => 150,
        ];

        $r = [
            "same" => [["height" => 100, "width" => 150], $ident],
            "wide rect" => [["height" => 100, "width" => 200], ["sourceX" => 25] + $ident],
            "narrow rect" => [["height" => 300, "width" => 150], ["sourceY" => 100] + $ident],
            "smaller" => [
                ["height" => 50, "width" => 75],
                ["height" => 50, "width" => 75, "sourceHeight" => 50, "sourceWidth" => 75] + $ident,
            ],
            "larger" => [["height" => 200, "width" => 300], ["sourceHeight" => 200, "sourceWidth" => 300] + $ident],
            "narrow small rect" => [
                ["height" => 220, "width" => 30],
                [
                    "height" => 20,
                    "width" => 30,
                    "sourceHeight" => 20,
                    "sourceWidth" => 30,
                    "sourceX" => 0,
                    "sourceY" => 100,
                ],
            ],
            "wide small rect" => [
                ["height" => 50, "width" => 175],
                [
                    "height" => 50,
                    "width" => 75,
                    "sourceHeight" => 50,
                    "sourceWidth" => 75,
                    "sourceX" => 50,
                    "sourceY" => 0,
                ],
            ],
            "small narrow" => [
                ["height" => 100, "width" => 75],
                [
                    "height" => 50,
                    "width" => 75,
                    "sourceHeight" => 50,
                    "sourceWidth" => 75,
                    "sourceX" => 0,
                    "sourceY" => 25,
                ],
            ],
            "small tall" => [
                ["height" => 200, "width" => 30],
                [
                    "height" => 20,
                    "width" => 30,
                    "sourceHeight" => 20,
                    "sourceWidth" => 30,
                    "sourceX" => 0,
                    "sourceY" => 90,
                ],
            ],
        ];

        return $r;
    }

    /**
     * Test some basic resize calculations that don't involve cropping.
     *
     * @param array $options Options to pass to **ImageResizer::resize()**.
     * @param array|null $expected The expected resize result.
     * @dataProvider provideCalculateSampleScaleRests
     */
    public function testCalculateSampleScale(array $options, ?array $expected = null): void
    {
        $source = ["width" => 200, "height" => 100];

        $expected = (array) $expected + $source;
        $expected += [
            "sourceHeight" => $source["height"],
            "sourceWidth" => $source["width"],
            "sourceX" => 0,
            "sourceY" => 0,
        ];

        $this->assertCalculateResize($source, $options, $expected);
    }

    /**
     * Provide tests for **testCalculateSampleScale()**.
     *
     * @return array Returns a data provider array.
     */
    public function provideCalculateSampleScaleRests(): array
    {
        $r = [
            "same" => [["width" => 200, "height" => 100]],
            "tall narrow" => [["width" => 50, "height" => 200], ["width" => 50, "height" => 25]],
            "wide short" => [["width" => 200, "height" => 50], ["width" => 100, "height" => 50]],
            "small" => [["width" => 50, "height" => 75], ["width" => 50, "height" => 25]],
        ];

        return $r;
    }

    /**
     * Verify a relevant exception is thrown when resizing without the required height.
     */
    public function testCalculateResizeNoSourceHeight(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing argument $source["height"].');
        $this->expectExceptionCode(400);
        $this->imageResizer->calculateResize(["width" => 100], []);
    }

    /**
     * Verify a relevant exception is thrown when resizing without the required width.
     */
    public function testCalculateResizeNoSourceWidth(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing argument $source["width"].');
        $this->expectExceptionCode(400);
        $this->imageResizer->calculateResize(["height" => 100], []);
    }

    /**
     * Test image resizing that have just one constraint.
     *
     * @param array $options The resize constraints.
     * @param array $expected The expected resize result.
     * @dataProvider provideOneConstraintCropTests
     */
    public function testOneConstraintResizeTests(array $options, array $expected = []): void
    {
        $source = ["width" => 200, "height" => 100];

        $expected = (array) $expected + $source;
        $expected += [
            "sourceHeight" => $source["height"],
            "sourceWidth" => $source["width"],
            "sourceX" => 0,
            "sourceY" => 0,
        ];

        $this->assertCalculateResize($source, $options, $expected, ["height", "width"]);
    }

    /**
     * Provide tests for **testOneConstraintResizeTests()**.
     */
    public function provideOneConstraintCropTests(): array
    {
        $r = [
            "no height" => [["width" => 100], ["width" => 100, "height" => 50]],
            "no width" => [["height" => 50], ["width" => 100, "height" => 50]],
            "large width" => [["width" => 1000]],
            "large height" => [["height" => 200]],
        ];

        return $r;
    }

    /**
     * Verify saving a GIF (animated, in particular) that does not require processing will not rewrite it (thus losing its animation).
     */
    public function testAnimatedGif(): void
    {
        $source = PATH_ROOT . "/tests/fixtures/animated.gif";
        $sourceHash = hash_file("md5", $source);
        $destination = PATH_ROOT . "/tests/cache/image-resizer/" . __FUNCTION__ . ".gif";

        $size = getimagesize($source);
        [$width, $height] = $size;
        $this->imageResizer->resize($source, $destination, ["width" => $width, "height" => $height]);
        $destinationHash = hash_file("md5", $destination);

        $this->assertSame($sourceHash, $destinationHash);
    }

    /**
     * Test actual image re-sizes.
     *
     * This test does some basic assertions, but to really tell
     *
     * @param int $w The desired width.
     * @param int $h The desired height.
     * @param string $ext The desired file extension.
     * @param array $opts Resize options.
     * @dataProvider provideResizes
     */
    public function testResize(int $w, int $h, string $ext = "*", array $opts = []): void
    {
        $source = PATH_ROOT . "/tests/fixtures/apple.jpg";
        $dest = PATH_ROOT . "/tests/cache/image-resizer/apple-{$w}x{$h}.$ext";

        $r = $this->imageResizer->resize($source, $dest, ["width" => $w, "height" => $h] + $opts);

        $this->assertFileExists($r["path"]);

        $size = getimagesize($r["path"]);
        [$dw, $dh, $type] = $size;

        $this->assertEquals($r["width"], $dw);
        $this->assertEquals($r["height"], $dh);
        $this->assertEquals($this->imageResizer->imageTypeFromExt($r["path"]), $type);
    }

    /**
     * Verify a relevant exception is thrown when attempting to resize a file that does not exist.
     */
    public function testResizeDoesNotExist(): void
    {
        $source = "/file/not/found.jpg";
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Source file \"$source\" does not exist.");
        $this->expectExceptionCode(400);

        $this->imageResizer->resize($source, "/path/to/destination.jpg", ["width" => 256, "height" => 256]);
    }

    /**
     * Verify images aren't unnecessarily rewritten.
     */
    public function testNoRewrite(): void
    {
        $source = PATH_ROOT . "/tests/fixtures/animated.gif";
        $destination = PATH_ROOT . "/tests/cache/image-resizer/animated-copy.gif";
        $this->imageResizer->resize($source, $destination, ["width" => 256, "height" => 256]);
        $this->assertFileEquals($source, $destination);
    }

    /**
     * Verify ability to determine the proper extension from an IMAGETYPE_* constant.
     *
     * @param int $type
     * @param string $expected
     * @dataProvider provideExtFromImageType
     */
    public function testExtFromImageType(int $type, $expected): void
    {
        $actual = $this->imageResizer->extFromImageType($type);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide data for testing mapping an IMAGETYPE_* constant to its extension.
     *
     * @return array
     */
    public function provideExtFromImageType(): array
    {
        $result = [];
        foreach (ImageResizer::ALL_TYPE_EXT as $type => $ext) {
            $result[$type] = [$type, $ext];
        }
        $result["Unsupported Type"] = [999, "999"];
        return $result;
    }

    /**
     * Verify ability to get a valid image type from a file extension.
     *
     * @param string $ext
     * @param int $expected
     * @dataProvider provideImageTypeFromExt
     */
    public function testImageTypeFromExt(string $ext, int $expected): void
    {
        $actual = $this->imageResizer->imageTypeFromExt("/path/to/file.{$ext}");
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide data for testing ability to determine image type from a file extension.
     *
     * @return array
     */
    public function provideImageTypeFromExt(): array
    {
        $result = [];
        foreach (ImageResizer::getExtType() as $ext => $type) {
            $result[$ext] = [$ext, $type];
        }
        return $result;
    }

    /**
     * Verify retrieving image type by an invalid extension throws a proper exception.
     */
    public function testImageTypeFromExtUnsupportedExtension(): void
    {
        $invalidExt = "wtf";
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown image type for extension '{$invalidExt}'.");
        $this->expectExceptionCode(400);
        $this->imageResizer->imageTypeFromExt("/path/to/file.{$invalidExt}");
    }

    /**
     * Provide tests for **testResize()**.
     *
     * @return array Returns a data provider array.
     */
    public function provideResizes(): array
    {
        $r = [[300, 300, "png"], [300, 300, "jpg"], [100, 100, "png"], [100, 100, "jpg"], [100, 100, "gif"], [50, 100]];

        $r2 = [];
        foreach ($r as $row) {
            $r2["{$row[0]}x{$row[1]}" . (isset($row[2]) ? " " . $row[2] : "")] = $row;
        }
        return $r2;
    }

    /**
     * Verify the extension-to-type mapping is available.
     */
    public function testGetExtType(): void
    {
        $actual = ImageResizer::getExtType();
        $this->assertIsArray($actual);
        $this->assertNotEmpty($actual);
    }

    /**
     * Verify the type-to-extension mapping is available.
     */
    public function testGetTypeExt(): void
    {
        $actual = ImageResizer::getTypeExt();
        $this->assertIsArray($actual);
        $this->assertNotEmpty($actual);
    }

    /**
     * Test that resizing a non-resizeable image (such as ico) throws an exception
     */
    public function testNonResizeableImage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot resize images of this type (ico).");
        $this->expectExceptionCode(400);

        $source = PATH_ROOT . "/tests/fixtures/apple.ico";
        $dest = PATH_ROOT . "/tests/cache/image-resizer/" . __FUNCTION__ . ".ico";

        $this->imageResizer->resize($source, $dest, ["width" => 128, "height" => 128]);
    }
}
