/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { ThemeBuilderBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { t } from "@vanilla/i18n/src";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";
import { CustomFontUrl } from "@library/forms/themeEditor/CustomFontUrl";
import { CustomFontFamily } from "@library/forms/themeEditor/CustomFontFamily";
import { fontKey, GoogleFontDropdown } from "./GoogleFontDropdown";

export function ThemeBuilderFontBlock(props: { forceDefaultKey?: string; forceError?: boolean }) {
    const { generatedValue } = useThemeVariableField(fontKey);
    const customFont = generatedValue === "custom" || props.forceDefaultKey === "custom";
    const docUrl = "https://success.vanillaforums.com/kb/articles/260-custom-font";
    const classes = themeBuilderClasses();
    return (
        <>
            <ThemeBuilderBlock label={t("Font")}>
                <GoogleFontDropdown forceDefaultKey={props.forceDefaultKey} />
            </ThemeBuilderBlock>
            {customFont && (
                <>
                    <ThemeBuilderBlock label={t("Font URL")} docUrl={docUrl}>
                        <CustomFontUrl forceError={props.forceError} />
                    </ThemeBuilderBlock>
                    <ThemeBuilderBlock label={t("Font Name")}>
                        <CustomFontFamily />
                    </ThemeBuilderBlock>
                </>
            )}
        </>
    );
}
