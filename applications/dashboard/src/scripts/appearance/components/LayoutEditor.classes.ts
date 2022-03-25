/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, styleUnit } from "@library/styles/styleHelpers";
import { useThemeCache } from "@library/styles/themeCache";
import { Mixins } from "@library/styles/Mixins";
import { calc, viewHeight } from "csx";

export const layoutEditorClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const spacer = {
        maxWidth: 952,
    };

    const button = {
        size: 28,
        circle: {
            color: {
                bg: globalVars.elementaryColors.white,
                fg: globalVars.mainColors.primary,
                border: "#1B6496",
            },
            active: {
                bg: globalVars.mainColors.primary,
                fg: globalVars.elementaryColors.white,
                border: "#0055C0",
            },
        },
        line: {
            size: 4,
            color: globalVars.mainColors.primary,
        },
    };

    const column = {
        height: 80,
        topHeight: 50,
        color: {
            bg: "#ECF5FA",
        },
        border: {
            color: "#555A62",
            active: globalVars.elementaryColors.primary,
        },
    };

    const root = css({
        transition: "backgroundColor 0.25s ease",
        display: "flex",
        flexDirection: "column",
        justifyContent: "stretch",
        height: viewHeight(100),
        position: "relative",
        overflow: "hidden",
    });

    const screen = css({
        position: "relative",
        textAlign: "initial",
        width: calc(`100% - 2px`),
        margin: "0 auto",
        overflowY: "auto",
    });

    const oneColumn = css({
        padding: 0,
        "> button, > div": {
            maxWidth: spacer.maxWidth + globalVars.widget.padding * 2,
            margin: "0 auto",
            width: styleUnit("100%"),
        },
    });

    const fullWidth = css({
        position: "relative",
    });

    const addWidget = css({
        minHeight: column.height,
        borderRadius: 4,
        border: "1px solid",
        borderColor: colorOut(column.border.color),
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        width: styleUnit("100%"),
        position: "relative",
        ":hover, :focus, :focus-within, :active": {
            cursor: "pointer",
            backgroundColor: colorOut(column.color.bg),
            borderColor: colorOut(column.border.active),
        },
        ".buttonCircle": {
            color: colorOut(column.border.color),
            borderColor: colorOut(column.border.color),
        },
        ":hover .buttonCircle, :focus .buttonCircle, :focus-within .buttonCircle, :active .buttonCircle": {
            color: colorOut(button.circle.active.fg),
            backgroundColor: colorOut(button.circle.active.bg),
            borderColor: colorOut(button.circle.active.border),
        },
    });

    const addSection = css({
        maxWidth: spacer.maxWidth,
        ...Mixins.margin({
            vertical: globalVars.spacer.componentInner,
            horizontal: "auto",
        }),
        width: "100%",
        padding: 0,
        height: button.size,
        ":hover .buttonCircle, :focus .buttonCircle, :focus-within .buttonCircle, :active .buttonCircle": {
            color: colorOut(button.circle.active.fg),
            backgroundColor: colorOut(button.circle.active.bg),
            borderColor: colorOut(button.circle.active.border),
        },
    });

    const buttonLine = css({
        position: "relative",
        height: button.line.size,
        borderRadius: button.line.size,
        border: "2px solid",
        backgroundColor: colorOut(button.line.color),
        borderColor: colorOut(button.line.color),
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        width: "100%",
    });

    const buttonCircle = css({
        borderRadius: "50%",
        color: colorOut(button.circle.color.fg),
        backgroundColor: colorOut(button.circle.color.bg),
        borderColor: colorOut(button.circle.color.border),
        border: "1px solid",
        padding: 0,
        width: button.size,
        height: button.size,
        lineHeight: button.size,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        position: "absolute",
        ...Mixins.margin({ horizontal: "auto" }),
        zIndex: 100,
    });

    // overwrite section CSS that are needed for the layout but not for the editor
    const sectionOverwrite = css({
        "&&": {
            ...Mixins.margin({
                vertical: 0,
                horizontal: "auto",
            }),
            width: calc(`100% - 2px`),
        },
        "&:hover": {
            outline: "1px dashed #555A62",
        },
        "&:focus, &.focus-visible, &:active": {
            outline: "-webkit-focus-ring-color auto 1px",
        },
        '[class*="-container-container"]': {
            maxWidth: spacer.maxWidth + (12 + globalVars.widget.padding) * 2,
            ...Mixins.padding({ horizontal: "12px", vertical: "4px" }),
            ...Mixins.margin({
                vertical: globalVars.spacer.componentInner,
                horizontal: "auto",
            }),
            postion: "relative",
        },
        '[class*="WidgetLayout-styles-widget"]': {
            ...Mixins.margin({
                vertical: globalVars.spacer.componentInner,
                horizontal: "auto",
            }),
        },
        '[class*="SectionOneColumn"]': {
            maxWidth: spacer.maxWidth + (12 + globalVars.widget.padding) * 2,
            width: spacer.maxWidth,
            ...Mixins.margin({
                vertical: globalVars.spacer.componentInner,
                horizontal: "auto",
            }),
        },
        '[class*="twoColumnLayout-main"], [class*="threeColumnLayout-main"]': {
            minHeight: 0,
        },
        '[class*="-isSticky"]': {
            position: "static",
            top: 0,
            height: "initial",
        },
        '[class*="panelArea-areaOverlay"]': {
            height: 0,
        },
        '[class*="panelArea-overflowFull"]': {
            height: 0,
            marginTop: 0,
            ...Mixins.padding({ vertical: 0 }),
        },
        '[class*="classes-widgetClass"]': {
            marginBotton: 0,
            ...Mixins.padding({
                all: globalVars.widget.padding,
            }),
        },
    });

    return {
        root,
        screen,
        fullWidth,
        oneColumn,
        buttonLine,
        buttonCircle,
        addWidget,
        addSection,
        sectionOverwrite,
    };
});
