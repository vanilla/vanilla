/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ComponentType, useMemo } from "react";
import { AppearanceNav } from "@dashboard/appearance/nav/AppearanceNav";
import AdminLayout from "@dashboard/components/AdminLayout";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import { useCollisionDetector } from "@vanilla/react-utils";
import LayoutPreviewList from "@dashboard/appearance/components/LayoutPreviewList";
import { LoadStatus } from "@library/@types/api/core";
import { useConfigsByKeys } from "@library/config/configHooks";
import Translate from "@library/content/Translate";
import ErrorMessages from "@library/forms/ErrorMessages";
import Loader from "@library/loaders/Loader";
import SmartLink from "@library/routing/links/SmartLink";
import { formatUrl } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n";
import { notEmpty } from "@vanilla/utils";
import Categories from "@dashboard/appearance/previews/Categories";
import ModernLayout from "@dashboard/appearance/previews/ModernLayout";
import AdminTitleBar from "@dashboard/components/AdminTitleBar";
import { useLegacyLayoutView } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";

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

function HomepageLegacyLayoutsPageImpl() {
    const configs = useConfigsByKeys(["routes.defaultController", "customLayout.home"]);

    const legacyPatcher = useLegacyLayoutView("home", "routes.defaultController");

    function applyHomeRoute(route: string) {
        legacyPatcher.putLegacyView(route);
    }

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(configs.status)) {
        return <Loader />;
    }

    if (!configs.data || configs.error) {
        return <ErrorMessages errors={[configs.error].filter(notEmpty)} />;
    }

    return (
        <LayoutPreviewList
            options={homepageRouteOptions.map((option) => ({
                label: t(option.label),
                thumbnailComponent: option.thumbnailComponent,
                active:
                    (configs.data["routes.defaultController"][0] == option.value ||
                        configs.data["routes.defaultController"] == option.value) &&
                    !configs.data["customLayout.home"],
                onApply: () => applyHomeRoute(option.value),
            }))}
        />
    );
}

export default function HomepageLegacyLayoutsPage() {
    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;
    const baseUrl = formatUrl("", true);

    return (
        <AdminLayout
            activeSectionID={"appearance"}
            customTitleBar={
                <AdminTitleBar
                    title={t("Legacy Layouts - Homepage")}
                    description={
                        <Translate
                            source="Choose the page people should see when they visit <0/>. To learn more, see <1/>."
                            c0={<SmartLink to={baseUrl}>{t("your website")}</SmartLink>}
                            c1={
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
            content={<HomepageLegacyLayoutsPageImpl />}
        />
    );
}
