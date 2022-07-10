/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { getMeta, t, assetUrl, formatUrl } from "@library/utility/appUtils";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { getCurrentLocale } from "@vanilla/i18n";
import { ITitleBarNav } from "./mebox/pieces/TitleBarNavItem";
import { IThemeVariables } from "@library/theming/themeReducer";
import { getThemeVariables } from "@library/theming/getThemeVariables";
import { uuidv4 } from "@vanilla/utils";
import { DeepPartial } from "redux";
import { INavigationItemBadge } from "@library/@types/api/core";
export interface INavigationVariableItem {
    id: string;
    name: string;
    url: string;
    children?: INavigationVariableItem[];
    permission?: string;
    isCustom?: boolean;
    isHidden?: boolean;
    react?: boolean;
    badge?: INavigationItemBadge;
}

type INavItemGenerator = () => INavigationVariableItem;

const navItemGenerators: INavItemGenerator[] = [];

export function registerDefaultNavItem(navItemGetter: INavItemGenerator) {
    navItemGenerators.push(navItemGetter);
}

export const navigationVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("navigation", forcedVars);

    let navigationItems: INavigationVariableItem[] = makeVars("navigationItems", getDefaultNavItems());
    navigationItems = navigationItems.filter(filterBadNavLinks);

    let mobileOnlyNavigationItems: INavigationVariableItem[] = makeVars("mobileOnlyNavigationItems", []);
    mobileOnlyNavigationItems = mobileOnlyNavigationItems.filter(getDefaultNavItems);

    const logo = makeVars("logo", {
        url: "/",
    });

    return { navigationItems, mobileOnlyNavigationItems, logo };
});

function filterBadNavLinks(link: DeepPartial<INavigationVariableItem>): boolean {
    const emptyUrlNoChildren = !link.url && (!link.children || (link.children && !link.children.length));
    if (!link.name || emptyUrlNoChildren) {
        return false;
    }

    return true;
}

function getDefaultNavItems() {
    // Existing custom nav links.
    const navVars = getThemeVariables()?.navigation;
    const legacyCustomNavItems: ITitleBarNav[] | null =
        navVars?.navItems?.[getCurrentLocale()] ?? navVars?.navItems?.default ?? null;

    return legacyCustomNavItems ? legacyCustomNavItems.map(mapLegacyNavItem) : getBuiltinNavItems();
}

function mapLegacyNavItem(item: ITitleBarNav): INavigationVariableItem {
    return {
        name: item.children as string,
        url: item.to,
        permission: item.permission,
        id: uuidv4(),
        children: [],
    };
}

function getBuiltinNavItems() {
    const forumEnabled = getMeta("siteSection.apps.forum", true);

    let builtins: INavigationVariableItem[] = [];
    if (forumEnabled) {
        builtins.push({
            id: "builtin-discussions",
            url: "/discussions",
            name: t("Discussions"),
            children: [],
        });
        builtins.push({
            id: "builtin-categories",
            url: "/categories",
            name: t("Categories"),
            children: [],
        });
    }

    navItemGenerators.forEach((generator) => {
        builtins.push(generator());
    });
    return builtins;
}
