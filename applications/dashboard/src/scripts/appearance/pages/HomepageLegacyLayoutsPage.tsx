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
import Categories from "@dashboard/appearance/previews/Categories";
import ModernLayout from "@dashboard/appearance/previews/ModernLayout";
import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";
import { formatUrl } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n";
import React, { ComponentType } from "react";

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

export default function HomepageLegacyLayoutsPage() {
    return (
        <LegacyLayoutFormPage
            title={getLayoutTypeSettingsLabel("home")}
            description={
                <>
                    <Translate
                        source="Choose the page people should see when they visit <0>your website</0>."
                        c0={(content) => <SmartLink to={formatUrl("", true)}>{content}</SmartLink>}
                    />
                    <Translate
                        source={"To learn more, <0>see the documentation</0>."}
                        c0={(content) => (
                            <SmartLink to={"https://success.vanillaforums.com/kb/articles/430"}>{content}</SmartLink>
                        )}
                    />
                </>
            }
            legacyLayoutTypes={homepageRouteOptions.map((opt) => ({
                type: opt.value,
                label: opt.label,
                thumbnailComponent: opt.thumbnailComponent,
            }))}
            layoutTypeLabel={getLayoutTypeGroupLabel("home")}
            legacyLayoutConfigKey="routes.defaultController"
            customLayoutConfigKey={getLayoutFeatureFlagKey("home")}
            legacyTitle={t("Legacy Home Layouts")}
            legacyDescription={t("Choose the preferred Legacy Home Layout.")}
            radios={{
                legendLabel: t("Home Layout Version"),
                legacyLabel: t("Legacy Home Layouts"),
                customLabel: t("Custom Home Layouts"),
            }}
        />
    );
}
