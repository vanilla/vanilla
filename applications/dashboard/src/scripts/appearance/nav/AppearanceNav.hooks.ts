/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INavigationTreeItem } from "@library/@types/api/core";
import { getRelativeUrl, t } from "@library/utility/appUtils";
import {
    BrandingPageRoute,
    CategoriesLegacyLayoutsRoute,
    DiscussionsLegacyLayoutsRoute,
    HomepageLegacyLayoutsRoute,
    LayoutOverviewRoute,
    StyleGuidesListRoute,
} from "@dashboard/appearance/routes/pageRoutes";
import { useConfigsByKeys } from "@library/config/configHooks";
import { useMemo } from "react";
import { useLayouts } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { RecordID } from "@vanilla/utils";
import { ILayout } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import isEmpty from "lodash/isEmpty";

const CUSTOM_LAYOUTS_CONFIG_KEY = "labs.customLayouts";

export function useAppearanceNavItems(parentID: RecordID): INavigationTreeItem[] {
    // Check if custom layouts are enabled
    const config = useConfigsByKeys([CUSTOM_LAYOUTS_CONFIG_KEY]);
    const { layoutsByViewType } = useLayouts();
    const isCustomLayoutsEnabled = useMemo(() => !!config?.data?.[CUSTOM_LAYOUTS_CONFIG_KEY], [config]);

    const TOP_LEVEL_ITEMS: INavigationTreeItem[] = [
        {
            name: t("Branding & SEO"),
            sort: 0,
            parentID,
            recordID: 0,
            recordType: "customLink",
            children: [],
            url: getRelativeUrl(BrandingPageRoute.url(undefined)),
        },
        {
            name: t("Style Guides"),
            sort: 0,
            parentID,
            recordID: 1,
            recordType: "customLink",
            children: [],
            url: getRelativeUrl(StyleGuidesListRoute.url(undefined)),
        },
    ];

    const navigationTree = useMemo(() => {
        // Top level layouts
        const layoutTreeItem: INavigationTreeItem = {
            name: t("Layout Editor"),
            parentID,
            sort: 0,
            recordID: 2,
            recordType: "panelMenu",
            children: [],
        };

        // Secondary level homepage
        const homepageTreeItem: INavigationTreeItem = {
            name: t("Home Page"),
            parentID: layoutTreeItem.recordID,
            sort: 0,
            recordID: 3,
            recordType: "panelMenu",
            children: [],
        };

        const homepageLegacyLayoutsTreeItem: INavigationTreeItem = {
            name: t("Legacy Layouts"),
            parentID: homepageTreeItem.recordID,
            sort: 0,
            recordID: 4,
            recordType: "legacyAppearanceNavItem",
            children: [],
            url: getRelativeUrl(HomepageLegacyLayoutsRoute.url(undefined)),
        };

        // Secondary level discussions
        const discussionsTreeItem: INavigationTreeItem = {
            name: t("Discussions Page"),
            parentID: layoutTreeItem.recordID,
            sort: 0,
            recordID: 5,
            recordType: "panelMenu",
            children: [],
        };

        const discussionsLegacyLayoutsTreeItem: INavigationTreeItem = {
            name: t("Legacy Layouts"),
            parentID: discussionsTreeItem.recordID,
            sort: 0,
            recordID: 6,
            recordType: "legacyAppearanceNavItem",
            children: [],
            url: getRelativeUrl(DiscussionsLegacyLayoutsRoute.url(undefined)),
        };

        // Secondary level categories
        const categoriesTreeItem: INavigationTreeItem = {
            name: t("Categories Page"),
            parentID: layoutTreeItem.recordID,
            sort: 0,
            recordID: 7,
            recordType: "panelMenu",
            children: [],
        };

        const categoriesLegacyLayoutsTreeItem: INavigationTreeItem = {
            name: t("Legacy Layouts"),
            parentID: categoriesTreeItem.recordID,
            sort: 0,
            recordID: 8,
            recordType: "legacyAppearanceNavItem",
            children: [],
            url: getRelativeUrl(CategoriesLegacyLayoutsRoute.url(undefined)),
        };

        homepageTreeItem.children.push(homepageLegacyLayoutsTreeItem);
        discussionsTreeItem.children.push(discussionsLegacyLayoutsTreeItem);
        categoriesTreeItem.children.push(categoriesLegacyLayoutsTreeItem);
        layoutTreeItem.children.push(homepageTreeItem, discussionsTreeItem, categoriesTreeItem);

        const userCreateLayoutItem = {
            sort: 0,
            name: t("Add Custom Layout"),
            url: "this-should-go-to-an-add-url",
            recordType: "addCustomLayout",
            parentID: 0,
            recordID: -1,
            isLink: true,
            children: [],
        };

        if (isCustomLayoutsEnabled) {
            if (!isEmpty(layoutsByViewType)) {
                const viewTypes = Object.keys(layoutsByViewType);
                viewTypes.forEach((viewType) => {
                    switch (viewType) {
                        case "home":
                            homepageTreeItem.children.push(
                                ...makeTreeChildren(layoutsByViewType.home, homepageTreeItem.recordID),
                            );

                            break;
                        case "discussion":
                            discussionsTreeItem.children.push(
                                ...makeTreeChildren(layoutsByViewType.discussion, homepageTreeItem.recordID),
                            );
                            break;
                        case "category":
                            categoriesTreeItem.children.push(
                                ...makeTreeChildren(layoutsByViewType.category, homepageTreeItem.recordID),
                            );
                            break;
                        default:
                            null;
                    }
                });
            }

            homepageTreeItem.children.push(userCreateLayoutItem);
            discussionsTreeItem.children.push(userCreateLayoutItem);
            categoriesTreeItem.children.push(userCreateLayoutItem);
        }

        return [...TOP_LEVEL_ITEMS, layoutTreeItem];
    }, [parentID, isCustomLayoutsEnabled, TOP_LEVEL_ITEMS, layoutsByViewType]);

    return navigationTree;
}

function makeTreeChildren(layouts: ILayout[], recordID: RecordID) {
    return (
        layouts.map((layout, index) => ({
            sort: index,
            name: layout.name,
            url: getRelativeUrl(LayoutOverviewRoute.url(layout)),
            recordType: "customLayout",
            parentID: recordID,
            recordID: layout.layoutID,
            children: [],
        })) ?? []
    );
}
