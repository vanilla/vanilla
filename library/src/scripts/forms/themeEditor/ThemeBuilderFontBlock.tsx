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
import { CustomFontFamily } from "@themingapi/theme/CustomFontFamily";
import { CustomFontUrl } from "@themingapi/theme/CustomFontUrl";

export function ThemeBuilderFontBlock(props: { forceDefaultKey?: string; forceError?: boolean }) {
    const { generatedValue } = useThemeVariableField(fontKey);
    const customFont = generatedValue === "custom" || props.forceDefaultKey === "custom";
    return (
        <>
            <ThemeBuilderBlock label={t("Font")}>
                <GoogleFontDropdown forceDefaultKey={props.forceDefaultKey} />
            </ThemeBuilderBlock>
            {customFont && (
                <>
                    <ThemeBuilderBlock
                        label={t("Font URL")}
                        info={t(
                            "You can upload a Custom Font in your Theming System. Just copy & paste the URL in the field.",
                        )}
                    >
                        <CustomFontUrl forceError={true} />
                    </ThemeBuilderBlock>
                    <ThemeBuilderBlock label={t("Font Name")}>
                        <CustomFontFamily />
                    </ThemeBuilderBlock>
                </>
            )}
        </>
    );
}
