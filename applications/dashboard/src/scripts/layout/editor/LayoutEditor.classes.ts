/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css, CSSObject } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { colorOut, styleUnit } from "@library/styles/styleHelpers";
import { useThemeCache } from "@library/styles/themeCache";
import { Property } from "csstype";

export const layoutEditorClasses = useThemeCache(() => {
    const globalVars = globalVariables();

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

    const focusShadow = `0 0 0 2px ${ColorsUtils.colorOut(globalVars.mainColors.primary)}`;

    const scrollableRegion = css({
        display: "flex",
        flexDirection: "column",
        minHeight: "100%",
    });

    const root = css({
        flex: "1 0 auto",
        position: "relative",
        display: "flex",
        flexDirection: "column",
        "&:focus": {
            outline: "none",
            boxShadow: focusShadow,
        },
    });

    const sectionToolbar = css({
        display: "flex",
        alignItems: "center",
    });

    const toolbarOffset = useThemeCache((offsetLeft) =>
        css({
            position: "absolute",
            flexDirection: "column",
            left: offsetLeft,
            top: "50%",
            transform: "translateY(-50%)",
            right: "initial",
            bottom: "initial",
        }),
    );

    const screen = css({
        position: "relative",
        textAlign: "initial",
    });

    const oneColumn = css({});

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
        ":hover, :focus, :active": {
            outline: "none",
            cursor: "pointer",
            backgroundColor: colorOut(column.color.bg),
            borderColor: colorOut(column.border.active),
        },
        "&:focus-visible, &.focus-visible": {
            boxShadow: focusShadow,
            borderWidth: 0,
        },
        "& .buttonCircle": {
            color: colorOut(column.border.color),
            borderColor: colorOut(column.border.color),
        },
        ":hover .buttonCircle, :focus .buttonCircle, :focus-within .buttonCircle, :active .buttonCircle": {
            color: colorOut(button.circle.active.fg),
            backgroundColor: colorOut(button.circle.active.bg),
            borderColor: colorOut(button.circle.active.border),
        },
    });

    const addSectionContextualMixin: CSSObject = {
        opacity: 0,
        "&:hover, &:focus, &:active, &:focus-within, &.focus-visible, &.isSelected": {
            opacity: 1,
        },
    };

    const addSectionContextualStatic = css({
        position: "static",
        bottom: "100%",
        left: 0,
        right: 0,
    });

    const addSectionContextualBefore = css({
        position: "absolute",
        bottom: "100%",
        left: 0,
        right: 0,
    });

    const addSectionContextualAfter = css({
        position: "absolute",
        top: "100%",
        left: 0,
        right: 0,
    });

    const addSection = css({
        position: "relative",
        ...Mixins.margin({
            vertical: 10,
            horizontal: "auto",
        }),
        width: "100%",
        padding: 0,
        height: button.size,
        overflow: "initial",
        "&:focus .buttonCircle, &:focus-within .buttonCircle, &:active .buttonCircle": {
            color: colorOut(button.circle.active.fg),
            backgroundColor: colorOut(button.circle.active.bg),
            borderColor: colorOut(button.circle.active.border),
        },
        "&:focus": {
            outline: "none",
        },
        "&.focus-visible:after": {
            content: "''",
            position: "absolute",
            top: 0,
            bottom: 0,
            left: -12,
            right: -12,
            boxShadow: focusShadow,
            borderRadius: 2,
        },
        ...addSectionContextualMixin,
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

    const section = useThemeCache((focusRingZIndex: Property.ZIndex = "initial") =>
        css({
            "&:focus": {
                outline: "none",
            },
            "&:before": {
                pointerEvents: "none",
                content: "''",
                position: "absolute",
                top: 0,
                bottom: 0,
                // 2 pixels to account for the shadow and 1 extra pixel so we aren't butted directly against the edge.
                left: 3,
                right: 3,
                borderRadius: 2,
                zIndex: focusRingZIndex,
            },
            "&.isFullWidth:before": {
                top: 0,
                left: 2,
                right: 2,
                bottom: 0,
            },
            "&.isActive:before": {
                border: "1px dashed #555A62",
            },
            "&.isActive:focus": {
                "&:before": {
                    boxShadow: focusShadow,
                    border: "none",
                },
            },
        }),
    );

    const widget = css({
        position: "relative",
        userSelect: "none",
    });

    const widgetBorder = css({
        pointerEvents: "none",
        content: "''",
        position: "absolute",
        top: 0,
        bottom: 0,
        left: 0,
        right: 0,
        boxShadow: focusShadow,
        borderRadius: 2,

        ".isFullWidth &": {
            top: 6,
            right: 8,
            left: 8,
            bottom: 6,
        },
    });

    const initialSectionForm = css({
        flex: "1 0 auto",
        paddingTop: 100,
    });

    const toolbarMenu = css({
        "&& button": {
            marginLeft: 2,
            marginRight: 2,
        },
    });

    return {
        root,
        screen,
        fullWidth,
        oneColumn,
        buttonLine,
        sectionToolbar,
        toolbarOffset,
        buttonCircle,
        addWidget,
        addSection,
        addSectionContextualStatic,
        addSectionContextualBefore,
        addSectionContextualAfter,
        section,
        widget,
        widgetBorder,
        initialSectionForm,
        toolbarMenu,
    };
});
