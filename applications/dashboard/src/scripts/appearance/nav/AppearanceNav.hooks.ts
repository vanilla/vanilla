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
    NewLayoutRoute,
    NewLayoutJsonRoute,
} from "@dashboard/appearance/routes/pageRoutes";
import { useConfigsByKeys } from "@library/config/configHooks";
import { useMemo } from "react";
import { useLayouts } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { RecordID, uuidv4 } from "@vanilla/utils";
import { ILayout } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import isEmpty from "lodash/isEmpty";
import { LayoutViewType, LAYOUT_VIEW_TYPES } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { registeredAppearanceNavItems } from "@dashboard/appearance/navigationItems";

const CUSTOM_LAYOUTS_CONFIG_KEY = "labs.customLayouts";

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
                                        url: getRelativeUrl(NewLayoutRoute.url(layoutViewType)),
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
            parentID,
            sort: 0,
            recordID: 1,
            recordType: "customLink",
            children: [],
            url: getRelativeUrl(itemPartial?.url ?? ""),
        }));
    }, [parentID, registeredNavItems]);

    const brandingPageLink: INavigationTreeItem = {
        name: t("Branding & SEO"),
        parentID,
        sort: 0,
        recordID: 0,
        recordType: "customLink",
        children: [],
        url: getRelativeUrl(BrandingPageRoute.url(undefined)),
    };

    const layoutTreeItem = useLayoutEditorNavTree(2, parentID);

    return [brandingPageLink, ...otherNavItems, layoutTreeItem];
}
