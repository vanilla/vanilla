<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\IsolatedTestCase;
use VanillaTests\Library\Vanilla\Formatting\AssertsFixtureRenderingTrait;
use Gdn_Format;

/**
 * Unit tests for Gdn_Format::links().
 *
 * Isolated test case is being used here because of the static nature of some config options.
 */
class GdnFormatLinksTest extends IsolatedTestCase {

    use AssertsFixtureRenderingTrait;
    use BootstrapTrait;

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
     * Test link formatting when nofollow is disabled.
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
        $this->useConfig(['Garden.Format.WarnLeaving' => true]);
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
        $this->useConfig(['Garden.Format.WarnLeaving' => true]);
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
     */
    public function testLinkFormattingDisabled() {
        $this->useConfig(['Garden.Format.Links' => false]);
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
        $this->useConfig(['Garden.TrustedDomains' => ['https://trusted.com', 'https://trusted2.com']]);
        $input = <<<HTML
https://trusted.com/someLink
<a href="https://trusted2.com/otherLink">SomeLink</a>
https://evil.com
<a href="https://evenEviler.com">Super Evil</a>
<a href="/gdnformatlinkstest/discussions/1">Self Link</a>
http://vanilla.test/gdnformatlinkstest/discussions/1
HTML;

        $expected = <<<HTML
<a href="https://trusted.com/someLink" rel="nofollow">https://trusted.com/someLink</a>
<a href="https://trusted2.com/otherLink">SomeLink</a>
<a href="https://evil.com" rel="nofollow">https://evil.com</a>
<a href="https://evenEviler.com">Super Evil</a>
<a href="/gdnformatlinkstest/discussions/1">Self Link</a>
<a href="http://vanilla.test/gdnformatlinkstest/discussions/1" rel="nofollow">http://vanilla.test/gdnformatlinkstest/discussions/1</a>
HTML;

        $output = Gdn_Format::links($input);
        $this->assertHtmlStringEqualsHtmlString($expected, $output);
    }

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

    /**
     * Setup the container to use a mock configuration object with the provided configuration values.
     *
     * @param array $config
     */
    private function useConfig(array $config) {
        $config = new MockConfig($config);
        self::$container
            ->setInstance(ConfigurationInterface::class, $config);
    }
}
