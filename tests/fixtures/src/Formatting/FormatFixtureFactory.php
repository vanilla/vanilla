<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Formatting;

/**
 * Class for getting fixtures from the fixtures/formats directory.
 */
class FormatFixtureFactory {

    const ROOT = PATH_ROOT . '/tests/fixtures/formats';

    /** @var string */
    private $formatName;

    /**
     * @param string $formatName
     */
    public function __construct(string $formatName) {
        $this->formatName = $formatName;
    }

    /**
     * @return FormatFixture[]
     */
    public function getAllFixtures(): array {
        $path = self::ROOT . '/' . $this->formatName . '/*';
        $dirs = glob($path, GLOB_ONLYDIR);
        $fixtures = [];
        foreach ($dirs as $dir) {
            $fixtures[] = new FormatFixture($dir);
        }
        return $fixtures;
    }
}
