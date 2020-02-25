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

    /** @var int Number of characters to test for */
    const TEST_MAX_COMMENT_LENGTH = 10;

    /**
     * Test the VisibleTextLengthValidator Class
     *
     * @param array $post Testable POST array.
     * @param bool $isValid Expected result of the validator.
     *
     * @dataProvider providePostContent()
     */
    public function testVisibleTextLengthValidator(array $post, bool $isValid) {
        $formatService = \Gdn::formatService();
        $locale = \Gdn::locale();
        $validator = new VisibleTextLengthValidator(self::TEST_MAX_COMMENT_LENGTH, $formatService, $locale);
        $result = $validator($post['Body'] ?? '', 'Body', $post);
        $resultType = true;
        if ($result instanceof Invalid) {
            $resultType = false;
        }
        $this->assertEquals($isValid, $resultType);
    }

    /**
     * Provide Markdown text to test counting text length with formatting stripped out.
     *
     * @return array
     */
    public static function providePostContent() {
        return [
            'Short Formatted' => [
                ['Body' => '**Bold**', 'Format' => 'Markdown'],
                true
            ],
            'Short Mixed' => [
                ['Body' => '**Bold** Text', 'Format' => 'Markdown'],
                true
            ],
            'Long Mixed' => [
                ['Body' => '**Bold** Text *italic*', 'Format' => 'Markdown'],
                false
            ],
            'Empty Body' => [
                ['Body' => '', 'Format' => 'Markdown'],
                true
            ],
        ];
    }
}
