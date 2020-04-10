/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { fontKey, GoogleFontDropdown } from "@themingapi/theme/GoogleFontDropdown";
import { ThemeBuilderBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { t } from "@vanilla/i18n/src";
import { ThemeBuilderFontDeclaration } from "./ThemeBuilderFontDeclaration";

export function ThemeBuilderFontBlock() {
    const { generatedValue, rawValue } = useThemeVariableField(fontKey);
    const customFont = (generatedValue ?? rawValue) === "custom";
    return (
        <>
            <ThemeBuilderBlock label={t("Font")}>
                <GoogleFontDropdown />
            </ThemeBuilderBlock>
            {customFont && <ThemeBuilderFontDeclaration />}
        </>
    );
}
