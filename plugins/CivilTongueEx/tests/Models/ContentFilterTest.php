<?php
/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\CivilTongueEx\Library;

use CivilTongueEx\Library\ContentFilter;
use Garden\Container\Container;
use PHPUnit\Framework\TestCase;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;

/**
 * Class ContentFilterTest
 */
class ContentFilterTest extends TestCase {

    use SiteTestTrait;
    use SetupTraitsTrait;

    /** @var ContentFilter */
    private static $contentFilter;

    /**
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array {
        return ['civiltongueex'];
    }

    /**
     * Bootstrap ContentFilter
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        require PATH_ROOT.'/plugins/CivilTongueEx/Library/ContentFilter.php';
        self::setUpBeforeClassTestTraits();
    }

    /**
     * Configure the container before addons are started.
     *
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container) {
        self::$contentFilter = new ContentFilter();
        $container->setInstance(ContentFilter::class, self::$contentFilter);
    }

    /**
     * Create a new ContentFilter instance for testing.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setUpTestTraits();
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
        self::$contentFilter->setReplacement('****');
        self::$contentFilter->setWords($patternList);
        $result = self::$contentFilter->replace($text);
        $this->AssertSame($expected, $result);
        self::$contentFilter->setStaticPatterns(null);
    }

    /**
     * Test Civil Tongue via APIs for integration testing.
     */
    public function testActivityItems(): void {
        self::$contentFilter->setReplacement('dog');
        self::$contentFilter->setWords('cat');
        $response = $this->bessy()->post('/activity/post/public', [
            'Format' => 'Markdown',
            'Comment' => 'cat food'
        ]);
        $activityID = $response->Data['Activities'][0]['ActivityID'] ?? null;
        $this->assertNotNull($activityID);

        $activity = $this->bessy()->getJsonData('/activity/item/' . $activityID);
        $this->assertSame('dog food', $activity['Activities'][0]['Story']);
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
            'Thai 1' => ['อี', 'อี', '****'],
            'Thai 2' => ['อี เหี้ย', 'อี เหี้ย', '****'],
            'Thai 3' => ['อี เหี้ย', 'อี เหี้ย', '****'],
            'Thai 3 multiple ' => ['หน้าตัวเมีย;อี', 'หน้าตัวเมีย อี', '**** ****'],
            'Thai 3 multiple eng' => ['หน้าตัวเมีย;อี;block', 'หน้าตัวเมีย อี block', '**** **** ****'],
            'Thai 3 multiple eng 2' => ['หน้าตัวเมีย;อี;block', 'หน้าตัวเมีย อี block not blocked', '**** **** **** not blocked']
        ];
        return $provider;
    }
}
