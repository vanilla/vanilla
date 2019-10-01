<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\TextExFormat;
use Vanilla\Formatting\Formats\TextFormat;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;

/**
 * Tests for the TextExFormat.
 */
class TextFormatTest extends AbstractFormatTestCase {

    /**
     * @inheritDoc
     */
    protected function prepareFormatter(): FormatInterface {
        return self::container()->get(TextFormat::class);
    }

    /**
     * @inheritDoc
     */
    protected function prepareFixtures(): array {
        return (new FormatFixtureFactory('text'))->getAllFixtures();
    }
}
