/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import image from "!file-loader!./CustomLayoutLabItem.svg";
import { LabThemeEditorNote } from "@dashboard/labs/LabThemeEditorNote";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { LoadStatus } from "@library/@types/api/core";
import { useConfigsByKeys } from "@library/config/configHooks";
import { t } from "@vanilla/i18n";
import React, { useMemo } from "react";

const LAYOUT_PAGES = ["home", "discussionList", "categoryList"];

export function CustomLayoutLabItem() {
    const config = useConfigsByKeys(LAYOUT_PAGES.map((page) => `customLayout.${page}`));

    const disabledProps = useMemo(() => {
        const { status, data } = config;

        // While loading, should be disabled to prevent user interactions
        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(status)) {
            return {
                disabled: true,
                disabledNote: t("Loading"),
            };
        }

        // Disable if any pages are applied
        if (
            status === LoadStatus.SUCCESS &&
            data &&
            Object.keys(data)
                .filter((key) => key.includes("customLayout"))
                .map((key) => data[key])
                .some((value) => value === true)
        ) {
            return {
                disabled: true,
                disabledNote: t("This lab cannot be disabled because a custom layout is applied."),
            };
        }

        // Default
        return {
            disabled: false,
        };
    }, [config]);

    return (
        <VanillaLabsItem
            imageUrl={image}
            themeFeatureName="layoutEditor"
            labName={"layoutEditor"}
            name={t("Layout Editor")}
            description={t(
                "Enable the Layout Editor to apply a custom layout for your community pages. Use our new editor to feature content using our catalogue of available  widgets.",
            )}
            notes={
                <LabThemeEditorNote
                    translatedLabName={t("Layout Editor")}
                    customSourceText="N.B. The new Layout Editor will inherit the theme set in your Style Guide (formally theme editor). <1>Find out more</1>"
                    docsUrl="https://success.vanillaforums.com/kb/articles/280-configuring-global-styles"
                />
            }
            {...disabledProps}
        />
    );
}
