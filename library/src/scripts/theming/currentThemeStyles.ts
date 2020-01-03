/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit, paddings, margins } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { themeCardVariables } from "./themeCardStyles";
import { percent } from "csx";

export const currentThemeVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("currentThemeInfo");
    const globalVars = globalVariables();

    const colors = makeThemeVars("colors", {
        fg: globalVars.messageColors.warning.fg,
    });

    const flag = makeThemeVars("flag", {
        padding: {
            left: 2,
            right: 12,
        },
        margin: {
            bottom: 15,
        },
    });

    const name = makeThemeVars("name", {
        padding: {
            bottom: 7,
        },
    });

    const authorName = makeThemeVars("name", {
        padding: {
            bottom: 18,
        },
    });

    const addTheme = makeThemeVars("addTheme", {
        width: 310,
        height: 225,

        padding: {
            top: 70,
            bottom: 70,
            right: 117,
            left: 117,
        },
    });

    const themeContainer = makeThemeVars("themeContainer", {
        margin: {
            top: 32,
            right: 28,
            bottom: 0,
            left: 26,
        },
        width: 596,
    });
    return {
        flag,
        name,
        authorName,
        addTheme,
        themeContainer,
    };
});

export const currentThemeClasses = useThemeCache(() => {
    const vars = currentThemeVariables();
    const globalVars = globalVariables();

    const style = styleFactory("currentThemeInfo");

    const themeContainer = style("themeContainer", {
        display: "flex",
        ...margins({
            top: unit(vars.themeContainer.margin.top),
            bottom: unit(vars.themeContainer.margin.bottom),
            left: unit(vars.themeContainer.margin.left),
            right: unit(vars.themeContainer.margin.right),
        }),
        maxWidth: percent(100),
        position: "relative",
        width: unit(vars.themeContainer.width),
    });

    const flag = style("flag", {
        display: "inline-block",
        paddingLeft: unit(vars.flag.padding.left),
        paddingRight: unit(vars.flag.padding.right),
        lineHeight: unit(0),
        fontSize: unit(9),
        color: globalVars.elementaryColors.white.toString(),
        borderStyle: "solid",
        borderColor: "#f5296d",
        borderRightColor: "transparent",
        borderWidth: unit(9),
        textTransform: "uppercase",
        marginBottom: unit(vars.flag.margin.bottom - 15),
    });

    const name = style("name", {
        fontSize: unit(globalVars.fonts.size.large),
        color: globalVars.mainColors.fg.toString(),
        $nest: {
            ["& h5"]: {
                fontWeight: globalVars.fonts.weights.semiBold,
            },
        },
    });

    const authorName = style("authorName", {
        fontSize: unit(globalVars.fonts.size.small),
        fontWeight: globalVars.fonts.weights.normal,
        color: globalVars.mainColors.primary.toString(),
        paddingBottom: unit(vars.authorName.padding.bottom),
        $nest: {
            ["& span"]: {
                color: globalVars.mainColors.fg.toString(),
            },
        },
    });

    const description = style("description", {
        fontSize: unit(globalVars.fonts.size.medium),
        fontWeight: globalVars.fonts.weights.normal,
        color: globalVars.mainColors.fg.toString(),
        lineHeight: unit(20),
    });

    const themeActionButtons = style("actionButtons", {
        flexDirection: "column",
        flex: 1,
        marginTop: unit(vars.themeContainer.margin.top + 10),
        $nest: {
            ["& button"]: {
                marginBottom: unit(vars.flag.margin.bottom),
                width: unit(144),
            },
        },
    });

    const themeInfo = style("themeInfo", {
        width: percent(100),
        marginRight: unit(vars.themeContainer.margin.right + 20),
    });

    const addTheme = style("addTheme", {
        width: unit(vars.addTheme.width),
        height: unit(vars.addTheme.height),
        border: "1px dashed #979797",
        ...paddings({
            top: unit(vars.addTheme.padding.top),
            bottom: unit(vars.addTheme.padding.bottom),
            left: unit(vars.addTheme.padding.left),
            right: unit(vars.addTheme.padding.right),
        }),
    });

    return {
        themeContainer,
        flag,
        name,
        authorName,
        description,
        themeActionButtons,
        addTheme,
        themeInfo,
    };
});

export default currentThemeClasses;
