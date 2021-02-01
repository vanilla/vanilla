<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Schema\Schema;
use LocaleModel;
use Vanilla\Contracts\Site\AbstractSiteProvider;
use Vanilla\Utility\ModelUtils;

/**
 * Constants for the scope of a record. (What types of users can view it).
 */
final class CrawlableRecordSchema {

    /**
     * The record is visible to guests.
     */
    const SCOPE_PUBLIC = "public";

    /**
     * The record is is not visible to guests.
     */
    const SCOPE_RESTRICTED = "restricted";

    /** @var string[] Locales that allow crawling of. */
    const CRAWLABLE_LOCALES = [
        "ar", "bg", "ca", "cs", "cy", "da", "de", "el", "en", "en_GB",
        "es", "es_MX", "fa", "fi", "fr", "fr_CA", "gd", "he", "hi",
        "hu", "id", "it", "ja", "ko", "ms_MY", "nl", "no", "nso",
        "pl", "pt", "pt_BR", "ro", "ru", "sk", "sr", "sv", "th", "tl",
        "tr", "uk", "ur", "vi", "zh", "zh_TW", "zu_ZA",
    ];

    const ALL_LOCALES = "all";

    const LOCALE_ANALYZERS = [
        "ar" => "arabic",
        "bg" => "bulgarian",
        "ca" => "catalan",
        "cs" => "czech",
        "cy" => "", // Welsh
        "da" => "danish",
        "de" => "german",
        "el" => "greek",
        "en" => "english",
        "en_GB" => "english",
        "es" => "spanish",
        "es_MX" => "spanish",
        "fa" => "persian",
        "fi" => "finnish",
        "fr" => "french",
        "fr_CA" => "french",
        "gd" => "", // Gaelic, Scottish Gaelic
        "he" => "", // hebrew
        "hi" => "hindi",
        "hu" => "hungarian",
        "id" => "indonesian",
        "it" => "italian",
        "ja" => "cjk", // "kuromoji", // japanese, https://www.elastic.co/guide/en/elasticsearch/plugins/current/analysis-kuromoji.html
        "ko" => "cjk", // "nori", // korean, https://www.elastic.co/guide/en/elasticsearch/plugins/current/analysis-nori.html
        "ms_MY" => "", // malay
        "nl" => "dutch",
        "no" => "norwegian",
        "nso" => "", // Northern Sotho
        "pl" => "", // polish, "stempel", https://www.elastic.co/guide/en/elasticsearch/plugins/current/analysis-stempel.html
        "pt" => "portuguese",
        "pt_BR" => "portuguese",
        "ro" => "romanian",
        "ru" => "russian",
        "sk" => "", // slovak
        "sr" => "", // serbian
        "sv" => "swedish",
        "th" => "thai",
        "tl" => "", // tagalog
        "tr" => "turkish",
        "uk" => "", // ukrainian, "ukrainian", https://www.elastic.co/guide/en/elasticsearch/plugins/current/analysis-ukrainian.html
        "ur" => "", // urdu
        "vi" => "", // vietnamese, https://github.com/duydo/elasticsearch-analysis-vietnamese
        "zh" => "cjk", // chinese
        "zh_TW" => "cjk", // chinese
        "zu_ZA" => "", // zulu
    ];

    /**
     * Create the schema for a crawlable record.
     *
     * @param string $defaultType The default type to apply if one is not specified.
     *
     * @return Schema
     */
    public static function schema(string $defaultType): Schema {
        /** @var AbstractSiteProvider $siteProvider */
        $siteProvider = \Gdn::getContainer()->get(AbstractSiteProvider::class);
        $isPrivateCommunity = \Gdn::config('Garden.PrivateCommunity', false);

        return Schema::parse([
            'scope' => [
                'type' => 'string',
                'enum' => [
                    self::SCOPE_PUBLIC,
                    self::SCOPE_RESTRICTED,
                ],
            ],
            'name:s',
            'excerpt:s' => [
                'minLength' => 0,
            ],
            'image:s?',
            'localizedID:s?',
            'locale:s?',
            'type:s' => [
                'default' => $defaultType,
            ],
            'siteID:i' => [
                'default' => $siteProvider->getOwnSite()->getSiteID(),
            ],
            'recordCollapseID:s?',
            "privacy:s?" => ["default" => "public"],
        ])->addFilter('', function ($data) use ($defaultType, $siteProvider, $isPrivateCommunity) {
            if (!isset($data['recordCollapseID'])) {
                $recordID = $data["{$defaultType}ID"] ?? randomString(10);
                $siteID = $data['siteID'] ?? $siteProvider->getOwnSite()->getSiteID();
                $data['recordCollapseID'] = "site{$siteID}_{$defaultType}{$recordID}";
            }
            if ($isPrivateCommunity) {
                $data['scope'] = self::SCOPE_RESTRICTED;
            }
            return $data;
        });
    }

    /**
     * Add localized properties to the schema provided
     *
     * @param Schema $schema
     * @return Schema
     */
    public static function localize(Schema $schema): Schema {
        $fields = [];
        foreach ($schema['properties'] as $property => $data) {
            if (true === ($data['x-localize'] ?? false)) {
                $fields[] = $property;
            }
        }
        if (!empty($fields)) {
            $schema->merge(self::getLocalesSchema($fields))
                ->addFilter('', function (array $row) use ($fields): array {
                    $locale = $row['locale'] ?? [];
                    foreach ($fields as $field) {
                        $val = $row[$field] ?? null;
                        if ($val && $locale) {
                            $row["{$field}_{$locale}"] = $val;
                        }
                    }
                    return $row;
                });
        }
        return $schema;
    }

    /**
     * Get locales schema for records.
     *
     * @return Schema
     */
    public static function getLocalesSchema(array $fields) {
        $schema = [];
        foreach (self::CRAWLABLE_LOCALES as $locale) {
            foreach ($fields as $field) {
                $schema[$field.'_'.$locale.':s?'] = ['x-analyzer' => self::LOCALE_ANALYZERS[$locale]];
            }
        }
        return Schema::parse($schema);
    }

    /**
     * Convert list of field name to localized field name when valid locale is provided
     *
     * @param array $fieldNames
     * @param string|null $locale
     * @return array
     */
    public static function localizedFieldNames(array $fieldNames, ?string $locale): array {
        if (!empty($locale) && !empty(self::LOCALE_ANALYZERS[$locale])) {
            foreach ($fieldNames as &$fieldName) {
                $fieldName .= '_'.$locale;
            }
        }
        return $fieldNames;
    }

    /**
     * Apply required crawlable schema to another existing schema.
     *
     * @param Schema $schema The schema to extend.
     * @param string $defaultType The default type to apply to the record if one does not exist.
     * @param array|bool|string $expand Only expand the schema if crawl is expanded.
     *
     * @return Schema
     */
    public static function applyExpandedSchema(Schema $schema, string $defaultType, $expand = []): Schema {
        if (ModelUtils::isExpandOption(ModelUtils::EXPAND_CRAWL, $expand)) {
            return self::localize(self::schema($defaultType)->merge($schema));
        } else {
            return $schema;
        }
    }
}
