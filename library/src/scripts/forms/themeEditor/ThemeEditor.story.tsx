/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { BannerAlignment } from "@library/banner/bannerStyles";
import { ThemeBuilderBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { ThemeBuilderCheckBox } from "@library/forms/themeEditor/ThemeBuilderCheckBox";
import { ThemeBuilderContextProvider } from "@library/forms/themeEditor/ThemeBuilderContext";
import { ThemeBuilderSection } from "@library/forms/themeEditor/ThemeBuilderSection";
import { ThemeBuilderTitle } from "@library/forms/themeEditor/ThemeBuilderTitle";
import { ThemeBuilderUpload } from "@library/forms/themeEditor/ThemeBuilderUpload";
import { ThemeColorPicker } from "@library/forms/themeEditor/ThemeColorPicker";
import { ThemeDropDown } from "@library/forms/themeEditor/ThemeDropDown";
import { ThemeInputNumber } from "@library/forms/themeEditor/ThemeInputNumber";
import { action } from "@storybook/addon-actions";
import { t } from "@vanilla/i18n";
import React, { useState } from "react";
import { ThemeToggle } from "@library/forms/themeEditor/ThemeToggle";
import { ThemeBuilderBreakpoints, BreakpointViewType } from "@library/forms/themeEditor/ThemeBuilderBreakpoints";

export default {
    title: "Forms",
};
import { ThemeInputText } from "@library/forms/themeEditor/ThemeInputText";
import { ThemeBuilderFontBlock } from "@library/forms/themeEditor/ThemeBuilderFontBlock";

export function ThemeEditor() {
    const [vars, setVars] = useState({});
    return (
        <div style={{ maxWidth: 400, margin: "0 auto" }}>
            <ThemeBuilderContextProvider
                rawThemeVariables={vars}
                onChange={(vars) => {
                    action("Variable Change");
                    setVars(vars);
                }}
            >
                <ThemeBuilderTitle title="Title Component" />
                <ThemeBuilderBlock label={t("Option Chooser")}>
                    <ThemeDropDown
                        variableKey="banner.options.alignment"
                        options={[
                            {
                                label: "Option 1",
                                value: BannerAlignment.LEFT,
                            },
                            {
                                label: "Option 2",
                                value: BannerAlignment.CENTER,
                            },
                        ]}
                    />
                </ThemeBuilderBlock>
                <ThemeBuilderBlock label={t("Toggle")} info={"This is a Toggle component."}>
                    <ThemeToggle variableKey="banner.backgrounds.useOverlay" />
                </ThemeBuilderBlock>
                <ThemeBuilderSection label="Section">
                    <ThemeBuilderBlock label={t("Image Upload")}>
                        <ThemeBuilderUpload variableKey="banner.outerBackground.image" />
                    </ThemeBuilderBlock>
                    <ThemeBuilderCheckBox
                        label={t("Checkbox")}
                        variableKey="banner.backgrounds.useOverlay"
                        info={"Test tooltip"}
                    />
                    <ThemeBuilderBlock label={t("Color")}>
                        <ThemeColorPicker variableKey="banner.colors.primary" />
                    </ThemeBuilderBlock>
                    <ThemeBuilderBlock label={t("Number Radius")}>
                        <ThemeInputNumber variableKey="global.border.radius" max={10} step={2} min={2} />
                    </ThemeBuilderBlock>
                    <ThemeBuilderBreakpoints
                        baseKey="contentBanner.outerBackground.bogus"
                        responsiveKey="image"
                        enabledView={BreakpointViewType.IMAGE}
                    />
                    <ThemeBuilderBreakpoints
                        label={"Open by default"}
                        baseKey="contentBanner.outerBackground"
                        responsiveKey="image"
                        enabledView={BreakpointViewType.IMAGE}
                        forceState={true}
                        toggleDisabled={true}
                    />
                </ThemeBuilderSection>
                <ThemeBuilderSection label="Font">
                    <ThemeBuilderFontBlock forceDefaultKey={"custom"} forceError={true} />
                </ThemeBuilderSection>
            </ThemeBuilderContextProvider>
        </div>
    );
}
