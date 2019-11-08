/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ITranslationLanguageHandler } from "@library/content/translationGrid/TranslationLanguageHandler";

export const translationGridData: ITranslationLanguageHandler = {
    i18nLocales: [
        {
            displayNames: {
                ca: "Anglès",
                en: "English",
                zh: "英文",
            },
            localeID: "en",
            localeKey: "en",
            regionalKey: "en",
        },
        {
            displayNames: {
                ca: "Xinès",
                en: "Chinese",
                zh: "中文",
            },
            localeID: "vf_zh",
            localeKey: "zh",
            regionalKey: "zh",
        },
        {
            displayNames: {
                ca: "Català",
                en: "Catalan",
                zh: "加泰罗尼亚文",
            },
            localeID: "vf_ca",
            localeKey: "ca",
            regionalKey: "ca",
        },
        {
            displayNames: {
                ca: "Francès",
                en: "French",
                fr: "Français",
                zh: "法文",
            },
            localeID: "vf_fr",
            localeKey: "fr",
            regionalKey: "fr",
        },
    ],
    newTranslationData: [
        {
            key: "name",
            locale: "fr",
            source: "English name",
            translation: "French name",
            translationStatus: "translated",
            multiLine: true,
        },
        {
            key: "description",
            locale: "fr",
            source: "English description",
            translation: "",
            translationStatus: "not-translated",
            multiLine: true,
        },
        {
            key: "comments",
            locale: "fr",
            source: "English comments",
            translation: "French comments",
            translationStatus: "translated",
            multiLine: true,
        },
        {
            key: "name",
            locale: "ca",
            source: "English name",
            translation: "Catala name",
            translationStatus: "translated",
            multiLine: true,
        },
        {
            key: "description",
            locale: "ca",
            source: "English description",
            translation: "Catala description",
            translationStatus: "translated",
            multiLine: true,
        },
        {
            key: "comments",
            locale: "ca",
            source: "English comments",
            translation: "",
            translationStatus: "not-translated",
            multiLine: true,
        },
    ],
};
