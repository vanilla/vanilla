/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, calc } from "csx";
import { unit, absolutePosition } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { layoutVariables } from "@vanilla/library/src/scripts/layout/panelLayoutStyles";

// Intentionally not overwritable.
export const themeEditorVariables = () => {
    const frameContainer = {
        width: 100,
    };
    const frame = {
        width: 100,
    };

    const panel = {
        width: 376,
    };
    const styleOptions = {
        width: 376,
        mobile: {
            margin: {
                top: 12,
            },
        },
        borderRaduis: 6,
    };

    return {
        frame,
        panel,
        frameContainer,
        styleOptions,
    };
};

export const themeEditorClasses = useThemeCache(() => {
    const vars = themeEditorVariables();
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
            // width: calc(`${percent(vars.frame.width)} - ${unit(vars.panel.width)}`),
            flexBasis: calc(`${percent(vars.frame.width)} - ${unit(vars.panel.width)}`),
            height: percent(100),
        },

        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    const styleOptions = style(
        "styleOptions",
        {
            boxShadow: "0 5px 10px 0 rgba(0, 0, 0, 0.3)",
            width: unit(vars.panel.width),
            flexBasis: unit(vars.panel.width),
        },
        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );
    const frameContainer = style(
        "frameContainer",
        {
            // width: calc(`${percent(vars.frameContainer.width)} - ${unit(vars.styleOptions.width)}`),
            flexBasis: calc(`${percent(vars.frameContainer.width)} - ${unit(vars.styleOptions.width)}`),
            height: percent(100),
        },

        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    return {
        frame,
        wrapper,
        styleOptions,
        frameContainer,
    };
});
