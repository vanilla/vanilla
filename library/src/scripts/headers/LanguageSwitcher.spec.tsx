/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { fireEvent, render, screen } from "@testing-library/react";
import LanganuageSwitcher from "@library/headers/LanguageSwitcher";
import { LocaleContext } from "@vanilla/i18n";

const LOCALES = [
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
];

describe("LanguageSwitcher", async () => {
    beforeEach(() => {
        // @ts-ignore
        delete window.location;
        window.location = new URL("https://vanilla-site.com/en") as any;

        render(
            <LocaleContext.Provider
                value={{
                    currentLocale: "en",
                    locales: LOCALES,
                }}
            >
                <LanganuageSwitcher />
            </LocaleContext.Provider>,
        );
    });

    it("renders a dropdown button which is collapsed by default", () => {
        const button = screen.getByRole("button");
        expect(button).toHaveAccessibleName("Choose language");

        expect(screen.queryByRole("menu")).not.toBeInTheDocument();
    });

    it("shows the list of languages with a link to the same page in each locale", () => {
        const button = screen.getByRole("button");
        fireEvent.click(button);

        const links = screen.getAllByRole("link");
        expect(links).toHaveLength(LOCALES.length);

        expect(links[0]).toHaveTextContent("English");
        expect(links[0]).toHaveAttribute("href", "https://vanilla-site.com/en");

        expect(links[1]).toHaveTextContent("Français (Canada)");
        expect(links[1]).toHaveAttribute("href", "https://vanilla-site.com/fr_CA");

        expect(links[2]).toHaveTextContent("Melayu (Malaysia)");
        expect(links[2]).toHaveAttribute("href", "https://vanilla-site.com/ms_MY");

        expect(links[3]).toHaveTextContent("Español");
        expect(links[3]).toHaveAttribute("href", "https://vanilla-site.com/es");

        expect(links[4]).toHaveTextContent("Español (México)");
        expect(links[4]).toHaveAttribute("href", "https://vanilla-site.com/es_MX");

        expect(links[5]).toHaveTextContent("한국어");
        expect(links[5]).toHaveAttribute("href", "https://vanilla-site.com/ko");
    });

    it("shows a checkmark next to the current language", () => {
        const button = screen.getByRole("button");
        fireEvent.click(button);

        const links = screen.getAllByRole("link");
        expect(links).toHaveLength(LOCALES.length);

        expect(links[0]).toHaveTextContent("English");
        expect(links[0]).toContainHTML('<svg class="icon icon-checkmark"');
    });
});
