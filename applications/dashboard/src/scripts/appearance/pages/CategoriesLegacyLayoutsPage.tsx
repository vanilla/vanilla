/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { AppearanceNav } from "@dashboard/appearance/nav/AppearanceNav";
import AdminLayout from "@dashboard/components/AdminLayout";
import { useTitleBarDevice, TitleBarDevices } from "@library/layout/TitleBarContext";
import { useCollisionDetector } from "@vanilla/react-utils";
import { t } from "@vanilla/i18n";
import { useConfigsByKeys } from "@library/config/configHooks";
import { LoadStatus } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import Loader from "@library/loaders/Loader";
import LayoutPreviewList from "@dashboard/appearance/components/LayoutPreviewList";
import MixedLayout from "@dashboard/appearance/previews/MixedLayout";
import TableLayout from "@dashboard/appearance/previews/TableLayout";
import ModernLayout from "@dashboard/appearance/previews/ModernLayout";
import TiledLayout from "@dashboard/appearance/previews/TiledLayout";
import { LayoutOption } from "@dashboard/appearance/types";
import AdminTitleBar from "@dashboard/components/AdminTitleBar";
import SmartLink from "@library/routing/links/SmartLink";
import Translate from "@library/content/Translate";
import { useLegacyLayoutView } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";

function CategoriesLegacyLayoutsPage() {
    const configs = useConfigsByKeys(["categories.layout", "customLayout.categoryList"]);
    const isCustomCategoryList = configs.data?.["customLayout.categoryList"];

    const legacyPatcher = useLegacyLayoutView("categoryList", "categories.layout");

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(configs.status)) {
        return <Loader />;
    }

    if (!configs.data || configs.error) {
        return <ErrorMessages errors={[configs.error].filter(notEmpty)} />;
    }

    function applyCategoriesLayout(layout: LayoutOption) {
        legacyPatcher.putLegacyView(layout);
    }

    const editUrl = "/appearance/style-guides";

    return (
        <LayoutPreviewList
            options={[
                {
                    label: t("Modern Layout"),
                    thumbnailComponent: ModernLayout,
                    active: !isCustomCategoryList && configs.data["categories.layout"] == LayoutOption.MODERN,
                    onApply: () => applyCategoriesLayout(LayoutOption.MODERN),
                },
                {
                    label: t("Table Layout"),
                    thumbnailComponent: TableLayout,
                    active: !isCustomCategoryList && configs.data["categories.layout"] == LayoutOption.TABLE,
                    onApply: () => applyCategoriesLayout(LayoutOption.TABLE),
                },
                {
                    label: t("Mixed Layout"),
                    thumbnailComponent: MixedLayout,
                    active: !isCustomCategoryList && configs.data["categories.layout"] == LayoutOption.MIXED,
                    onApply: () => applyCategoriesLayout(LayoutOption.MIXED),
                },
                {
                    label: t("Tiled Layout"),
                    thumbnailComponent: TiledLayout,
                    active: !isCustomCategoryList && configs.data["categories.layout"] == LayoutOption.FOUNDATION,
                    onApply: () => applyCategoriesLayout(LayoutOption.FOUNDATION),
                    editUrl,
                },
            ]}
        />
    );
}

export default function Page() {
    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;
    return (
        <AdminLayout
            activeSectionID={"appearance"}
            customTitleBar={
                <AdminTitleBar
                    title={t("Legacy Layouts - Categories")}
                    description={
                        <Translate
                            source={
                                "Choose the preferred layout for lists of categories. You can edit the Tiled Layout. To learn more, see <0/>."
                            }
                            c0={
                                <SmartLink to={"https://success.vanillaforums.com/kb/articles/430"}>
                                    {t("the documentation")}
                                </SmartLink>
                            }
                        />
                    }
                />
            }
            adminBarHamburgerContent={<AppearanceNav asHamburger />}
            leftPanel={!isCompact && <AppearanceNav />}
            content={<CategoriesLegacyLayoutsPage />}
        />
    );
}
