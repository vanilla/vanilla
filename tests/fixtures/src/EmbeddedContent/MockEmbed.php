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

    private $allowedTypes = [self::TYPE];

    /**
     * Override constructor to allow passing allowed types.
     *
     * @param array $data
     * @param array|null $allowedTypes
     */
    public function __construct(array $data, array $allowedTypes = null) {
        if ($allowedTypes) {
            $this->allowedTypes = $allowedTypes;
        }
        parent::__construct($data);
    }

    /**
     * Make an empty mock embed.s
     *
     * @return MockEmbed
     */
    public static function nullEmbed(): MockEmbed {
        return new MockEmbed([
            'embedType' => self::TYPE,
            'url' => 'https://test.com',
            'testProp' => 'test',
        ]);
    }

    /**
     * @return array
     */
    protected function getAllowedTypes(): array {
        return $this->allowedTypes;
    }

    /**
     * @return Schema
     */
    protected function schema(): Schema {
        return Schema::parse([
            'testProp:s?',
        ]);
    }
}
