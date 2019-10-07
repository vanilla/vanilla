/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, colorOut, defaultTransition, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { calc, linearGradient, percent, px, translateY } from "csx";
import { buttonResetMixin } from "@library/forms/buttonStyles";
import { userLabelVariables } from "@library/content/userLabelStyles";

export const translationGridVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("translationGrid");
    const globalVars = globalVariables();
    const { mainColors } = globalVars;

    const paddings = makeThemeVars("paddings", {
        vertical: 9,
        horizontal: 12,
    });

    const header = makeThemeVars("header", {
        height: 52,
    });

    const cell = makeThemeVars("cell", {
        paddings: {
            vertical: 20,
            outer: 24,
            inner: 36,
        },
    });
    return { paddings, header, cell };
});

export const translationGridClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = translationGridVariables();
    const style = styleFactory("translationGrid");

    const isFirst = style("isFirst", {});
    const isLast = style("isLast", {});

    const root = style({});

    const inScrollContainer = style("inScrollContainer", {
        ...absolutePosition.fullSizeOfParent(),
    });

    const text = style("text", {});

    const row = style("row", {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "stretch",
    });

    const leftCell = style("leftCell", {
        width: percent(50),
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
    });

    const rightCell = style("rightCell", {
        width: percent(50),
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
    });

    const header = style("header", {
        display: "flex",
        flexWrap: "nowrap",
        width: percent(100),
        backgroundColor: colorOut(globalVars.mainColors.bg),
    });

    const frame = style("frame", {
        display: "flex",
        flexDirection: "column",
        height: percent(100),
        width: percent(100),
    });

    const headerLeft = style("headerLeft", {});

    const headerRight = style("headerRight", {});

    const inputWrapper = style("inputWrapper", {
        $nest: {
            "&&&": {
                margin: 0,
            },
        },
    });

    const input = style("input", {
        $nest: {
            "&&&": {
                border: 0,
                borderRadius: 0,
                minHeight: unit(0),
            },
        },
    });

    const body = style("body", {
        flexGrow: 1,
        height: calc(`100% - ${unit(vars.header.height)}`),
        overflow: "auto",
    });

    return {
        root,
        text,
        isFirst,
        isLast,
        row,
        leftCell,
        rightCell,
        header,
        headerLeft,
        frame,
        headerRight,
        input,
        inputWrapper,
        body,
        inScrollContainer,
    };
});
