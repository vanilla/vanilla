/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ThemeDropDown } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeDropDown";
import { useThemeBuilder } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderContext";

export const fontKey = "global.fonts.googleFontFamily";

export function GoogleFontDropdown(props: { forceDefaultKey?: string; disabled?: boolean }) {
    const { setVariableValue } = useThemeBuilder();
    const { forceDefaultKey, disabled } = props;

    return (
        <ThemeDropDown
            // This is actually an array, but the first is the real one. The rest are fallbacks.
            variableKey={fontKey}
            forceDefaultKey={forceDefaultKey}
            disabled={!!props.forceDefaultKey || disabled}
            afterChange={(value) => {
                setVariableValue("global.fonts.forceGoogleFont", value === "custom" ? false : !!value);
            }}
            options={[
                { label: "Custom Font", value: "custom" },
                { label: "Open Sans", value: "Open Sans" },
                { label: "Roboto", value: "Roboto" },
                { label: "Lato", value: "Lato" },
                { label: "Montserrat", value: "Montserrat" },
                { label: "Roboto Condensed", value: "Roboto Condensed" },
                { label: "Source Sans Pro", value: "Source Sans Pro" },
                { label: "Merriweather", value: "Merriweather" },
                { label: "Raleway", value: "Raleway" },
                { label: "Roboto Mono", value: "Roboto Mono" },
                { label: "Poppins", value: "Poppins" },
                { label: "Nunito", value: "Nunito" },
                { label: "PT Serif", value: "PT Serif" },
            ]}
        />
    );
}
