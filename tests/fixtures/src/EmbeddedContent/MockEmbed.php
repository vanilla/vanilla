<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\EmbeddedContent;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;

/**
 * A configurable stub embed for usage in tests.
 */
class MockEmbed extends AbstractEmbed {

    const TYPE = "testEmbedType";

    /**
     * @return array
     */
    protected function getAllowedTypes(): array {
        return [self::TYPE];
    }

    /**
     * @return Schema
     */
    protected function schema(): Schema {
        return Schema::parse([
            'testProp:s',
        ]);
    }
}
