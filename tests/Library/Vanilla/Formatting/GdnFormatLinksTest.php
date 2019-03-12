<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use PHPUnit\Framework\TestCase;
use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\Library\Vanilla\Formatting\AssertsFixtureRenderingTrait;
use Gdn_Format;

/**
 * Unit tests for Gdn_Format::links().
 */
class GdnFormatLinksTest extends TestCase {

    use AssertsFixtureRenderingTrait;
    use BootstrapTrait {
        setUpBeforeClass as bootstrapSetupBefore;
    }

    /** @var MockConfig */
    private static $config;

    /**
     * Initialize configuration.
     */
    public static function setUpBeforeClass() {
        self::bootstrapSetupBefore();
        self::$config = new MockConfig();
        self::$container
            ->setInstance(ConfigurationInterface::class, self::$config);
    }

    /**
     * Reset config before every test.
     */
    protected function setUp() {
        parent::setUp();
        self::$config->reset();
    }

    /**
     * Restore various static Gdn_Format values.
     */
    protected function tearDown() {
        parent::tearDown();
        Gdn_Format::$FormatLinks = true;
        Gdn_Format::$DisplayNoFollow = true;
    }

    /**
     * Testing a simple link conversion.
     */
    public function testSimpleLink() {
        $input = 'https://test.com';
        $expected = "<a href='https://test.com' rel='nofollow'>https://test.com</a>";
        $output = Gdn_Format::links($input);
        $this->assertHtmlStringEqualsHtmlString($expected, $output);
    }
    
    /**
     * Testing a link with Right-To-Left character override.
     */
    public function testRightLeftOverrideLink() {
        $input = 'https://‮test.com';
        $expected = "<a href='https://test.com' rel='nofollow'>https://test.com</a>";
        $output = Gdn_Format::links($input);
        $this->assertHtmlStringEqualsHtmlString($expected, $output);
    }
    /**
     * Test that autolinking ignore various puncation marks at the end of them.
     *
     * @param string $punc The puncuation marks to check.
     *
     * @dataProvider providePuncuation
     */
    public function testPunctuation(string $punc) {
        $input = "https://test.com{$punc} Other text";
        $expected = <<<HTML
<a href='https://test.com' rel='nofollow'>https://test.com</a>{$punc} Other text
HTML;
        $output = Gdn_Format::links($input);
        $this->assertHtmlStringEqualsHtmlString($expected, $output);
    }

    /**
     * @return array
     */
    public function providePuncuation(): array {
        return [
            ['.'],
            ['?'],
            ['!'],
            [','],
            [':'],
            [';'],
            ['&nbsp;'],
        ];
    }

    /**
     * Test link formatting when nofollow is disabled.
     *
     * The following flags are set so that the static values are restored properly.
     */
    public function testNoFollowDisabled() {
        Gdn_Format::$DisplayNoFollow = false;
        $input = 'https://test.com';
        $expected = "<a href='https://test.com'>https://test.com</a>";
        $output = Gdn_Format::links($input);
        $this->assertHtmlStringEqualsHtmlString($expected, $output);
    }

    /**
     * Test link formatting when 'WarnLeaving' is enabled.
     *
     * @param string $url
     * @param string $target
     *
     * @dataProvider warnLeavingProvider
     */
    public function testWarnLeaving(string $url, string $target) {
        self::$config->loadData(['Garden.Format.WarnLeaving' => true]);
        $body = htmlspecialchars(rawurldecode($url), ENT_QUOTES, 'UTF-8');
        $expected = <<<HTML
<a class=Popup href="$target">$body</a>
HTML;
        $output = Gdn_Format::links($url);
        $this->assertHtmlStringEqualsHtmlString($expected, $output);
    }

    /**
     * Test link formatting when 'WarnLeaving' is enabled and links are already preformed.
     *
     * This test shows that currently the "Popup" class is not applied to this type of link.
     * This test should not indicate that is necessarily desired behaviour, but is provided
     * to get a handle on what Gdn_Format _currently_ does.
     *
     * @param string $url
     * @param string $target
     *
     * @dataProvider warnLeavingProvider
     */
    public function testAlreadyFormattedWarnLeaving(string $url, string $target) {
        self::$config->loadData(['Garden.Format.WarnLeaving' => true]);
        $input = <<<HTML
<a href="$target">CustomSetBody</a>
HTML;
        $expected = <<<HTML
<a href="$target">CustomSetBody</a>
HTML;

        $output = Gdn_Format::links($input);
        $this->assertHtmlStringEqualsHtmlString($expected, $output);
    }

    /**
     * Assert that content is passed through unmodified when disabled in the config.
     *
     * The following flags are set so that the static values are restored properly.
     */
    public function testLinkFormattingDisabled() {
        self::$config->loadData(['Garden.Format.Links' => false]);
        $inputOutput = 'https://test.com';
        $output = Gdn_Format::links($inputOutput);
        $this->assertSame($inputOutput, $output);

        // The other mode of warn leaving is done by settings a static value.
        Gdn_Format::$FormatLinks = false;
        self::$config->loadData(['Garden.Format.Links' => true]);
        $inputOutput = 'https://test.com';
        $output = Gdn_Format::links($inputOutput);
        $this->assertSame($inputOutput, $output);
    }

    /**
     * @return array
     */
    public function warnLeavingProvider(): array {
        return [
            ['https://test.com', "/gdnformatlinkstest/home/leaving?target=https%3A%2F%2Ftest.com"],
            // Try some encoded values.
            [
                'https://test.com/path?query=%5B%27one%27%2C%20%27two%27%2C%20%27three%27%5D',
                '/gdnformatlinkstest/home/leaving?' .
                'target=https%3A%2F%2Ftest.com%2Fpath%3Fquery%3D%255B%2527one%2527%252C%2520%2527two%2527%252C%2520%2527three%2527%255D',
            ],
        ];
    }

    /**
     * Test formatting with trusted domains.
     *
     * This test exposed that nofollow is not applied consistently in Gdn_Format::links().
     * This is not desired behaviour. A future refactoring that either leaves enforcement of nofollow
     * to the Gdn_Format::htmlfilter or enforces it consistently should be allowed to modify this test.
     */
    public function testTrustedDomains() {
        self::$config->loadData([
            'Garden.TrustedDomains' => ['trusted.com', 'trusted2.com'],
            'Garden.Format.WarnLeaving' => true,
        ]);
        $input = <<<HTML
https://trusted.com/someLink
<a href="https://trusted2.com/otherLink">SomeLink</a>
https://evil.com
<p><a href="https://evenEviler.com">Super Evil</a></p>
<a href="/gdnformatlinkstest/discussions/1">Self Link</a>
http://vanilla.test/gdnformatlinkstest/discussions/1
HTML;

        $expected = <<<HTML
<a href=https://trusted.com/someLink rel=nofollow>https://trusted.com/someLink</a>
<a href=https://trusted2.com/otherLink>SomeLink</a>
<a class=Popup href="/gdnformatlinkstest/home/leaving?target=https%3A%2F%2Fevil.com">https://evil.com</a>
<p>
<a class=Popup href="/gdnformatlinkstest/home/leaving?target=https%3A%2F%2FevenEviler.com">Super Evil</a>
</p>
<a href=/gdnformatlinkstest/discussions/1>Self Link</a>
<a href=http://vanilla.test/gdnformatlinkstest/discussions/1 rel=nofollow>http://vanilla.test/gdnformatlinkstest/discussions/1</a>
HTML;

        $output = Gdn_Format::links($input, true);
        $this->assertHtmlStringEqualsHtmlString($expected, $output);
    }

    /**
     * Test link structures that are nested inside of other HTML.
     */
    public function testNestedLinks() {
        $input = <<<HTML
<div><div>https://test.com</div></div>
<a href="/test.com">https://othertest.com</a>
<code>https://test.com</code>
HTML;
        $expected = <<<HTML
<div><div><a href=https://test.com rel=nofollow>https://test.com</a></div></div>
<a href=/test.com>https://othertest.com</a>
<code>https://test.com</code>
HTML;

        $output = Gdn_Format::links($input);
        $this->assertHtmlStringEqualsHtmlString($expected, $output);
    }
}
