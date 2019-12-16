/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { t } from "@library/utility/appUtils";
import { variableFactory, useThemeCache } from "@library/styles/styleUtils";
import { getCurrentLocale } from "@vanilla/i18n";
import { ITitleBarNav } from "./mebox/pieces/TitleBarNavItem";

export const navigationVariables = useThemeCache(() => {
    const makeVars = variableFactory("navigation");

    const navItems: {
        [language: string]: ITitleBarNav[];
    } = makeVars("navItems", {
        default: [
            {
                to: "/categories",
                children: t("Categories"),
            },
            {
                to: "/discussions",
                children: t("Discussions"),
            },
            {
                to: "/kb",
                children: t("Help Menu", "Help"),
                permission: "kb.view",
            },
        ],
    });

    console.log(navItems);

    const currentLocale = getCurrentLocale();
    console.log(currentLocale);
    const getNavItemsForLocale = (locale = currentLocale): ITitleBarNav[] => {
        if (locale in navItems) {
            return navItems[locale];
        } else {
            return navItems.default;
        }
    };

    return { navItems, getNavItemsForLocale };
});
