/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, calc } from "csx";
import { unit, absolutePosition, colorOut, fonts } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { layoutVariables } from "@vanilla/library/src/scripts/layout/panelLayoutStyles";
import { themeBuilderVariables } from "@library/forms/themeEditor/themeBuilderStyles";
import { globalVariables } from "@library/styles/globalStyleVars";

// Intentionally not overwritable.
export const themeEditorVariables = () => {
    const frame = {
        width: 100,
    };

    const panel = {
        width: 376,
    };

    return {
        frame,
        panel,
    };
};

export const themeEditorClasses = useThemeCache(() => {
    const vars = themeEditorVariables();
    const globalVars = globalVariables();
    const themeBuilderVars = themeBuilderVariables();
    const style = styleFactory("themeEditor");

    const mediaQueries = layoutVariables().mediaQueries();
    const wrapper = style(
        "wrapper",
        {
            ...absolutePosition.fullSizeOfParent(),
            width: percent(100),
            height: percent(100),
            $nest: {
                "&&&": {
                    display: "flex",
                },
            },
        },
        mediaQueries.oneColumnDown({
            display: "block",
        }),
    );
    const frame = style(
        "frame",
        {
            width: calc(`${percent(vars.frame.width)} - ${unit(vars.panel.width)}`),
            flexBasis: calc(`${percent(vars.frame.width)} - ${unit(vars.panel.width)}`),
            height: percent(100),
        },

        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    const panel = style(
        "panel",
        {
            boxShadow: "0 5px 10px 0 rgba(0, 0, 0, 0.3)",
            width: unit(vars.panel.width),
            flexBasis: unit(vars.panel.width),
            zIndex: 1,
            $nest: {
                "& .SelectOne__single-value": {
                    ...fonts(themeBuilderVars.defaultFont),
                },
                "& .SelectOne__value-container, & .SelectOne__menu": {
                    background: colorOut(themeBuilderVars.panel.bg),
                    ...fonts(themeBuilderVars.defaultFont),
                },
                "& .suggestedTextInput-option.suggestedTextInput-option.isFocused": {
                    background: colorOut(
                        themeBuilderVars.mainColors.primary.mix(
                            themeBuilderVars.mainColors.bg,
                            globalVars.constants.states.hover.stateEmphasis,
                        ),
                    ),
                },
            },
        },
        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    return {
        frame,
        wrapper,
        panel,
    };
});
