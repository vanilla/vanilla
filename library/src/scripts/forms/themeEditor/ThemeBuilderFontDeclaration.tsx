/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { ThemeBuilderBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { t } from "@vanilla/i18n/src";
import { isAllowedUrl } from "@library/utility/appUtils";
import { CustomFontFamilyName } from "@themingapi/theme/CustomFontFamilyName";
import { CustomFontUrl } from "@themingapi/theme/CustomFontUrl";

export const fontURLKey = "global.fonts.customFontUrl";

export function ThemeBuilderFontDeclaration() {
    const { generatedValue, initialValue, rawValue } = useThemeVariableField(fontURLKey);
    const validURL = isAllowedUrl(generatedValue ?? rawValue);
    return (
        <>
            <ThemeBuilderBlock
                label={t("Font URL")}
                info={t("You can upload a Custom Font in your Theming System. Just copy & paste the URL in the field.")}
            >
                <CustomFontUrl />
            </ThemeBuilderBlock>
            {validURL && (
                <ThemeBuilderBlock label={t("Font Name")}>
                    <CustomFontFamilyName />
                </ThemeBuilderBlock>
            )}
        </>
    );
}
