/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { CSSObject } from "@emotion/serialize";
import { bodyStyleMixin } from "@library/layout/bodyStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder, styleUnit } from "@library/styles/styleHelpers";
import { useThemeCache } from "@library/styles/themeCache";
import { Property } from "csstype";

export const layoutEditorClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const button = {
        size: 28,
        circle: {
            color: {
                bg: globalVars.mainColors.bg,
                fg: globalVars.mainColors.fg,
                border: globalVars.mainColors.fg,
            },
            active: {
                bg: globalVars.mainColors.primary,
                fg: globalVars.mainColors.primaryContrast,
                border: globalVars.mainColors.primary,
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
            bg: globalVars.states.hover.highlight,
        },
        border: {
            color: globalVars.mainColors.fg,
            active: globalVars.mainColors.primary,
        },
    };

    const focusShadow = `0 0 0 2px ${ColorsUtils.colorOut(globalVars.mainColors.primary)}`;

    const root = css({
        ...bodyStyleMixin(),
        flex: "1",
        position: "relative",
        "&:focus, &:focus-visible": {
            outline: "none",
            boxShadow: focusShadow,
        },
        overflow: "auto",
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
        borderColor: ColorsUtils.colorOut(column.border.color),
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        width: styleUnit("100%"),
        position: "relative",
        ":hover, :focus, :active": {
            outline: "none",
            cursor: "pointer",
            backgroundColor: ColorsUtils.colorOut(column.color.bg),
            borderColor: ColorsUtils.colorOut(column.border.active),
        },
        "&:focus-visible, &.focus-visible": {
            boxShadow: focusShadow,
            borderWidth: 0,
        },
        "& .buttonCircle": {
            color: ColorsUtils.colorOut(column.border.color),
            borderColor: ColorsUtils.colorOut(column.border.color),
        },
        ":hover .buttonCircle, :focus .buttonCircle, :focus-within .buttonCircle, :active .buttonCircle": {
            color: ColorsUtils.colorOut(button.circle.active.fg),
            backgroundColor: ColorsUtils.colorOut(button.circle.active.bg),
            borderColor: ColorsUtils.colorOut(button.circle.active.border),
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
            color: ColorsUtils.colorOut(button.circle.active.fg),
            backgroundColor: ColorsUtils.colorOut(button.circle.active.bg),
            borderColor: ColorsUtils.colorOut(button.circle.active.border),
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
        backgroundColor: ColorsUtils.colorOut(button.line.color),
        borderColor: ColorsUtils.colorOut(button.line.color),
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        width: "100%",
    });

    const buttonCircle = css({
        borderRadius: "50%",
        color: ColorsUtils.colorOut(button.circle.color.fg),
        backgroundColor: ColorsUtils.colorOut(button.circle.color.bg),
        borderColor: ColorsUtils.colorOut(button.circle.color.border),
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
        zIndex: 1,
    });

    const section = useThemeCache((focusRingZIndex: Property.ZIndex = "initial") =>
        css({
            "&:focus": {
                outline: "none",
            },
            "&:after": {
                pointerEvents: "none",
                content: "''",
                position: "absolute",
                top: -16,
                bottom: -16,
                // 2 pixels to account for the shadow and 1 extra pixel so we aren't butted directly against the edge.
                left: 3,
                right: 3,
                borderRadius: 6,
                zIndex: 1,
            },
            "&.isFullWidth:after": {
                top: 0,
                left: 2,
                right: 2,
                bottom: 0,
            },
            "&.isActive:after": {
                border: "3px dashed rgb(200, 195, 195)",
            },
            "&.isActive:focus": {
                "&:after": {
                    outline: "none",
                    border: `3px dashed ${globalVars.mainColors.primary}`,
                },
            },
        }),
    );

    const widget = css({
        position: "relative",
        userSelect: "none",
        outline: "none",

        // Special override for the Article Reactions widget, see note in ArticleReactionsWidgetPreview
        "& .articleReactionsModal": {
            display: "none",
        },
    });

    const widgetBorder = css({
        content: "''",
        position: "absolute",
        top: -10,
        bottom: -10,
        left: -10,
        right: -10,
        border: `3px dashed ${globalVars.mainColors.primary}`,
        zIndex: 1,
        borderRadius: 6,

        ".isFullWidth &": {
            top: 3,
            right: 8,
            left: 8,
            bottom: 3,
        },
    });

    const initialSectionForm = css({
        flex: "1 0 auto",
        paddingTop: 100,
    });

    const toolbarMenu = css({
        border: singleBorder(),
        "&& button": {
            marginLeft: 2,
            marginRight: 2,
        },
    });

    return {
        modal: css({
            display: "flex",
            flexDirection: "column",
            height: "100%",
        }),
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
