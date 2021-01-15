/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { themeCardVariables } from "./themePreviewCardStyles";
import { percent, color, px } from "csx";
import { Mixins } from "@library/styles/Mixins";

export const currentThemeVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("currentThemeInfo");
    const globalVars = globalVariables();

    const colors = makeThemeVars("colors", {
        fg: globalVars.messageColors.warning.fg,
        white: color("#ffffff"),
        btnTextColor: color("#555a62"),
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

    const themeContainer = makeThemeVars("themeContainer", {
        margin: {
            top: 18,
            right: 28,
            bottom: 0,
            left: 26,
        },
    });
    return {
        flag,
        name,
        authorName,
        themeContainer,
        colors,
    };
});

export const currentThemeClasses = useThemeCache(() => {
    const vars = currentThemeVariables();
    const globalVars = globalVariables();

    const style = styleFactory("currentThemeInfo");

    const root = style({
        display: "flex",
        flexWrap: "wrap",
        backgroundColor: "#f6f9fb",
        ...Mixins.padding({
            horizontal: globalVars.gutter.size,
            vertical: globalVars.gutter.size + globalVars.gutter.half,
        }),
        marginLeft: -18,
        marginRight: -18,
    });

    const cardContainer = style("cardContainer", {
        maxWidth: percent(100),
        width: styleUnit(400),
    });

    const themeContainer = style("themeContainer", {
        display: "flex",
        flex: 1,
        ...Mixins.margin({
            top: styleUnit(vars.themeContainer.margin.top),
            bottom: styleUnit(vars.themeContainer.margin.bottom),
            left: styleUnit(vars.themeContainer.margin.left),
            right: styleUnit(vars.themeContainer.margin.right),
        }),
        maxWidth: percent(100),
        position: "relative",
    });

    const flag = style("flag", {
        display: "inline-block",
        paddingLeft: styleUnit(vars.flag.padding.left),
        paddingRight: styleUnit(vars.flag.padding.right),
        lineHeight: styleUnit(0),
        fontSize: styleUnit(9),
        color: globalVars.elementaryColors.white.toString(),
        borderStyle: "solid",
        borderColor: "#f5296d",
        borderRightColor: "transparent",
        borderWidth: styleUnit(9),
        textTransform: "uppercase",
        marginBottom: styleUnit(vars.flag.margin.bottom - 2),
    });

    const name = style("name", {
        fontSize: styleUnit(globalVars.fonts.size.large),
        color: globalVars.mainColors.fg.toString(),
        marginBottom: styleUnit(vars.flag.margin.bottom - 6),
        ...{
            ["& h5"]: {
                fontWeight: globalVars.fonts.weights.semiBold,
            },
        },
    });

    const authorName = style("authorName", {
        fontSize: styleUnit(globalVars.fonts.size.small),
        fontWeight: globalVars.fonts.weights.normal,
        color: globalVars.mainColors.primary.toString(),
        paddingBottom: styleUnit(vars.authorName.padding.bottom),
        ...{
            ["& span"]: {
                color: globalVars.mainColors.fg.toString(),
            },
        },
    });

    const description = style("description", {
        fontSize: styleUnit(globalVars.fonts.size.medium),
        fontWeight: globalVars.fonts.weights.normal,
        color: globalVars.mainColors.fg.toString(),
        lineHeight: styleUnit(20),
    });

    const themeActionButtons = style("themeActionButtons", {
        flexDirection: "column",
        display: "flex",
        flex: 0,
        marginTop: styleUnit(vars.themeContainer.margin.top + 10),
    });

    const themeActionButton = style("themeActionButton", {
        ...{
            "&&": {
                marginBottom: styleUnit(vars.flag.margin.bottom),
                width: styleUnit(180),
            },
        },
    });

    const themeInfo = style("themeInfo", {
        flex: 1,
        width: percent(100),
        minWidth: px(220),
        marginRight: styleUnit(vars.themeContainer.margin.right + 20),
    });

    return {
        root,
        cardContainer,
        themeContainer,
        flag,
        name,
        authorName,
        description,
        themeActionButtons,
        themeActionButton,
        themeInfo,
    };
});

export default currentThemeClasses;
