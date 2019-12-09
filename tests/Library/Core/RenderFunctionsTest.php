<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;
use VanillaTests\SiteTestTrait;

/**
 * Test some of the functions in functions.render.php.
 */
class RenderFunctionsTest extends TestCase {

    use HtmlNormalizeTrait;
    use SiteTestTrait {
        setupBeforeClass as siteTestBeforeClass;
    }

    /**
     * Make sure the render functions are included.
     */
    public static function setUpBeforeClass(): void {
        self::$addons = ['dashboard']; // Needed for render paths.
        self::siteTestBeforeClass();
        require_once PATH_ROOT.'/library/core/functions.render.php';
    }

    /**
     * Cleanup the html normalize trait.
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->shouldReplaceSVGs = true;
    }


    /**
     * Test a basic {@link userBuilder()}.
     */
    public function testUserBuilder() {
        $userRow = [
            'InsertUserID' => 123,
            'InsertName' => 'Fank',
            'InsertPhoto' => 'foo.png',
            'InsertEmail' => 'foo@noreply.com',
            'InsertGender' => 'mf'
        ];

        $user = userBuilder($userRow, 'Insert');
        $this->assertSame(array_values($userRow), array_values((array)$user));
    }

    /**
     * Test the multiple prefix version of {@link userBuilder()}.
     */
    public function testUserBuilderMultiplePrefixes() {
        $userRow = [
            'InsertUserID' => 123,
            'InsertUserName' => 'Frank',
            'FirstUserID' => 234,
            'FirstName' => 'Barry'
        ];

        $user = userBuilder($userRow, ['First', 'Insert']);
        $this->assertSame(234, $user->UserID);
        $this->assertSame('Barry', $user->Name);

        $user = userBuilder($userRow, ['Blarg', 'First']);
        $this->assertSame(234, $user->UserID);
        $this->assertSame('Barry', $user->Name);
    }

    /**
     * Test the dashboardSymbol() function.
     *
     * @param array $params
     * @param string $expectedHtml
     *
     * @dataProvider provideSymbolArgs
     */
    public function testDashboardSymbol(array $params, string $expectedHtml) {
        $actual = dashboardSymbol(...$params);
        $this->shouldReplaceSVGs = false;
        $this->assertHtmlStringEqualsHtmlString($expectedHtml, $actual);
    }

    /**
     * @return array
     */
    public function provideSymbolArgs(): array {
        return [
            'simple' => [
                ['testName', 'testClass'],
                '<svg alt="testName" class="icon icon-svg testClass" viewbox="0 0 17 17">
                    <use xlink:href="#testName"></use>
                </svg>',
            ],
            'compat attributes' => [
                ['testName', '', ['class' => 'testClass', 'alt' => 'testAlt']],
                '<svg alt="testAlt" class="icon icon-svg testClass" viewbox="0 0 17 17">
                    <use xlink:href="#testName"></use>
                </svg>',
            ],
            'arbitrary attributes' => [
                ['testName', '', ['data-test' => 'test', 'data-xss' => "\"><script>alert('hi')</script>"]],
                '<svg
                    alt="testName"
                    class="icon icon-svg"
                    viewbox="0 0 17 17"
                    data-test="test"
                    data-xss="&quot;&gt;&lt;script&gt;alert(\'hi\')&lt;/script&gt;"
                >
                    <use xlink:href="#testName"></use>
                </svg>',
            ],
        ];
    }

    /**
     * Tests for the heading function.
     *
     * @param array $params
     * @param string $expectedHtml
     *
     * @dataProvider provideHeadingArgs
     */
    public function testDashboardHeading(array $params, string $expectedHtml) {
        $actual = heading(...$params);
        $this->assertHtmlStringEqualsHtmlString($expectedHtml, $actual);
    }

    /**
     * @return array
     */
    public function provideHeadingArgs(): array {
        return [
            'simple title' => [
                ["Hello! <dont-escape-me></dont-escape-me> me once"],
                "
                <header class=header-block>
                    <div class=title-block>
                    <h1>Hello!<dont-escape-me></dont-escape-me> me once</h1></div>
                </header>",
            ],
            'title with return' => [
                [
                    'Hello',
                    '',
                    '',
                    '',
                    'https://test.com/back',
                ],
                "<header class=header-block>
                    <div class=title-block>
                        <a aria-label=Return class='btn btn-icon btn-return' href=https://test.com/back><SVG /></a>
                        <h1>Hello</h1>
                    </div>
                </header>"
            ],
            'with buttons' => [
                [
                    'Hello',
                    'button',
                    'http://test.com/button',
                    ['data-test' => 'test'],
                    'https://test.com/back',
                ],
                "<header class=header-block>
                    <div class=title-block>
                        <a aria-label=Return class='btn btn-icon btn-return' href=https://test.com/back><SVG /></a>
                        <h1>Hello</h1>
                    </div>
                    <div class=btn-container>
                        <a class='btn btn-primary' data-test=test href=http://test.com/button>button</a>
                    </div>
                </header>"
            ]
        ];
    }
}
