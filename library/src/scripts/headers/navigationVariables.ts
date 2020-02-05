/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { getMeta, t } from "@library/utility/appUtils";
import { variableFactory, useThemeCache } from "@library/styles/styleUtils";
import { getCurrentLocale } from "@vanilla/i18n";
import { ITitleBarNav } from "./mebox/pieces/TitleBarNavItem";

type INavItemGenerator = () => ITitleBarNav;

const navItemGenerators: INavItemGenerator[] = [];

export function registerDefaultNavItem(navItemGetter: INavItemGenerator) {
    navItemGenerators.push(navItemGetter);
}

export function generateNavItems() {
    const forumEnabled = getMeta("siteSection.apps.forum");
    const kbEnabled = getMeta("siteSection.apps.knowledgeBase");

    if (forumEnabled) {
        registerDefaultNavItem(() => {
            return {
                to: "/discussions",
                children: t("Discussions"),
            };
        });
        registerDefaultNavItem(() => {
            return {
                to: "/categories",
                children: t("Categories"),
            };
        });

        if (kbEnabled) {
            registerDefaultNavItem(() => {
                return {
                    children: t("Help Menu", "Help"),
                    permission: "kb.view",
                    to: "/kb",
                };
            });
        }
    }
}

export const navigationVariables = useThemeCache(() => {
    const makeVars = variableFactory("navigation");

    const navItems: {
        [language: string]: ITitleBarNav[];
    } = makeVars("navItems", {
        default: [...navItemGenerators.map(generator => generator())],
    });

    const currentLocale = getCurrentLocale();

    const getNavItemsForLocale = (locale = currentLocale): ITitleBarNav[] => {
        if (locale in navItems) {
            return navItems[locale];
        } else {
            return navItems.default;
        }
    };

    return { navItems, getNavItemsForLocale };
});
