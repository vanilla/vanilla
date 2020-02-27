<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Vanilla\Invalid;
use Vanilla\PlainTextLengthValidator;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for PlainTextLengthValidator class.
 */
class PlainTextLengthValidatorTest extends MinimalContainerTestCase {

    /** @var int Number of characters to test for. */
    const TEST_MAX_COMMENT_LENGTH = 10;

    /**
     * Test the PlainTextLengthValidator Class
     *
     * @param array $post Testable POST array.
     * @param bool $isValid Expected result of the validator.
     *
     * @dataProvider providePostContent
     */
    public function testPlainTextLengthValidator(array $post, bool $isValid) {
        $validator = $this->container()->get(PlainTextLengthValidator::class);
        $field = (object)[
            'Name' => 'Body',
            'maxPlainTextLength' => self::TEST_MAX_COMMENT_LENGTH,
        ];
        $result = $validator($post['Body'] ?? '', $field, $post);
        $actual = !($result instanceof Invalid);
        $this->assertEquals($isValid, $actual);
    }

    /**
     * Provide formatted text to test counting text length with formatting stripped out.
     *
     * @return array Formatted posts with expected validation results (true or false).
     */
    public static function providePostContent() {
        return [
            'Empty Body' => [
                [
                    'Body' => '',
                    'Format' => 'markdown',
                ],
                true,
            ],
            'Empty Format' => [
                [
                    'Body' => 'Word up!',
                    'Format' => '',
                ],
                false,
            ],
            'Short Plain' => [
                [
                    'Body' => 'Word Up',
                    'Format' => 'markdown',
                ],
                true,
            ],
            'Short Formatted' => [
                [
                    'Body' => '**Word** *Up*',
                    'Format' => 'markdown',
                ],
                true,
            ],
            'Long Markdown' => [
                [
                    'Body' => '**Many** Words *Up*',
                    'Format' => 'markdown',
                ],
                false,
            ],
            'Short Rich' => [
                [
                    'Body' => '[{"insert":"Word "},{"attributes":{"link":"https:\/\/up.org"},"insert":"Up"},{"insert":"\n"}]',
                    'Format' => 'rich',
                ],
                true,
            ],
            'Long Rich' => [
                [
                    'Body' => '[{"insert":"Many words"},{"attributes":{"link":"https:\/\/up.org"},"insert":"Up"}]',
                    'Format' => 'rich',
                ],
                false,
            ],
            'Short WYSYWIG' => [
                [
                    'Body' => '<p>Word<span class="Test"><a rel="nofollow" href="https://up.org">up</a></span></p>',
                    'Format' => 'wysiwyg',
                ],
                true,
            ],
            'Long WYSYWIG' => [
                [
                    'Body' => '<p>Many words<span class="Test"><a rel="nofollow" href="https://up.org">up</a></span></p>',
                    'Format' => 'wysiwyg',
                ],
                false,
            ],
        ];
    }
}
