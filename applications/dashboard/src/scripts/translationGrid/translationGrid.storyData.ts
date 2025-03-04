/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { TranslationPropertyType, ITranslationProperty, ILocale } from "@vanilla/i18n";

export function makeTestTranslationProperty(key: string, source: string, isMultiLine?: boolean): ITranslationProperty {
    return {
        recordType: "custom",
        propertyType: isMultiLine ? TranslationPropertyType.TEXT_MULTILINE : TranslationPropertyType.TEXT,
        recordID: 1312,
        propertyValidation: {},
        propertyName: "test",
        sourceText: source,
    };
}

export const localeData: ILocale[] = [
    {
        displayNames: {
            ca: "Anglès",
            en: "English",
            zh: "英文",
        },
        localeID: "VF_en-asdf",
        localeKey: "en",
        regionalKey: "en",
        translationService: null,
    },
    {
        displayNames: {
            ca: "Xinès",
            en: "Chinese",
            zh: "中文",
        },
        localeID: "VF_zh-asdfasdf",
        localeKey: "zh",
        regionalKey: "zh",
        translationService: null,
    },
    {
        displayNames: {
            ca: "Català",
            en: "Catalan",
            zh: "加泰罗尼亚文",
        },
        localeID: "VF_ca-asdf",
        localeKey: "ca",
        regionalKey: "ca",
        translationService: null,
    },
    {
        displayNames: {
            ca: "Francès",
            en: "French",
            fr: "Français",
            zh: "法文",
        },
        localeID: "VF_fr-asdf",
        localeKey: "fr",
        regionalKey: "fr",
        translationService: null,
    },
];
