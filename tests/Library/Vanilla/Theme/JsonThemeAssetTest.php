<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Garden\Web\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use Vanilla\Theme\Asset\JsonThemeAsset;

/**
 * Tests for the
 */
class JsonThemeAssetTest extends TestCase {

    /**
     * Test that certain input values are correctly preserved.
     *
     * We can't currently test this with NEON until https://github.com/nette/neon/issues/34 is completed.
     *
     * @param string $inOut
     *
     * @dataProvider provideRenderPreservion
     */
    public function testRenderPreservation(string $inOut) {
        $asset = new JsonThemeAsset($inOut, '');
        $result = $asset->render()->getData();
        $this->assertEquals($inOut, $result);

        $asset = new JsonThemeAsset($inOut, '');
        $asset->setIncludeValueInJson(true);
        $encoded = json_encode($asset);
        $result = <<<JSON
{"url":"","type":"json","content-type":"application\/json","data":$inOut}
JSON;

        $this->assertEquals($result, $encoded);
    }

    /**
     * @return array
     */
    public function provideRenderPreservion(): array {
        return [
            'empty array' => [
                '[]',
            ],
            'empty object' => [
                '{}'
            ],
            // Unfortunately can't be fixed at the moment.
            // 'nested empty array' => [
            //    '{"key":[]}'
            // ],
            'nested empty object' => [
                '{"key":{}}'
            ],
            'nested empty array object' => [
                '[{"key":{}}]'
            ],
            'indexed array' => [
                '{"field":[1,5,"asdf"]}',
            ],
            'indexed array 2' => [
                '{"field":{"field":["asdf"]}}',
            ],
        ];
    }

    /**
     * Test error handling.
     *
     * @param string $in
     * @param array $error
     *
     * @dataProvider provideErrors
     */
    public function testErrors(string $in, array $error) {
        $asset = new JsonThemeAsset($in, '');
        $asset->setIncludeValueInJson(true);

        $caughtError = false;
        try {
            $asset->validate();
        } catch (ClientException $e) {
            $caughtError = true;
        }
        $this->assertTrue($caughtError, 'An exception must be thrown while validating.');
        $this->assertEquals($error, $asset->getValue());
    }

    /**
     * @return array|array[]
     */
    public function provideErrors(): array {
        return [
            'invalid json' => [
                '{asdfasdf [asdfasdf]',
                [
                    'error' => 'Error decoding JSON',
                    'message' => 'Syntax error',
                ],
            ],
            'is a string' => [
                'hello',
                [
                    'error' => 'Error decoding JSON',
                    'message' => 'Syntax error',
                ],
            ],
            'is number' => [
                '52',
                [
                    'value' => 52,
                ],
            ],
            'is null' => [
                'null',
                [
                    'value' => null,
                ],
            ],
        ];
    }
}
