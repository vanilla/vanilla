/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { Component, ComponentType, useEffect } from "react";
import { t } from "@vanilla/i18n";

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { LoadStatus } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import Loader from "@library/loaders/Loader";
import LayoutPreviewList from "@dashboard/layout/components/LayoutPreviewList";
import Categories from "@dashboard/layout/previews/Categories";
import FoundationLayout from "@dashboard/layout/previews/FoundationLayout";
import MixedLayout from "@dashboard/layout/previews/MixedLayout";
import TableLayout from "@dashboard/layout/previews/TableLayout";
import ModernLayout from "@dashboard/layout/previews/ModernLayout";
import TiledLayout from "@dashboard/layout/previews/TiledLayout";
import { formatUrl } from "@library/utility/appUtils";
import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";

import { BrowserRouter } from "react-router-dom";

enum LayoutOption {
    MODERN = "modern",
    FOUNDATION = "foundation",
    MIXED = "mixed",
    TABLE = "table",
}

export interface IHomepageRouteOption {
    label: string;
    value: string;
    thumbnailComponent: ComponentType<{ className?: string }>;
}

const homepageRouteOptions: IHomepageRouteOption[] = [
    {
        label: "Discussions",
        value: "discussions",
        thumbnailComponent: ModernLayout,
    },
    {
        label: "Categories",
        value: "categories",
        thumbnailComponent: Categories,
    },
];

export function addHomepageRouteOption(option: IHomepageRouteOption) {
    homepageRouteOptions.push(option);
}

const baseUrl = formatUrl("", true);

export function LayoutPage() {
    const configs = useConfigsByKeys(["discussions.layout", "categories.layout", "routes.defaultController"]);

    const { patchConfig } = useConfigPatcher();

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(configs.status)) {
        return <Loader />;
    }

    if (!configs.data || configs.error) {
        return <ErrorMessages errors={[configs.error].filter(notEmpty)} />;
    }

    function applyHomeRoute(route: string) {
        patchConfig({
            "routes.defaultController": [route, "internal"],
        });
    }

    function applyDiscussionsLayout(layout: LayoutOption) {
        patchConfig({
            "discussions.layout": layout,
        });
    }

    function applyCategoriesLayout(layout: LayoutOption) {
        patchConfig({
            "categories.layout": layout,
        });
    }

    const editUrl = "/theme/theme-settings";

    return (
        <BrowserRouter>
            <DashboardHeaderBlock title={t("Layout")} />
            <LayoutPreviewList
                title={t("Homepage")}
                description={
                    <Translate
                        source="Choose the page people should see when they visit <0/>"
                        c0={<SmartLink to={baseUrl}>{baseUrl}</SmartLink>}
                    />
                }
                options={homepageRouteOptions.map((option) => ({
                    label: t(option.label),
                    thumbnailComponent: option.thumbnailComponent,
                    active: configs.data["routes.defaultController"][0] == option.value,
                    onApply: () => applyHomeRoute(option.value),
                }))}
            />
            <LayoutPreviewList
                title={t("Discussions")}
                description={t("Choose the preferred layout for lists of discussions.")}
                options={[
                    {
                        label: t("Modern Layout"),
                        thumbnailComponent: ModernLayout,
                        onApply: () => applyDiscussionsLayout(LayoutOption.MODERN),
                        active: configs.data["discussions.layout"] == LayoutOption.MODERN,
                    },
                    {
                        label: t("Table Layout"),
                        thumbnailComponent: TableLayout,
                        onApply: () => applyDiscussionsLayout(LayoutOption.TABLE),
                        active: configs.data["discussions.layout"] == LayoutOption.TABLE,
                    },
                    {
                        label: t("Foundation Layout"),
                        thumbnailComponent: FoundationLayout,
                        onApply: () => applyDiscussionsLayout(LayoutOption.FOUNDATION),
                        active: configs.data["discussions.layout"] == LayoutOption.FOUNDATION,
                        editUrl,
                    },
                ]}
            />
            <LayoutPreviewList
                title={t("Categories")}
                description={t("Choose the preferred layout for lists of categories. You can edit the Tiled Layout.")}
                options={[
                    {
                        label: t("Modern Layout"),
                        thumbnailComponent: ModernLayout,
                        active: configs.data["categories.layout"] == LayoutOption.MODERN,
                        onApply: () => applyCategoriesLayout(LayoutOption.MODERN),
                    },
                    {
                        label: t("Table Layout"),
                        thumbnailComponent: TableLayout,
                        active: configs.data["categories.layout"] == LayoutOption.TABLE,
                        onApply: () => applyCategoriesLayout(LayoutOption.TABLE),
                    },
                    {
                        label: t("Mixed Layout"),
                        thumbnailComponent: MixedLayout,
                        active: configs.data["categories.layout"] == LayoutOption.MIXED,
                        onApply: () => applyCategoriesLayout(LayoutOption.MIXED),
                    },
                    {
                        label: t("Tiled Layout"),
                        thumbnailComponent: TiledLayout,
                        active: configs.data["categories.layout"] == LayoutOption.FOUNDATION,
                        onApply: () => applyCategoriesLayout(LayoutOption.FOUNDATION),
                        editUrl,
                    },
                ]}
            />
        </BrowserRouter>
    );
}
