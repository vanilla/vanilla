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

        // set config for disucussion filters test
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get('Config');
        $config->set('Vanilla.EnableCategoryFollowing', true, true, false);
    }

    /**
     * Cleanup the html normalize trait.
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->shouldReplaceSVGs = true;
    }

    /**
     * Provide data for testing the userAnchor function.
     *
     * @return array[]
     */
    public function provideUserAnchorValues(): array {
        $result = [
            "Simple user array" => [
                [
                    "UserID" => 1,
                    "Name" => "Foo",
                ],
                null,
                '<a href="/renderfunctionstest/profile/Foo" class="js-userCard" data-userid="1">Foo</a>'
            ],
            "Simple user object" => [
                (object)[
                    "UserID" => 2,
                    "Name" => "Bar",
                ],
                null,
                '<a href="/renderfunctionstest/profile/Bar" class="js-userCard" data-userid="2">Bar</a>'
            ],
            "Post array with prefix" => [
                [
                    "InsertUserID" => 1,
                    "InsertName" => "Foo",
                ],
                ["Px" => "Insert"],
                '<a href="/renderfunctionstest/profile/Foo" class="js-userCard" data-userid="1">Foo</a>'
            ],
            "Post object with prefix" => [
                 (object)[
                    "InsertUserID" => 2,
                    "InsertName" => "Bar",
                 ],
                 ["Px" => "Insert"],
                 '<a href="/renderfunctionstest/profile/Bar" class="js-userCard" data-userid="2">Bar</a>'
            ],
        ];
        return $result;
    }

    /**
     * Verify result of simple userAnchor calls.
     *
     * @param array|object $value
     * @param array|null $options
     * @param string $expected
     * @dataProvider provideUserAnchorValues
     */
    public function testUserAnchor($value, $options, $expected): void {
        $actual = userAnchor($value, null, $options);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test userPhoto.
     *
     * @param array|object $value
     * @param array|null $options
     * @param string $expected
     * @dataProvider provideUserPhotoValues
     */
    public function testUserPhoto($value, $options, $expected): void {
        $actual = userPhoto($value, $options);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide data for testing the userPhoto function.
     *
     * @return array[]
     */
    public function provideUserPhotoValues(): array {
        $result = [
            "User array" => [
                [
                    "UserID" => 1,
                    "Name" => "System",
                ],
                null,
                '<a title="System" href="/renderfunctionstest/profile/System" class="PhotoWrap js-userCard" aria-label="User: &quot;'.
                'System&quot;" data-userid="1"><img src="https://vanilla.test/renderfunctionstest/applications/dashboard/design/images/usericon.png"'.
                ' alt="System" class="ProfilePhoto ProfilePhotoMedium" data-fallback="avatar" /></a>'
            ],
            "User array no id" => [
                [
                    "UserID" => null
                ],
                null,
                '<a title="Unknown" href="/renderfunctionstest/profile/" class="PhotoWrap js-userCard" aria-label="User: &quot;Unknown&quot;"'.
                ' data-userid=""><img src="http://vanilla.test/renderfunctionstest/applications/dashboard/design/images/defaulticon.png"'.
                ' alt="Unknown" class="ProfilePhoto ProfilePhotoMedium" data-fallback="avatar" /></a>'
            ],
            "User object" => [
                (object)[
                    "UserID" => 1,
                    "Name" => "System"
                ],
                null,
                '<a title="System" href="/renderfunctionstest/profile/System" class="PhotoWrap js-userCard" aria-label="User: &quot;'.
                'System&quot;" data-userid="1"><img src="https://vanilla.test/renderfunctionstest/applications/dashboard/design/images/usericon.png"'.
                ' alt="System" class="ProfilePhoto ProfilePhotoMedium" data-fallback="avatar" /></a>'
            ],
            "User object no id" => [
                (object)[
                    "UserID" => null
                ],
                null,
                '<a title="Unknown" href="/renderfunctionstest/profile/" class="PhotoWrap js-userCard" aria-label="User: &quot;Unknown&quot;"'.
                ' data-userid=""><img src="http://vanilla.test/renderfunctionstest/applications/dashboard/design/images/defaulticon.png"'.
                ' alt="Unknown" class="ProfilePhoto ProfilePhotoMedium" data-fallback="avatar" /></a>'
            ]
        ];
        return $result;
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

    /**
     * Test for discussion filters function
     *
     * @param string $expected
     *
     * @dataProvider provideDiscussionFilters
     */
    public function testDiscussionFilters(string $expected) {
        $actual = discussionFilters();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * @return array
     */
    public function provideDiscussionFilters() {
        return ['simple filter' => [
            <<<EOT
<div class="PageControls-filters">
    <span class="ToggleFlyout selectBox selectBox-following">
        <span class="selectBox-label">View:</span>
        <span class="selectBox-main">
            <a class="FlyoutButton selectBox-toggle" href="#" rel="nofollow" role="button" tabindex="0">
                <span class="selectBox-selected">All</span>
                <span aria-label="Down Arrow" class="vanillaDropDown-arrow">▾</span>
            </a>
            <ul class="Flyout MenuItems selectBox-content" role="menu">
                <li class="isActive selectBox-item" role="menuitem">
                    <a class="dropdown-menu-link selectBox-link" href="/renderfunctionstest/discussions?save=1&amp;TransientKey=0" tabindex="0">
                        <svg class="vanillaIcon selectBox-selectedIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18">
                            <title>✓</title>
                            <polygon fill="currentColor" points="1.938,8.7 0.538,10.1 5.938,15.5 17.337,3.9 15.938,2.5 5.938,12.8"></polygon>
                        </svg>
                        <span class="selectBox-selectedText">All</span>
                    </a>
                </li>
                <li class="selectBox-item" role="menuitem">
                    <a class="dropdown-menu-link selectBox-link" href="/renderfunctionstest/discussions?followed=1&amp;save=1&amp;TransientKey=0" tabindex="0">Following</a>
                </li>
            </ul>
        </span>
    </span>
</div>
EOT
            ]
        ];
    }

    /**
     * Test for category filters function
     *
     * @param string $expected
     *
     * @dataProvider provideCategoryFilters
     */
    public function testCategoryFilters(string $expected) {
        $actual = categoryFilters();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * @return array
     */
    public function provideCategoryFilters() {
        return ['simple filter' => [
            <<<EOT
<div class="PageControls-filters">
    <span class="ToggleFlyout selectBox selectBox-following">
        <span class="selectBox-label">View:</span>
        <span class="selectBox-main">
            <a class="FlyoutButton selectBox-toggle" href=# rel=nofollow role=button tabindex="0">
                <span class="selectBox-selected">All</span>
                <span aria-label="Down Arrow" class="vanillaDropDown-arrow">▾</span>
            </a>
            <ul class="Flyout MenuItems selectBox-content" role="menu">
                <li class="isActive selectBox-item" role="menuitem">
                    <a class="dropdown-menu-link selectBox-link" href="/renderfunctionstest/categories?save=1&amp;TransientKey=0" tabindex="0">
                        <svg class="vanillaIcon selectBox-selectedIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18">
                            <title>✓</title>
                            <polygon fill="currentColor" points="1.938,8.7 0.538,10.1 5.938,15.5 17.337,3.9 15.938,2.5 5.938,12.8"></polygon>
                        </svg>
                        <span class="selectBox-selectedText">All</span>
                    </a>
                </li>
                <li class="selectBox-item" role="menuitem">
                    <a class="dropdown-menu-link selectBox-link" href="/renderfunctionstest/categories?followed=1&amp;save=1&amp;TransientKey=0" tabindex="0">Following</a>
                </li>
            </ul>
        </span>
    </span>
</div>
EOT
            ]
        ];
    }
}
