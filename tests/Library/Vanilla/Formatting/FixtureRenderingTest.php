<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting;

use VanillaTests\SharedBootstrapTestCase;

class FixtureRenderingTest extends SharedBootstrapTestCase {

    use HtmlNormalizeTrait;

    const FIXTURE_ROOT = PATH_ROOT . '/tests/fixtures';

    /**
     * @param string $fixtureDir
     *
     * @return string[] A tuple of the input and expected output.
     * @throws \Exception When a fixture doesn't have the exactly 1 input & output.
     */
    public function getFixture(string $fixtureDir): array {
        $inputs = glob( $fixtureDir . '/input.*');
        $outputs = glob( $fixtureDir . '/output.*');

        if (count($inputs) !== 1) {
            throw new \Exception("There must be exactly 1 input when fetching a fixture.");
        }

        if (count($outputs) !== 1) {
            throw new \Exception("There must be exactly 1 output when fetching a fixture.");
        }
        return [
            file_get_contents($inputs[0]),
            file_get_contents($outputs[0]),
        ];
    }

    /**
     * Assert that two strings of HTML are roughly similar. This doesn't work for code blocks.
     *
     * @param string $expected
     * @param string $actual
     * @param string|null $message
     */
    protected function assertHtmlStringEqualsHtmlString(string $expected, string $actual, string $message = null) {
        $expected = $this->normalizeHtml($expected);
        $actual = $this->normalizeHtml($actual);
        $this->assertEquals($expected, $actual, $message);
    }

    protected function createFixtureDataProvider(string $fixtureDir) {
        $paramSets = [];

        $files = glob(self::FIXTURE_ROOT . "$fixtureDir/*", GLOB_ONLYDIR);
        foreach ($files as $file) {
            $paramSets[] = [basename($file)];
        }

        return $paramSets;
    }
}
