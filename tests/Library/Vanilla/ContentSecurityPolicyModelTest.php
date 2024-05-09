<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\Error\Warning;
use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\Fixtures\MockUASniffer;
use Vanilla\Logger;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyProviderInterface;
use VanillaTests\Fixtures\MockConfig;
use VanillaTests\MinimalContainerTestCase;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;
use Vanilla\Web\ContentSecurityPolicy\DefaultContentSecurityPolicyProvider;
use Vanilla\Web\ContentSecurityPolicy\EmbedWhitelistContentSecurityPolicyProvider;

/**
 * Some basic tests for the `ContentSecurityPolicyModel`.
 */
class ContentSecurityPolicyModelTest extends MinimalContainerTestCase
{
    /**
     * @var ContentSecurityPolicyModel
     */
    private $cspModel;

    /**
     * Get a new model for each test.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->cspModel = $this->container()->get(ContentSecurityPolicyModel::class);
    }

    /**
     * Test CSP model with DefaultContentSecurityPolicyProvider
     */
    public function testCspModelDefaultProvider()
    {
        /** @var ContentSecurityPolicyProviderInterface $defaultProvider */
        $defaultProvider = $this->container()->get(DefaultContentSecurityPolicyProvider::class);
        $this->cspModel->addProvider($defaultProvider);
        $header = $this->cspModel->getHeaderString();
        $this->assertStringEndsWith('\'self\'', $header);
        $this->assertStringContainsString("frame-ancestors ", $header);
        $this->assertStringNotContainsString("unsafe-eval", $header);
        return $header;
    }

    public function testCspModelDefaultProviderWithSctrictDynamic()
    {
        $c = $this->container()->get(\Gdn_Configuration::class);
        $c->set(\SettingsController::CONFIG_CSP_STRICT_DYNAMIC, true, true, false);
        $header = $this->testCspModelDefaultProvider();
        $this->assertStringContainsString("strict-dynamic", $header);
    }

    /**
     * Test CSP model with DefaultContentSecurityPolicyProvider with Garden.Embed.Allow
     *
     * @depends testCspModelDefaultProvider
     */
    public function testCspModelDefaultProviderEmbedEnabled()
    {
        /** @var \Gdn_Configuration $config */
        $config = $this->container()->get(ConfigurationInterface::class);
        $config->set("Garden.Embed.Allow", true);
        /** @var ContentSecurityPolicyProviderInterface $defaultProvider */
        $defaultProvider = $this->container()->get(DefaultContentSecurityPolicyProvider::class);
        $this->cspModel->addProvider($defaultProvider);
        $header = $this->cspModel->getHeaderString();
        $this->assertStringEndsWith('\'self\'', $header);
        $this->assertStringContainsString("frame-ancestors ", $header);
        $this->assertStringNotContainsString("unsafe-eval", $header);
    }

    /**
     * Test CSP model with EmbedWhitelistContentSecurityPolicyProvider
     */
    public function testCspModeleEmbedWhiteListProvider()
    {
        /** @var ContentSecurityPolicyProviderInterface $embedWhiteListProvider */
        $embedWhiteListProvider = $this->container()->get(EmbedWhitelistContentSecurityPolicyProvider::class);
        $this->cspModel->addProvider($embedWhiteListProvider);
        $header = $this->cspModel->getHeaderString();
        $this->assertStringEndsWith("https://www.instagram.com/embed.js", $header);
        $this->assertStringNotContainsString("unsafe-eval", $header);
        $this->assertStringNotContainsString("frame-ancestors ", $header);
    }

    /**
     * Test CSP model with DefaultContentSecurityPolicyProvider, EmbedWhitelistContentSecurityPolicyProvider
     *     all enabled together
     *
     * @depends testCspModelDefaultProviderEmbedEnabled
     */
    public function testCspModelAllProviders()
    {
        /** @var ContentSecurityPolicyProviderInterface $defaultProvider */
        $defaultProvider = $this->container()->get(DefaultContentSecurityPolicyProvider::class);
        $this->cspModel->addProvider($defaultProvider);

        /** @var ContentSecurityPolicyProviderInterface $embedWhiteListProvider */
        $embedWhiteListProvider = $this->container()->get(EmbedWhitelistContentSecurityPolicyProvider::class);
        $this->cspModel->addProvider($embedWhiteListProvider);

        $header = $this->cspModel->getHeaderString();
        $this->assertStringContainsString('\'self\'', $header);
        $this->assertStringContainsString("https://www.instagram.com/embed.js", $header);
        $this->assertStringContainsString("frame-ancestors ", $header);
    }

    /**
     * Test our fallback X-Frame-Options
     *
     * @param bool $allowEmbed
     * @param string $trustedDomains
     * @param bool $isIE11
     * @param mixed $expected
     *
     * @dataProvider provideXFrameOptions
     */
    public function testXFrameOptions(bool $allowEmbed, string $trustedDomains, bool $isIE11, $expected)
    {
        $config = new MockConfig([
            "Garden.TrustedDomains" => $trustedDomains,
            "Garden.Embed.Allow" => $allowEmbed,
        ]);

        $uaSniffer = new MockUASniffer($isIE11);
        $logger = new Logger();

        $cspModel = new ContentSecurityPolicyModel($uaSniffer, $logger, \Gdn::config());
        $provider = new DefaultContentSecurityPolicyProvider($config);
        $cspModel->addProvider($provider);

        if ($expected === Warning::class) {
            $this->expectWarning();
            $cspModel->getXFrameString();
        } else {
            $this->assertEquals($expected, $cspModel->getXFrameString());
        }
    }

    /**
     * @return array
     */
    public function provideXFrameOptions(): array
    {
        return [
            [false, "", true, "SAMEORIGIN"],
            [false, "", false, "SAMEORIGIN"],
            [true, "http://embed.com", false, "ALLOW-FROM http://embed.com"],
            [true, "", false, "SAMEORIGIN"],
            [true, "http://test.com http://other.com", false, null],
            [true, "http://test.com http://other.com", true, Warning::class],
        ];
    }
}
