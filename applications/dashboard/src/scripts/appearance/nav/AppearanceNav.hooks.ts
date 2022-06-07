/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INavigationTreeItem } from "@library/@types/api/core";
import { getRelativeUrl, t } from "@library/utility/appUtils";
import {
    BrandingPageRoute,
    LegacyLayoutsRoute,
    LayoutOverviewRoute,
    LayoutEditorRoute,
} from "@dashboard/appearance/routes/appearanceRoutes";
import { useConfigsByKeys } from "@library/config/configHooks";
import { useMemo } from "react";
import { useLayouts } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { RecordID, uuidv4 } from "@vanilla/utils";
import { ILayoutDetails } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import isEmpty from "lodash/isEmpty";
import { LayoutViewType, LAYOUT_VIEW_TYPES } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { registeredAppearanceNavItems } from "@dashboard/appearance/navigationItems";

export const CUSTOM_LAYOUTS_CONFIG_KEY = "labs.layoutEditor";

function makeTreeChildren(layouts: ILayoutDetails[], recordID: RecordID) {
    return (
        layouts.map((layout, index) => {
            const isApplied =
                layout.layoutViewType === recordID &&
                !!layout.layoutViews.length &&
                !!layout.layoutViews.find((layoutView) => layoutView.layoutViewType === recordID);
            return {
                sort: index,
                name: layout.name,
                url: getRelativeUrl(LayoutOverviewRoute.url(layout)),
                recordType: "customLayout",
                parentID: recordID,
                recordID: layout.layoutID,
                children: [],
                withCheckMark: isApplied,
            };
        }) ?? []
    );
}

function useLayoutEditorNavTree(ownID: RecordID, parentID: RecordID): INavigationTreeItem {
    // Check if custom layouts are enabled
    const config = useConfigsByKeys([CUSTOM_LAYOUTS_CONFIG_KEY]);
    const { layoutsByViewType } = useLayouts();
    const isCustomLayoutsEnabled = useMemo(() => !!config?.data?.[CUSTOM_LAYOUTS_CONFIG_KEY], [config]);

    const layoutViewTypesTranslatedNames: { [key in LayoutViewType]: string } = {
        home: t("Home"),
        discussions: t("Discussions"),
        categories: t("Categories"),
    };

    return {
        name: t("Layout Editor"),
        parentID,
        sort: 0,
        recordID: ownID,
        recordType: "panelMenu",
        children: LAYOUT_VIEW_TYPES.map((layoutViewType) => ({
            name: t(`${layoutViewTypesTranslatedNames[layoutViewType]} Page`),
            parentID: ownID,
            sort: 0,
            recordID: layoutViewType,
            recordType: "panelMenu",
            children: [
                {
                    name: t("Legacy Layouts"),
                    parentID: layoutViewType,
                    sort: 0,
                    recordID: uuidv4(),
                    recordType: "appearanceNavItem",
                    children: [],
                    url: getRelativeUrl(LegacyLayoutsRoute.url(layoutViewType)),
                },
                ...(isCustomLayoutsEnabled && !isEmpty(layoutsByViewType)
                    ? [
                          ...makeTreeChildren(layoutsByViewType[layoutViewType] ?? [], layoutViewType),
                          ...(layoutViewType === "home"
                              ? [
                                    {
                                        name: t("Add Custom Layout"),
                                        parentID: layoutViewType,
                                        sort: 0,
                                        recordID: -1,
                                        recordType: "addLayout",
                                        isLink: true,
                                        children: [],
                                        url: getRelativeUrl(LayoutEditorRoute.url({ layoutViewType })),
                                    },
                                ]
                              : []),
                      ]
                    : []),
            ],
        })),
    };
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
