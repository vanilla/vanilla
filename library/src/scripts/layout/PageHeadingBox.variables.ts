/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { GlobalVariableMapping } from "@library/styles/VariableMapping";
import { Variables } from "@library/styles/Variables";

export enum SubtitleType {
    STANDARD = "standard",
    OVERLINE = "overline",
}

export interface IPageHeadingBoxOptions {
    subtitleType: SubtitleType;
    alignment: "left" | "center";
}

export const pageHeadingBoxVariables = useThemeCache((overrideOptions?: Partial<IPageHeadingBoxOptions>) => {
    const globalVars = globalVariables();
    const makeVars = variableFactory("pageHeadingBox", undefined, [
        new GlobalVariableMapping({
            "homeWidgetContainer.options.subtitle.font": "subtitle.font",
            "homeWidgetContainer.options.subtitle.type": "options.subtitleType",
        }),
    ]);

    const options: IPageHeadingBoxOptions = makeVars(
        "options",
        {
            subtitleType: SubtitleType.OVERLINE,
            alignment: "left",
        },
        overrideOptions,
    );

    const subtitle = makeVars("subtitle", {
        font: Variables.font(
            options.subtitleType === SubtitleType.OVERLINE
                ? {
                      letterSpacing: 1,
                      transform: "uppercase",
                      size: globalVars.fonts.size.small,
                      color: globalVars.mainColors.primary,
                      weight: globalVars.fonts.weights.normal,
                  }
                : {
                      size: globalVars.fonts.size.subTitle,
                      weight: globalVars.fonts.weights.semiBold,
                  },
        ),
    });

    return { options, subtitle };
});
