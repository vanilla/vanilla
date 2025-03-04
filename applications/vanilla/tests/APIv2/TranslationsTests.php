<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace APIv2;

use Garden\Web\Exception\ClientException;
use Gdn_Configuration;
use Vanilla\Formatting\FormatService;
use Vanilla\Forum\Models\TranslationModel;
use Vanilla\Forum\Models\TranslationPropertyModel;
use Vanilla\Translation\TranslationManager;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Translation\MockTranslationFormat;
use VanillaTests\Translation\MockTranslationService;

/**
 * Test Translations APIv2 endpoints.
 */
class TranslationsTests extends AbstractAPIv2Test
{
    /**
     * {@inheritdoc}
     */
    public function setup(): void
    {
        parent::setUp();
    }

    /**
     * Setup routine, run before the test class is instantiated.
     */
    public static function setupBeforeClass(): void
    {
        parent::setUpBeforeClass();
        /** @var Gdn_Configuration $config */
        $config = static::container()->get(Gdn_Configuration::class);
        $config->set("EnabledLocales.vf_fr", true);
    }

    /**
     * Test Post /translations
     */
    public function testPostResource()
    {
        $resource = [
            "name" => "resource one name",
            "sourceLocale" => "en",
            "urlCode" => "resourceOne",
        ];

        $result = $this->api()->post("translations", $resource);
        $this->assertEquals(201, $result->getStatusCode());
    }

    /**
     *  Post /translations failure
     *
     * @depends testPostResource
     */
    public function testPostResourceFailure()
    {
        $this->expectException(ClientException::class);
        $resource = [
            "name" => "resource one name",
            "sourceLocale" => "en",
            "urlCode" => "resourceOne",
        ];

        $this->api()->post("translations", $resource);
    }

    /**
     * Patch /translations/:resourceUrlCode
     *
     * @param array $record
     * @param string $key
     * @param string $translation
     *
     * @depends      testPostResource
     * @dataProvider translationsPropertyProvider
     */
    public function testPatchTranslations($record, $key, $translation)
    {
        $this->api()->patch("translations/resourceOne", $record);

        $record = reset($record);

        /** @var TranslationPropertyModel $translationPropertyModel */
        $translationPropertyModel = self::container()->get(TranslationPropertyModel::class);
        $translationProperty = $translationPropertyModel->getTranslationProperty($record);
        $result = $translationPropertyModel->get([
            "translationPropertyKey" => $translationProperty["translationPropertyKey"],
        ]);
        $this->assertEquals($key, $result[0]["translationPropertyKey"]);

        /** @var TranslationModel $translationModel */
        $translationModel = self::container()->get(TranslationModel::class);
        $result = $translationModel->get(["translationPropertyKey" => $result[0]["translationPropertyKey"]]);
        $this->assertEquals($translation, $result[0]["translation"]);
    }

    /**
     * Provider for Patch /translations/:resourceUrlCode
     *
     * @return array
     */
    public function translationsPropertyProvider(): array
    {
        return [
            [
                [
                    [
                        "recordType" => "recordTypeOne",
                        "recordID" => 8,
                        "propertyName" => "name",
                        "locale" => "en",
                        "translation" => "english recordTypeOne name",
                    ],
                ],
                "recordTypeOne.8.name",
                "english recordTypeOne name",
            ],
            [
                [
                    [
                        "recordType" => "recordTypeTwo",
                        "recordID" => 9,
                        "propertyName" => "description",
                        "locale" => "en",
                        "translation" => "english recordTypeTwo cat description",
                    ],
                ],
                "recordTypeTwo.9.description",
                "english recordTypeTwo cat description",
            ],
            [
                [
                    [
                        "recordType" => "recordTypeTwo",
                        "recordKey" => "recordTryTwoKey",
                        "propertyName" => "site",
                        "locale" => "en",
                        "translation" => "english recordTypeTwo site",
                    ],
                ],
                "recordTypeTwo.recordTryTwoKey.site",
                "english recordTypeTwo site",
            ],
            [
                [
                    [
                        "recordType" => "recordTypeThree",
                        "propertyName" => "name",
                        "locale" => "fr",
                        "translation" => "name",
                    ],
                ],
                "recordTypeThree..name",
                "name",
            ],
        ];
    }

    /**
     * Test GET /translation/:resourceUrlCode
     *
     * @param array $query
     * @param int $count
     * @depends testPatchTranslations
     * @dataProvider getTranslationsProvider
     */
    public function testGetTranslations($query, $count)
    {
        $result = $this->api()
            ->get("translations/resourceOne", $query)
            ->getBody();

        $this->assertEquals($count, count($result));
    }

    /**
     * Provider for GET /translations/:resourceUrlCode
     *
     * @return array
     */
    public function getTranslationsProvider(): array
    {
        return [
            [
                [
                    "recordType" => "recordTypeOne",
                    "recordIDs" => [8],
                    "locale" => "en",
                ],
                1,
            ],
            [
                [
                    "locale" => "en",
                ],
                3,
            ],
            [
                [
                    "locale" => "fr",
                ],
                1,
            ],
            [
                [
                    "recordType" => "recordTypeThree",
                    "locale" => "fr",
                ],
                1,
            ],
            [
                [
                    "recordType" => "recordTypeThree",
                    "locale" => "fr",
                    "recordIDs" => [8],
                    "recordKeys" => ["recordKey3"],
                ],
                0,
            ],
        ];
    }

    /**
     * Test Deleting a translation.
     *
     * @depends testPostResource
     */
    public function testPatchDeleteTranslation()
    {
        $record = [
            "recordType" => "recordTypeOne",
            "recordID" => 8,
            "propertyName" => "category-name",
            "locale" => "en",
            "translation" => "english recordTypeOne category-name",
        ];

        $this->api()->patch("translations/resourceOne", [$record]);

        unset($record["translation"]);

        $result = $this->api()->patch("translations/resourceOne/remove", [$record]);
        $this->assertEquals(200, $result->getStatusCode());

        unset($record["recordID"]);
        $record["recordIDs"] = [8];

        $result = $this->api()->get("translations/resourceOne", $record);
        $this->assertEquals(0, count($result->getBody()));
    }
}
