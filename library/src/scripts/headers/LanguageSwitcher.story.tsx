/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { t } from "@library/utility/appUtils";
import LanganuageSwitcher from "@library/headers/LanguageSwitcher";

import { mockSiteSection } from "@library/utility/__fixtures__/SiteSection.fixtures";
import { SiteSectionContext } from "@library/utility/SiteSectionContext";
import { LocaleContext } from "@vanilla/i18n";

export default {
    title: "LanguageSwitcher",
};

export const LanganuageSwitcherStory = () => {
    return (
        <>
            <StoryHeading depth={1}>{t("Language Switcher")}</StoryHeading>

            <SiteSectionContext.Provider value={{ siteSection: mockSiteSection }}>
                <LocaleContext.Provider
                    value={{
                        currentLocale: "en",
                        locales: [
                            {
                                localeID: "en",
                                localeKey: "en",
                                regionalKey: "en",
                                displayNames: {
                                    en: "English",
                                    fr_CA: "Anglais",
                                    ms_MY: "Inggeris",
                                    da: "Engelsk",
                                    es: "Inglés",
                                    es_MX: "Inglés",
                                    cy: "Saesneg",
                                    ko: "영어",
                                },
                                translationService: null,
                            },
                            {
                                localeID: "vf_fr_CA",
                                localeKey: "fr_CA",
                                regionalKey: "fr_CA",
                                displayNames: {
                                    en: "French (Canada)",
                                    fr_CA: "Français (Canada)",
                                    ms_MY: "Perancis (Kanada)",
                                    da: "Fransk (Canada)",
                                    es: "Francés (Canadá)",
                                    es_MX: "Francés (Canadá)",
                                    cy: "Ffrangeg (Canada)",
                                    ko: "프랑스어 (캐나다)",
                                },
                                translationService: null,
                            },
                            {
                                localeID: "vf_ms_MY",
                                localeKey: "ms_MY",
                                regionalKey: "ms_MY",
                                displayNames: {
                                    en: "Malay (Malaysia)",
                                    fr_CA: "Malais (Malaisie)",
                                    ms_MY: "Melayu (Malaysia)",
                                    da: "Malajisk (Malaysia)",
                                    es: "Malayo (Malasia)",
                                    es_MX: "Malayo (Malasia)",
                                    cy: "Maleieg (Malaysia)",
                                    ko: "말레이어 (말레이시아)",
                                },
                                translationService: "google",
                            },
                            {
                                localeID: "vf_da",
                                localeKey: "da",
                                regionalKey: "da",
                                displayNames: {
                                    en: "Danish",
                                    fr_CA: "Danois",
                                    ms_MY: "Denmark",
                                    da: "Dansk",
                                    es: "Danés",
                                    es_MX: "Danés",
                                    cy: "Daneg",
                                    ko: "덴마크어",
                                },
                                translationService: null,
                            },
                            {
                                localeID: "vf_es",
                                localeKey: "es",
                                regionalKey: "es",
                                displayNames: {
                                    en: "Spanish",
                                    fr_CA: "Espagnol",
                                    ms_MY: "Sepanyol",
                                    da: "Spansk",
                                    es: "Español",
                                    es_MX: "Español",
                                    cy: "Sbaeneg",
                                    ko: "스페인어",
                                },
                                translationService: null,
                            },
                            {
                                localeID: "vf_es_MX",
                                localeKey: "es_MX",
                                regionalKey: "es_MX",
                                displayNames: {
                                    en: "Spanish (Mexico)",
                                    fr_CA: "Espagnol (Mexique)",
                                    ms_MY: "Sepanyol (Mexico)",
                                    da: "Spansk (Mexico)",
                                    es: "Español (México)",
                                    es_MX: "Español (México)",
                                    cy: "Sbaeneg (Mecsico)",
                                    ko: "스페인어 (멕시코)",
                                },
                                translationService: null,
                            },
                            {
                                localeID: "vf_cy",
                                localeKey: "cy",
                                regionalKey: "cy",
                                displayNames: {
                                    en: "Welsh",
                                    fr_CA: "Gallois",
                                    ms_MY: "Wales",
                                    da: "Walisisk",
                                    es: "Galés",
                                    es_MX: "Galés",
                                    cy: "Cymraeg",
                                    ko: "웨일스어",
                                },
                                translationService: null,
                            },
                            {
                                localeID: "vf_ko",
                                localeKey: "ko",
                                regionalKey: "ko",
                                displayNames: {
                                    en: "Korean",
                                    fr_CA: "Coréen",
                                    ms_MY: "Korea",
                                    da: "Koreansk",
                                    es: "Coreano",
                                    es_MX: "Coreano",
                                    cy: "Coreeg",
                                    ko: "한국어",
                                },
                                translationService: null,
                            },
                        ],
                    }}
                >
                    <LanganuageSwitcher />
                </LocaleContext.Provider>
            </SiteSectionContext.Provider>
        </>
    );
};

LanganuageSwitcherStory.story = {
    name: "Language Switcher",
};
