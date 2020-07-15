<?php
/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\CivilTongueEx\Library;

use CivilTongueEx\Library\ContentFilter;
use PHPUnit\Framework\TestCase;

/**
 * Class ContentFilterTest
 */
class ContentFilterTest extends TestCase {

    /**
     * Bootstrap ContentFilter
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        require PATH_ROOT.'/plugins/CivilTongueEx/Library/ContentFilter.php';
    }

    /**
     * Create a new ContentFilter instance for testing.
     */
    public function setUp(): void {
        parent::setUp();

        $this->contentFilter = new ContentFilter();
        $this->contentFilter->setReplacement('****');
    }

    /**
     * Test replace() method in ContentFilter
     *
     * @param string string $patternList
     * @param string string $text
     * @param string string $expected
     * @dataProvider providePatternList
     */
    public function testReplace(string $patternList, string $text, string $expected) {
        $this->contentFilter->setWords($patternList);
        $result = $this->contentFilter->replace($text);
        $this->AssertSame($expected, $result);
    }

    /**
     * Provide patterns, test text and expected results to the test.
     *
     * @return array Provider data.
     */
    public function providePatternList() {
        $provider = [
            'General' => ['poop;$hit;a$$', 'This poop is the text.', 'This **** is the text.'],
            'TextBeginsWithSwear' => ['poop;$hit;a$$', 'poop the text', '**** the text'],
            'TextEndsWithSwear' => ['poop;$hit;a$$', 'The text is poop', 'The text is ****'],
            'SwearEndsWithDollarSign' => ['poop;$hit;a$$', 'The text is a$$', 'The text is ****'],
            'SwearStartsWithDollarSign' => ['poop;$hit;a$$', '$hit the text', '**** the text'],
            'SwearHasDollarSign' => ['poop;$hit;a$$', '$hithead the text', '$hithead the text'],
            'SwearHasCamelCase' => ['poop;$hit;a$$', 'PoOp the text', '**** the text'],
        ];
        return $provider;
    }
}
