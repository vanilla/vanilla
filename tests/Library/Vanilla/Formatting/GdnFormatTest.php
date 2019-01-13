<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use VanillaTests\Library\Vanilla\Formatting\FixtureRenderingTest;

class GdnFormatTest extends FixtureRenderingTest {

    const FIXTURE_DIR = self::FIXTURE_ROOT . '/formats';

    /**
     * @param string $fixtureDir
     * @throws \Exception
     * @dataProvider provideBBCode
     */
    public function testToBBCode(string $fixtureDir) {
        list($input, $expectedOutput) = $this->getFixture(self::FIXTURE_DIR . '/bbcode/html/' . $fixtureDir);
        $output = \Gdn_Format::to($input, "bbcode");
        $this->assertHtmlStringEqualsHtmlString(
            $expectedOutput, // Needed so code blocks are equivalently decoded
            $output, // Gdn_Format does htmlspecialchars
            "Expected html outputs for fixture $fixtureDir did not match."
        );
    }

    public function provideBBCode() {
        return $this->createFixtureDataProvider('/formats/bbcode/html');
    }
}
