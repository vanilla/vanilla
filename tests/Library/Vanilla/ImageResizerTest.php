<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use Vanilla\ImageResizer;

class ImageResizerTest extends TestCase {

    protected function assertCalculateCrop(array $source, array $options, array $expected, array $props = null) {
        $cropper = new ImageResizer();

        $r = $cropper->calculateResize($source, $options);

        $fn = function ($a, $b) {
            if ($a[0] === 's' && $b[0] === 's') {
                return strcmp($a, $b);
            } elseif ($a[0] === 's') {
                return 1;
            } elseif ($b[0] === 's') {
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
     * @param array $source
     * @param array|null $expected
     * @dataProvider provideCalculateSquareCropTests
     */
    public function testCalculateSquareCrop(array $source, array $expected = null) {
        $opts = ['width' => 100, 'height' => 100];

        $this->assertCalculateCrop($source, $opts, $expected ?: $opts);

    }

    public function provideCalculateSquareCropTests() {
        $ident = ['height' => 100, 'width' => 100, 'sourceX' => 0, 'sourceY' => 0, 'sourceHeight' => 100, 'sourceWidth' => 100];

        $r = [
            'same' => [['height' => 100, 'width' => 100], $ident],
            'wide rect' => [['height' => 100, 'width' => 200], ['sourceX' => 50] + $ident],
            'narrow rect' => [['height' => 200, 'width' => 100], ['sourceY' => 50] + $ident],
            'small square' => [['height' => 50, 'width' => 50], ['height' => 50, 'width' => 50, 'sourceHeight' => 50, 'sourceWidth' => 50] + $ident],
            'large square' => [['height' => 200, 'width' => 200], ['sourceHeight' => 200, 'sourceWidth' => 200] + $ident],
            'narrow small rect' => [['height' => 200, 'width' => 50], ['height' => 50, 'width' => 50, 'sourceHeight' => 50, 'sourceWidth' => 50, 'sourceX' => 0, 'sourceY' => 75]],
            'wide small rect' => [['height' => 50, 'width' => 200], ['height' => 50, 'width' => 50, 'sourceHeight' => 50, 'sourceWidth' => 50, 'sourceX' => 75, 'sourceY' => 0]],
            'small narrow' => [['height' => 50, 'width' => 70], ['height' => 50, 'width' => 50, 'sourceHeight' => 50, 'sourceWidth' => 50, 'sourceX' => 10, 'sourceY' => 0]],
            'small tall' => [['height' => 70, 'width' => 50], ['height' => 50, 'width' => 50, 'sourceHeight' => 50, 'sourceWidth' => 50, 'sourceX' => 0, 'sourceY' => 10]]
        ];

        return $r;
    }

    /**
     * @param array $source
     * @param array|null $expected
     * @dataProvider provideOneConstraintCropTests
     */
    public function testOneConstraintCropTests(array $source, array $expected = null) {
        $opts = ['width' => 200];

        $this->assertCalculateCrop($source, $opts, $expected ?: $opts, ['height', 'width']);
    }

    /**
     *
     */
    public function provideOneConstraintCropTests() {
        $ident = ['height' => 50, 'width' => 200, 'sourceX' => 0, 'sourceY' => 0, 'sourceHeight' => 50, 'sourceWidth' => 200];

        $r = [
            'wide banner' => [['width' => 300, 'height' => 3000], ['width' => 200, 'height' => 2000] + $ident]
        ];

        return $r;
    }
}
