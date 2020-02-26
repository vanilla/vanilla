<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Vanilla\Invalid;
use Vanilla\VisibleTextLengthValidator;
use Vanilla\Formatting\FormatService;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for VisibleTextLengthValidator class.
 *
 * Class VisibleTextLengthValidatorTest
 * @package VanillaTests\Library\Vanilla
 */
class VisibleTextLengthValidatorTest extends MinimalContainerTestCase {

    /** @var int Number of characters to test for. */
    const TEST_MAX_COMMENT_LENGTH = 10;

    /**
     * Test the VisibleTextLengthValidator Class
     *
     * @param array $post Testable POST array.
     * @param bool $isValid Expected result of the validator.
     *
     * @dataProvider providePostContent
     */
    public function testVisibleTextLengthValidator(array $post, bool $isValid) {
        $formatService = \Gdn::formatService();
        $locale = \Gdn::locale();
        $validator = new VisibleTextLengthValidator(self::TEST_MAX_COMMENT_LENGTH, $formatService, $locale);
        $field = (object) array('Name' => 'Body', 'maxTextLength' => self::TEST_MAX_COMMENT_LENGTH);
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
                ['Body' => '', 'Format' => 'markdown'],
                true
            ],
            'Empty Format' => [
                ['Body' => 'Word up!', 'Format' => ''],
                false
            ],
            'Short Plain' => [
                ['Body' => 'Word Up', 'Format' => 'markdown'],
                true
            ],
            'Short Formatted' => [
                ['Body' => '**Word** *Up*', 'Format' => 'markdown'],
                true
            ],
            'Long Markdown' => [
                ['Body' => '**Many** Words *Up*', 'Format' => 'markdown'],
                false
            ],
            'Short Rich' => [
                ['Body' => '[{"insert":"Word "},{"attributes":{"link":"https:\/\/up.org"},"insert":"Up"},{"insert":"\n"}]', 'Format' => 'rich'],
                true
            ],
            'Long Rich' => [
                ['Body' => '[{"insert":"Many words"},{"attributes":{"link":"https:\/\/up.org"},"insert":"Up"}]', 'Format' => 'rich'],
                false
            ],
            'Short WYSYWIG' => [
                ['Body' => '<p>Word<span class="Test"><a rel="nofollow" href="https://up.org">up</a></span></p>', 'Format' => 'wysiwyg'],
                true
            ],
            'Long WYSYWIG' => [
                ['Body' => '<p>Many words<span class="Test"><a rel="nofollow" href="https://up.org">up</a></span></p>', 'Format' => 'wysiwyg'],
                false
            ],
        ];
    }
}
