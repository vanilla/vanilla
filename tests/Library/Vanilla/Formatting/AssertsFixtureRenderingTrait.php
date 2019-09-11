<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting;

/**
 * Base test case for rendering some input/output from a fixture.
 */
trait AssertsFixtureRenderingTrait {

    use HtmlNormalizeTrait;

    /**
     * Get the expected in and out contents from a directory.
     *
     * Directory should contain:
     * - input.*
     * - output.html
     * - output.txt
     *
     * @param string $fixtureDir
     *
     * @return string[] A tuple of the input and expected output for html and txt.
     * @throws \Exception When a fixture doesn't have the exactly 1 input & output.
     */
    public function getFixture(string $fixtureDir): array {
        $inputs = glob($fixtureDir . '/input.*');
        $outputHtml = glob($fixtureDir . '/output.html')[0] ?? null;
        $outputText = glob($fixtureDir . '/output.txt')[0] ?? null;

        if (count($inputs) !== 1) {
            throw new \Exception("There must be exactly 1 input when fetching a fixture.");
        }

        return [
            file_get_contents($inputs[0]),
            $outputHtml ? file_get_contents($outputHtml) : 'no output.html fixture provided',
            $outputText ? file_get_contents($outputText) : 'no output.txt fixture provided',
        ];
    }

    /**
     * Create a PHPUnit data provider value for a fixture.
     *
     * @param string $fixtureDir
     *
     * @return array
     */
    protected function createFixtureDataProvider(string $fixtureDir) {
        $paramSets = [];

        $files = glob(PATH_ROOT . '/tests/fixtures/' . "$fixtureDir/*", GLOB_ONLYDIR);
        foreach ($files as $file) {
            $paramSets[] = [$file];
        }

        return $paramSets;
    }
}
