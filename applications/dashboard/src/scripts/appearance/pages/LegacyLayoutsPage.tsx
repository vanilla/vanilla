/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    getLayoutFeatureFlagKey,
    getLayoutTypeGroupLabel,
    getLayoutTypeLabel,
    getLayoutTypeSettingsLabel,
} from "@dashboard/appearance/components/layoutViewUtils";
import { LegacyLayoutFormPage } from "@dashboard/appearance/components/LegacyLayoutForm";
import HomepageLegacyLayoutsPage from "@dashboard/appearance/pages/HomepageLegacyLayoutsPage";
import FoundationLayout from "@dashboard/appearance/previews/FoundationLayout";
import MixedLayout from "@dashboard/appearance/previews/MixedLayout";
import ModernLayout from "@dashboard/appearance/previews/ModernLayout";
import TableLayout from "@dashboard/appearance/previews/TableLayout";
import TiledLayout from "@dashboard/appearance/previews/TiledLayout";
import { LayoutOption } from "@dashboard/appearance/types";
import { LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import NotFoundPage from "@library/routing/NotFoundPage";
import { t } from "@vanilla/i18n";
import React from "react";
import { RouteComponentProps } from "react-router-dom";

export default function LegacyLayoutsPage(
    props: RouteComponentProps<{
        layoutViewType: LayoutViewType;
    }>,
) {
    const { layoutViewType } = props.match.params;
    switch (layoutViewType) {
        case "home":
        case "subcommunityHome":
            return <HomepageLegacyLayoutsPage />;
        case "nestedCategoryList":
        case "discussionCategoryPage":
        case "categoryList":
            return (
                <LegacyLayoutFormPage
                    title={getLayoutTypeSettingsLabel("nestedCategoryList")}
                    legacyLayoutTypes={[
                        {
                            type: LayoutOption.MODERN,
                            label: t("Modern Layout"),
                            thumbnailComponent: ModernLayout,
                        },
                        {
                            type: LayoutOption.TABLE,

                            label: t("Table Layout"),
                            thumbnailComponent: TableLayout,
                        },
                        {
                            type: LayoutOption.MIXED,
                            label: t("Mixed Layout"),
                            thumbnailComponent: MixedLayout,
                        },
                        {
                            type: LayoutOption.FOUNDATION,
                            label: t("Tiled Layout"),
                            thumbnailComponent: TiledLayout,
                        },
                    ]}
                    layoutTypeLabel={getLayoutTypeGroupLabel("nestedCategoryList")}
                    legacyLayoutConfigKey="categories.layout"
                    customLayoutConfigKey={getLayoutFeatureFlagKey("nestedCategoryList")}
                    legacyTitle={t("Legacy Category Layout")}
                    legacyDescription={t("Choose the preferred Legacy Category Layout.")}
                    radios={{
                        legendLabel: t("Category Layout Version"),
                        legacyLabel: t("Legacy Category Layouts"),
                        customLabel: t("Custom Category Layouts"),
                    }}
                />
            );
        case "discussionList":
            return (
                <LegacyLayoutFormPage
                    title={getLayoutTypeSettingsLabel("discussionList")}
                    legacyLayoutTypes={[
                        {
                            type: LayoutOption.MODERN,
                            label: t("Modern Layout"),
                            thumbnailComponent: ModernLayout,
                        },
                        {
                            type: LayoutOption.TABLE,
                            label: t("Table Layout"),
                            thumbnailComponent: TableLayout,
                        },
                        {
                            type: LayoutOption.FOUNDATION,
                            label: t("Foundation Layout"),
                            thumbnailComponent: FoundationLayout,
                        },
                    ]}
                    layoutTypeLabel={getLayoutTypeGroupLabel("discussionList")}
                    legacyLayoutConfigKey="discussions.layout"
                    customLayoutConfigKey={getLayoutFeatureFlagKey("discussionList")}
                    legacyTitle={t("Legacy Discussion Layout")}
                    legacyDescription={t("Choose the preferred Legacy Discussion Layout.")}
                    radios={{
                        legendLabel: t("Discussion Layout Version"),
                        legacyLabel: t("Legacy Discussion Layouts"),
                        customLabel: t("Custom Discussion Layouts"),
                    }}
                />
            );
        case "discussionThread":
            return (
                <LegacyLayoutFormPage
                    layoutTypeLabel={getLayoutTypeGroupLabel("discussionThread")}
                    title={getLayoutTypeSettingsLabel("discussionThread")}
                    customLayoutConfigKey={getLayoutFeatureFlagKey("discussionThread")}
                    legacyTitle={t("Legacy Discussion Thread Layout")}
                    legacyDescription={t("Choose the preferred Legacy Discussion Thread Layout.")}
                    radios={{
                        legendLabel: t("Discussion Thread Layout Version"),
                        legacyLabel: t("Legacy Discussion Thread Layouts"),
                        customLabel: t("Custom Discussion Thread Layouts"),
                    }}
                />
            );
        default:
            return <NotFoundPage />;
    }
}
