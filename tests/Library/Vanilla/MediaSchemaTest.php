<?php
/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Vanilla\Models\VanillaMediaSchema;
use VanillaTests\MinimalContainerTestCase;

/**
 * Class CategoryModelTest
 *
 * @package VanillaTests\Models
 */
class MediaSchemaTest extends MinimalContainerTestCase {
    /**
     * Provides image widths and expected display size.
     * @return array[]
     */
    public function provideMediaRows(): array {
        return [
            "Small Image" => [
                'imageWidth' => 100,
                'expectedDisplaySize' => "small"
            ],
            "Medium Image" => [
                'imageWidth' => 300,
                'expectedDisplaySize' => "medium"
            ],
            "Large Image" => [
                'imageWidth' => 500,
                'expectedDisplaySize' => "large"
            ]
        ];
    }

    /**
     * Tests display size determined by the width of an image.
     * This test is a little too narrow scoped, I would like to test normalizeFromDbRecord() but it uses Gdn_Upload which is harder to test.
     *
     * @param int $imageWidth
     * @param string $expectedDisplaySize
     * @dataProvider provideMediaRows
     */
    public function testDisplaySize(int $imageWidth, string $expectedDisplaySize) {
        $displaySize = VanillaMediaSchema::getDefaultDisplaySize($imageWidth);

        $this->assertEquals($displaySize, $expectedDisplaySize);
    }
}
