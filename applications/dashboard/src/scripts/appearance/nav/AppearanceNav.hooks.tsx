/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AppliedLayoutViewLocationDetails } from "@dashboard/appearance/components/AppliedLayoutViewLocationDetails";
import {
    getLayoutTypeGroupLabel,
    getLayoutTypeLabel,
    getLayoutTypeSettingsLabel,
    getLayoutTypeSettingsUrl,
} from "@dashboard/appearance/components/layoutViewUtils";
import { registeredAppearanceNavItems } from "@dashboard/appearance/navigationItems";
import {
    BrandingPageRoute,
    LayoutEditorRoute,
    LayoutOverviewRoute,
} from "@dashboard/appearance/routes/appearanceRoutes";
import { sliceLayoutsByViewType, useLayoutsQuery } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { LayoutViewType, LAYOUT_VIEW_TYPES } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { cx } from "@emotion/css";
import { INavigationTreeItem } from "@library/@types/api/core";
import { useConfigsByKeys } from "@library/config/configHooks";
import { CheckCompactIcon } from "@library/icons/common";
import { siteNavNodeClasses } from "@library/navigation/siteNavStyles";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import {
    getDefaultSiteSection,
    getEnabledPostTypes,
    getMeta,
    getRelativeUrl,
    hasMultipleSiteSections,
    t,
} from "@library/utility/appUtils";
import { Icon } from "@vanilla/icons";
import { notEmpty, RecordID } from "@vanilla/utils";
import React, { useMemo } from "react";

type WithRequiredProperty<Type, Key extends keyof Type> = Type & {
    [Property in Key]-?: Type[Property];
};

function useLayoutEditorNavTree(ownID: RecordID, parentID: RecordID): INavigationTreeItem {
    // Check if custom layouts are enabled
    const perPageEditorConfigs = LAYOUT_VIEW_TYPES.map((type) => `layoutEditor.${type}`);
    const perPageAppliedConfigs = LAYOUT_VIEW_TYPES.map((type) => `customLayout.${type}`);
    const configValues = useConfigsByKeys([...perPageEditorConfigs, ...perPageAppliedConfigs]);
    const layoutsQuery = useLayoutsQuery();
    const classes = siteNavNodeClasses();

    const layoutsByViewType = sliceLayoutsByViewType(layoutsQuery.data ?? []);

    function getChildLayouts(layoutViewType: LayoutViewType, parentLayoutViewType?: string): INavigationTreeItem[] {
        const layouts: INavigationTreeItem[] =
            layoutsByViewType?.[layoutViewType]?.map((layout, index) => {
                const isActive = configValues.data?.[`customLayout.${parentLayoutViewType ?? layoutViewType}`];
                const activeCheck = (
                    <ToolTip label={<AppliedLayoutViewLocationDetails layout={layout} mode={"tooltipContents"} />}>
                        <ToolTipIcon>
                            <CheckCompactIcon
                                className={cx({
                                    [classes.checkMark]: isActive,
                                    [classes.icon]: !isActive,
                                    disabled: !isActive,
                                })}
                            />
                        </ToolTipIcon>
                    </ToolTip>
                );
                const inactiveCheck = (
                    <span className={classes.iconGroup}>
                        {activeCheck}
                        <ToolTip label="This applied layout is applied but not active because legacy layouts are enabled.">
                            <ToolTipIcon>
                                <Icon icon="data-information" className={classes.icon} />
                            </ToolTipIcon>
                        </ToolTip>
                    </span>
                );
                const isApplied = layout.layoutViews.length > 0;

                const checkMark = configValues.data?.[`customLayout.${parentLayoutViewType ?? layoutViewType}`]
                    ? activeCheck
                    : inactiveCheck;
                return {
                    sort: index,
                    name: layout.name,
                    url: getRelativeUrl(LayoutOverviewRoute.url(layout)),
                    recordType: "customLayout",
                    parentID: parentID ?? layoutViewType,
                    recordID: layout.layoutID,
                    children: [],
                    iconSuffix: isApplied ? checkMark : undefined,
                };
            }) ?? [];

        if (layouts.length > 0) {
            layouts.push({
                name: t("Add Custom Layout"),
                parentID: layoutViewType,
                sort: 0,
                recordID: -1,
                recordType: "addLayout",
                isLink: true,
                children: [],
                url: getRelativeUrl(LayoutEditorRoute.url({ layoutViewType, layoutID: layoutViewType, isCopy: true })),
            });
        }
        return layouts;
    }

    let homeItems: INavigationTreeItem[] = [];

    const hasSubcommunityDefaultSiteSection = getDefaultSiteSection().basePath !== "";
    const hasSubcommunities = hasMultipleSiteSections();

    if (hasSubcommunities) {
        if (hasSubcommunityDefaultSiteSection) {
            homeItems = getChildLayouts("subcommunityHome", "home");
        } else {
            // Now we have both.
            homeItems = [
                {
                    name: getLayoutTypeLabel("home"),
                    parentID: "home",
                    recordID: "siteHome",
                    recordType: "panelMenu",
                    children: [...getChildLayouts("home", "home")],
                },
                {
                    name: getLayoutTypeLabel("subcommunityHome"),
                    parentID: "home",
                    recordID: "subcommunityHome",
                    recordType: "panelMenu",
                    children: [...getChildLayouts("subcommunityHome", "home")],
                },
            ];
        }
    } else {
        homeItems = getChildLayouts("home", "home");
    }

    const enabledPostTypes = getEnabledPostTypes();

    function makeDiscussionThreadItem() {
        const isEnabled = getMeta("featureFlags.layoutEditor.discussionThread.Enabled", false);

        // If the discussion thread layout editor is enabled, then we need to add the discussion thread item.
        if (!isEnabled) {
            return null;
        }

        let discussionSettings: INavigationTreeItem = {
            name: getLayoutTypeSettingsLabel("discussionThread"),
            parentID: "discussionThread",
            recordType: "appearanceNavItem",
            recordID: "discussionThreadLayoutSettings",
            url: getLayoutTypeSettingsUrl("discussionThread"),
        };

        let subTypes: INavigationTreeItem[] = [];

        if (enabledPostTypes.includes("discussion")) {
            subTypes.push({
                name: getLayoutTypeLabel("discussionPage"),
                parentID: "discussionThread",
                recordID: "threadList",
                recordType: "panelMenu",
                children: [...getChildLayouts("discussionThread", "discussionThread")],
            });
        }

        if (enabledPostTypes.includes("idea")) {
            subTypes.push({
                name: getLayoutTypeLabel("ideaThread"),
                parentID: "discussionThread",
                recordID: "ideaThread",
                recordType: "panelMenu",
                children: [...getChildLayouts("ideaThread", "discussionThread")],
            });
        }

        let discussionThreadItem: WithRequiredProperty<INavigationTreeItem, "children"> = {
            name: getLayoutTypeGroupLabel("discussionThread"),
            parentID: ownID,
            recordID: "discussionThread",
            recordType: "panelMenu",
            children: [
                // Always needs the settings page
                {
                    name: getLayoutTypeSettingsLabel("discussionThread"),
                    parentID: "discussionThread",
                    recordType: "appearanceNavItem",
                    recordID: "discussionThreadLayoutSettings",
                    url: getLayoutTypeSettingsUrl("discussionThread"),
                },
                ...(subTypes.length === 1 ? subTypes[0].children! : subTypes),
            ],
        };

        return discussionThreadItem;
    }

    const navigationItems: INavigationTreeItem[] = [
        {
            name: getLayoutTypeGroupLabel("home"),
            parentID: ownID,
            recordID: "home",
            recordType: "panelMenu",
            children: [
                {
                    name: getLayoutTypeSettingsLabel("home"),
                    parentID: "home",
                    sort: 0,
                    recordID: "home",
                    recordType: "appearanceNavItem",
                    children: [],
                    url: getLayoutTypeSettingsUrl("home"),
                },
                ...homeItems,
            ],
        },
        {
            name: getLayoutTypeGroupLabel("discussionList"),
            parentID: ownID,
            recordID: "discussionList",
            recordType: "panelMenu",
            children: [
                {
                    name: getLayoutTypeSettingsLabel("discussionList"),
                    parentID: "discussionList",
                    sort: 0,
                    recordID: "discussionList",
                    recordType: "appearanceNavItem",
                    children: [],
                    url: getLayoutTypeSettingsUrl("discussionList"),
                },
                ...getChildLayouts("discussionList"),
            ],
        },
        {
            name: getLayoutTypeGroupLabel("discussionCategoryPage"),
            parentID: ownID,
            recordID: "categories",
            recordType: "panelMenu",
            children: [
                {
                    name: getLayoutTypeSettingsLabel("discussionCategoryPage"),
                    parentID: "categories",
                    recordType: "appearanceNavItem",
                    recordID: "categoryLayoutSettings",
                    url: getLayoutTypeSettingsUrl("discussionCategoryPage"),
                },
                {
                    name: getLayoutTypeLabel("categoryList"),
                    parentID: "categories",
                    recordID: "categoryList",
                    recordType: "panelMenu",
                    children: [...getChildLayouts("categoryList", "categoryList")],
                },
                {
                    name: getLayoutTypeLabel("discussionCategoryPage"),
                    parentID: "categories",
                    recordID: "discussionCategoryPage",
                    recordType: "panelMenu",
                    children: [...getChildLayouts("discussionCategoryPage", "categoryList")],
                },
                {
                    name: getLayoutTypeLabel("nestedCategoryList"),
                    parentID: "categories",
                    recordID: "nestedCategoryList",
                    recordType: "panelMenu",
                    children: [...getChildLayouts("nestedCategoryList", "categoryList")],
                },
            ],
        },
        makeDiscussionThreadItem(),
    ].filter(notEmpty);

    const topLevelItem: INavigationTreeItem = {
        name: t("Layouts"),
        parentID,
        sort: 0,
        recordID: ownID,
        recordType: "panelMenu",
        children: navigationItems,
    };

    return topLevelItem;
}

export function useAppearanceNavItems(parentID: RecordID): INavigationTreeItem[] {
    const registeredNavItems = registeredAppearanceNavItems();

    const otherNavItems = useMemo(() => {
        return registeredNavItems.map((itemPartial: Partial<INavigationTreeItem>) => ({
            ...itemPartial,
            name: t(itemPartial?.name ?? ""),
            parentID: 0,
            sort: 0,
            recordID: "registeredNavItem",
            recordType: "customLink",
            children: [],
            url: getRelativeUrl(itemPartial?.url ?? ""),
        }));
    }, [registeredNavItems]);

    const brandingPageLink: INavigationTreeItem = {
        name: t("Branding & SEO"),
        parentID: 0,
        sort: 0,
        recordID: "brandingAndSEO",
        recordType: "customLink",
        children: [],
        url: getRelativeUrl(BrandingPageRoute.url(undefined)),
    };

    //Branding & SEO and Style Guides should be part of parent named Branding & Assets
    const brandingAndAssetsTreeItem = {
        name: t("Branding & Assets"),
        parentID,
        sort: 0,
        recordID: 0,
        recordType: "panelMenu",
        children: [brandingPageLink, ...otherNavItems],
    };

    const layoutTreeItem = useLayoutEditorNavTree(1, parentID);

    return [brandingAndAssetsTreeItem, layoutTreeItem];
}
