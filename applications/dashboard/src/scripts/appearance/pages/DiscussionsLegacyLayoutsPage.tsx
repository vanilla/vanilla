/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { AppearanceNav } from "@dashboard/appearance/nav/AppearanceNav";
import AdminLayout from "@dashboard/components/AdminLayout";
import { useTitleBarDevice, TitleBarDevices } from "@library/layout/TitleBarContext";
import Loader from "@library/loaders/Loader";
import { t } from "@vanilla/i18n";
import { useCollisionDetector } from "@vanilla/react-utils";
import { notEmpty } from "@vanilla/utils";
import LayoutPreviewList from "@dashboard/appearance/components/LayoutPreviewList";
import FoundationLayout from "@dashboard/appearance/previews/FoundationLayout";
import ModernLayout from "@dashboard/appearance/previews/ModernLayout";
import TableLayout from "@dashboard/appearance/previews/TableLayout";
import { LoadStatus } from "@library/@types/api/core";
import { useConfigsByKeys } from "@library/config/configHooks";
import ErrorMessages from "@library/forms/ErrorMessages";
import { LayoutOption } from "@dashboard/appearance/types";
import AdminTitleBar from "@dashboard/components/AdminTitleBar";
import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";
import { useLegacyLayoutView } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";

function DiscussionsLegacyLayoutsPage() {
    const configs = useConfigsByKeys(["discussions.layout", "customLayout.discussionList"]);
    const isCustomDiscussionList = configs.data?.["customLayout.discussionList"] ?? false;

    const legacyPatcher = useLegacyLayoutView("discussionList", "discussions.layout");

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(configs.status)) {
        return <Loader />;
    }

    if (!configs.data || configs.error) {
        return <ErrorMessages errors={[configs.error].filter(notEmpty)} />;
    }

    function applyDiscussionsLayout(layout: LayoutOption) {
        legacyPatcher.putLegacyView(layout);
    }

    const editUrl = "/appearance/style-guides";

    return (
        <LayoutPreviewList
            options={[
                {
                    label: t("Modern Layout"),
                    thumbnailComponent: ModernLayout,
                    onApply: () => applyDiscussionsLayout(LayoutOption.MODERN),
                    active: !isCustomDiscussionList && configs.data["discussions.layout"] == LayoutOption.MODERN,
                },
                {
                    label: t("Table Layout"),
                    thumbnailComponent: TableLayout,
                    onApply: () => applyDiscussionsLayout(LayoutOption.TABLE),
                    active: !isCustomDiscussionList && configs.data["discussions.layout"] == LayoutOption.TABLE,
                },
                {
                    label: t("Foundation Layout"),
                    thumbnailComponent: FoundationLayout,
                    onApply: () => applyDiscussionsLayout(LayoutOption.FOUNDATION),
                    active: !isCustomDiscussionList && configs.data["discussions.layout"] == LayoutOption.FOUNDATION,
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
                    title={t("Legacy Layouts - Discussions")}
                    description={
                        <Translate
                            source={
                                "Choose the preferred layout for lists of discussions. You can edit the Foundation Layout. To learn more, see <0/>."
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
            content={<DiscussionsLegacyLayoutsPage />}
        />
    );
}
