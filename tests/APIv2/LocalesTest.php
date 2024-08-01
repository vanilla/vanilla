<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class LocalesTest
 *
 * @package VanillaTests\APIv2
 */
class LocalesTest extends AbstractAPIv2Test
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    protected static $enabledLocales = ["vf_en" => "en", "vf_zh" => "zh"];

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->configuration = \Gdn::config();
    }

    /**
     * Test Locales expandDisplayNames.
     *
     * @param string $config
     * @param string $value
     * @param array $expected
     *
     * @dataProvider provideData
     * @return void
     */
    public function testGet(string $config, string $value, array $expected): void
    {
        $this->configuration->saveToConfig($config, $value);
        $results = $this->api()
            ->get("locales")
            ->getBody();
        $this->assertEqualsCanonicalizing($expected, $results);
        $this->configuration->remove($config);
    }

    /**
     * Test that the translations endpoint returns javascript.
     */
    public function testTranslations()
    {
        // This should support running in a private community.
        $this->runWithPrivateCommunity(function () {
            $response = $this->api()->get("/locales/en/translations.js");
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals("application/javascript; charset=utf-8", $response->getHeader("content-type"));
        });
    }

    /**
     * Test that we fetch enabled locales from the index.
     */
    public function testIndex()
    {
        // This should be accesible in a private community.
        $this->runWithPrivateCommunity(function () {
            $response = $this->api()->get("/locales");
            $this->assertEquals(200, $response->getStatusCode());

            // The 3 enabled locales.
            $this->assertEquals(["en", "zh"], array_column($response->getBody(), "localeKey"));
        });
    }

    /**
     * Provide locales test data.
     *
     * @return array Data provider.
     */
    public function provideData(): array
    {
        return [
            "zh-zh" => [
                "localeOverrides.zh.zh",
                "中文1",
                [
                    [
                        "localeID" => "test_vf_en",
                        "localeKey" => "en",
                        "regionalKey" => "en",
                        "displayNames" => [
                            "en" => "English",
                            "zh" => "英语",
                        ],
                    ],
                    [
                        "localeID" => "test_vf_zh",
                        "localeKey" => "zh",
                        "regionalKey" => "zh",
                        "displayNames" => [
                            "en" => "Chinese",
                            "zh" => "中文1",
                        ],
                    ],
                ],
                "",
            ],
            "en-zh" => [
                "localeOverrides.en.zh",
                "中文2",
                [
                    [
                        "localeID" => "test_vf_en",
                        "localeKey" => "en",
                        "regionalKey" => "en",
                        "displayNames" => [
                            "en" => "English",
                            "zh" => "中文2",
                        ],
                    ],
                    [
                        "localeID" => "test_vf_zh",
                        "localeKey" => "zh",
                        "regionalKey" => "zh",
                        "displayNames" => [
                            "en" => "Chinese",
                            "zh" => "中文",
                        ],
                    ],
                ],
                "",
            ],
            "zh-en" => [
                "localeOverrides.zh.en",
                "中文3",
                [
                    [
                        "localeID" => "test_vf_en",
                        "localeKey" => "en",
                        "regionalKey" => "en",
                        "displayNames" => [
                            "en" => "English",
                            "zh" => "英语",
                        ],
                    ],
                    [
                        "localeID" => "test_vf_zh",
                        "localeKey" => "zh",
                        "regionalKey" => "zh",
                        "displayNames" => [
                            "en" => "中文3",
                            "zh" => "中文",
                        ],
                    ],
                ],
                "",
            ],
            "Override-all" => [
                "localeOverrides.zh.*",
                "中文4",
                [
                    [
                        "localeID" => "test_vf_en",
                        "localeKey" => "en",
                        "regionalKey" => "en",
                        "displayNames" => [
                            "en" => "English",
                            "zh" => "英语",
                        ],
                    ],
                    [
                        "localeID" => "test_vf_zh",
                        "localeKey" => "zh",
                        "regionalKey" => "zh",
                        "displayNames" => [
                            "en" => "中文4",
                            "zh" => "中文4",
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test that event-based locale definitions are applied to the translations API.
     */
    public function testTranslationsWithExtras()
    {
        $before = $this->api->get("/locales/en/translations")->getBody();
        $this->assertArrayNotHasKey("foo", $before);
        \Gdn::eventManager()->bindClass($this);

        $after = $this->api->get("/locales/en/translations")->getBody();
        $this->assertEquals("bar", $after["foo"]);
    }

    /**
     * Event handler mimicking vfcom's console locale loading.
     *
     * @param \Gdn_Locale $locale
     */
    public function gdn_locale_afterSet_handler(\Gdn_Locale $locale)
    {
        $translations = [
            "foo" => "bar",
        ];

        $locale->LocaleContainer->loadArray($translations, "ClientLocale", "Definition", true);
    }
}
