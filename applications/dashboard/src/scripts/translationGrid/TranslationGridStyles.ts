/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, percent, translate } from "csx";
import { dropDownVariables } from "@library/flyouts/dropDownStyles";
import { toolTipClasses } from "@library/toolTip/toolTipStyles";
import { Mixins } from "@library/styles/Mixins";

export const translationGridVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("translationGrid");
    const globalVars = globalVariables();

    const paddings = makeThemeVars("paddings", {
        vertical: 8,
        horizontal: 12,
    });

    const header = makeThemeVars("header", {
        height: 52,
    });

    const cell = makeThemeVars("cell", {
        color: globalVars.mixBgAndFg(0.22),
        paddings: {
            inner: 20,
            outer: 15,
        },
    });

    return { paddings, header, cell };
});

export const translationGridClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElVars = formElementsVariables();
    const vars = translationGridVariables();
    const style = styleFactory("translationGrid");

    const innerPadding = vars.cell.paddings.inner;
    const oneLineHeight = Math.ceil(globalVars.lineHeights.condensed * globalVars.fonts.size.medium);

    const dropDownOffset = dropDownVariables().item.padding.horizontal;

    const input = style("input", {
        ...{
            "&&": {
                border: 0,
                borderRadius: 0,
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("medium"),
                    lineHeight: globalVars.lineHeights.condensed,
                }),
                ...Mixins.padding({
                    vertical: vars.cell.paddings.inner,
                    left: vars.cell.paddings.outer + vars.cell.paddings.inner + 3,
                    right: vars.cell.paddings.inner,
                }),
                flexGrow: 1,
            },
        },
    });

    const isFirst = style("isFirst", {
        ...{
            [`.${input}.${input}.${input}`]: {
                paddingTop: styleUnit(vars.cell.paddings.inner - vars.paddings.vertical),
            },
        },
    });

    const editIcon = style("editIcon", {
        top: styleUnit(
            vars.cell.paddings.inner + Math.floor(globalVars.lineHeights.condensed * globalVars.fonts.size.medium) / 2,
        ),
        ...Mixins.margin({
            top: -4,
            left: -2,
        }),
    });

    const isLast = style("isLast", {});

    const root = style({});

    const inScrollContainer = style("inScrollContainer", {
        ...Mixins.absolute.fullSizeOfParent(),
    });

    const text = style("text", {});

    const row = style("row", {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "stretch",
    });

    const languageDropdown = style("languageDropdown", {
        width: 200,
        ...{
            ul: {
                fontWeight: "normal",
            },
        },
    });

    const languageDropdownToggle = style("languageDropdownToggle", {
        paddingRight: styleUnit(dropDownOffset),
    });

    const leftCell = style("leftCell", {
        width: percent(50),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium"),
            lineHeight: globalVars.lineHeights.condensed,
        }),

        cursor: "default",
        borderRight: singleBorder({
            color: vars.cell.color,
        }),
        borderBottom: singleBorder({
            color: vars.cell.color,
        }),
        ...Mixins.padding({
            vertical: vars.cell.paddings.inner,
            left: vars.cell.paddings.outer,
            right: vars.cell.paddings.outer + vars.cell.paddings.inner,
        }),
        ...{
            [`&.${isLast}`]: {
                borderBottom: 0,
            },
        },
    });

    const rightCell = style("rightCell", {
        width: percent(50),
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        position: "relative",
        borderBottom: singleBorder({
            color: vars.cell.color,
        }),
        ...{
            [`&.${isLast}`]: {
                borderBottom: 0,
            },
            [`.${toolTipClasses().noPointerTrigger}`]: {
                minWidth: styleUnit(34),
                transform: translate(0, percent(-50)),
            },
        },
    });

    const header = style("header", {
        display: "flex",
        flexWrap: "nowrap",
        width: percent(100),
        height: 55,
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg),
    });

    const frame = style("frame", {
        display: "flex",
        flexDirection: "column",
        height: percent(100),
        width: percent(100),
    });

    const headerLeft = style("headerLeft", {
        fontWeight: globalVars.fonts.weights.semiBold,
        ...Mixins.padding({
            vertical: vars.cell.paddings.outer + vars.paddings.vertical - 3,
            horizontal: vars.cell.paddings.outer + vars.paddings.horizontal, //vars.cell.paddings.outer + vars.paddings.horizontal
        }),
        borderRight: "none",
    });

    const headerRight = style("headerRight", {
        fontWeight: globalVars.fonts.weights.semiBold,
        ...Mixins.padding({
            right: vars.cell.paddings.outer + vars.cell.paddings.inner + 3,
            left: vars.cell.paddings.outer + vars.cell.paddings.inner + 3,
        }),
    });

    const fullHeight = style("fullHeight", {
        ...{
            "&&": {
                display: "flex",
                alignItems: "center",
                justifyContent: "stretch",
                flexGrow: 1,
                height: percent(100),
            },
        },
    });

    const inputWrapper = style("inputWrapper", {
        width: percent(100),
        ...{
            "&&&": {
                margin: 0,
                minHeight: styleUnit(oneLineHeight),
            },
        },
    });

    const body = style("body", {
        flexGrow: 1,
        height: calc(`100% - ${styleUnit(vars.header.height)}`),
        overflow: "auto",
        ...Mixins.padding(vars.paddings),
    });

    const multiLine = style("multiLine", {
        ...{
            "&&&": {
                minHeight: percent(100),
            },
        },
    });

    const iconOffset = -8;

    const icon = style("icon", {
        position: "absolute",
        display: "block",
        left: styleUnit((vars.cell.paddings.outer + vars.cell.paddings.inner) / 2),
        transform: translate(styleUnit(iconOffset + 2) as string, styleUnit(iconOffset) as string),
        "&.isFirst": {
            transform: translate(styleUnit(iconOffset) as string, styleUnit(iconOffset - 3) as string),
        },
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
        headerRight,
        frame,
        input,
        inputWrapper,
        body,
        inScrollContainer,
        fullHeight,
        multiLine,
        icon,
        languageDropdown,
        languageDropdownToggle,
        editIcon,
    };
});
