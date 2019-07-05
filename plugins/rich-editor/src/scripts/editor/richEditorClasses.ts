/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import {
    absolutePosition,
    appearance,
    colorOut,
    singleBorder,
    singleLineEllipsis,
    srOnly,
    unit,
    userSelect,
    sticky,
    pointerEvents,
    allButtonStates,
    borders,
    negative,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { calc, important, percent, quote, translateY } from "csx";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { NestedCSSProperties } from "typestyle/lib/types";

export const richEditorClasses = useThemeCache((legacyMode: boolean, mobile?: boolean) => {
    const globalVars = globalVariables();
    const style = styleFactory("richEditor");
    const vars = richEditorVariables();
    const formVars = formElementsVariables();

    const root = style({
        position: "relative",
        display: "block",
        $nest: {
            "&.isDisabled": {
                $nest: {
                    "&, &.richEditor-button": {
                        cursor: important("progress"),
                    },
                },
            },
            "& .richEditor-text, & .richEditor-textWrap, & .richEditor-frame": {
                display: "flex",
                flexDirection: "column",
                flexGrow: 1,
                position: "relative",
            },
            "& .ql-clipboard": {
                ...srOnly(),
                position: "fixed", // Fixed https://github.com/quilljs/quill/issues/1374#issuecomment-415333651
            },
            "& .richEditor-nextInput, .iconButton, .richEditor-button": {
                ...singleLineEllipsis(),
                ...appearance(),
                position: "relative",
                border: 0,
                padding: 0,
                background: "none",
                textAlign: "left",
            },
            "& .Close-x": {
                display: "block",
                cursor: "pointer",
            },
            "& .content-wrapper": {
                height: percent(100),
            },
            "& .embedDialogue": {
                position: "relative",
            },
        },
    });

    const iconWrap = style("iconWrap", {
        ...pointerEvents(),
        content: quote(``),
        ...absolutePosition.middleOfParent(),
        width: unit(vars.iconWrap.width),
        height: unit(vars.iconWrap.height),
        ...borders({
            radius: 3,
            color: "transparent",
        }),
    } as NestedCSSProperties);

    const paragraphMenu = style("paragraphMenu", {
        position: "absolute",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        top: unit(vars.pilcrow.offset),
        left: 0,
        marginLeft: unit(-globalVars.gutter.quarter + (!legacyMode ? -(globalVars.gutter.size + 6) : 0)),
        transform: `translateX(-100%) translateY(-50%)`,
        height: unit(vars.paragraphMenuHandle.size),
        width: unit(globalVars.icon.sizes.default),
        animationName: vars.pilcrow.animation.name,
        animationDuration: vars.pilcrow.animation.duration,
        animationTimingFunction: vars.pilcrow.animation.timing,
        animationIterationCount: vars.pilcrow.animation.iterationCount,
        zIndex: 1,
        $nest: {
            ".richEditor-button&.isActive:hover": {
                cursor: "default",
            },
            "&.isMenuInset": {
                transform: translateY("-50%"),
            },
        },
    });

    const paragraphMenuMobile = style("paragraphMenu-mobile", {
        position: "relative",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        top: important(0),
    });

    const menuBar = style("menuBar", {
        position: "relative",
        width: unit(vars.menuButton.size * 4),
        overflow: "hidden",
    });

    const menuBarToggles = style("menuBarToggles", {
        position: "relative",
        display: "flex",
        justifyContent: "space-between",
        flexWrap: "nowrap",
        width: unit(vars.menuButton.size * 4),
    });

    const paragraphMenuHandle = style("paragraphMenuHandle", {
        ...appearance(),
        ...userSelect(),
        background: "transparent",
        border: 0,
        display: "block",
        cursor: "pointer",
        width: unit(formVars.sizing.height),
        height: unit(formVars.sizing.height),
        padding: 0,
        maxWidth: unit(formVars.sizing.height),
        minWidth: unit(formVars.sizing.height),
        outline: 0,
        $nest: {
            "&:focus, &:hover": {
                color: colorOut(globalVars.mainColors.primary),
            },
            [`&.isOpen .${iconWrap}`]: {
                backgroundColor: colorOut(vars.buttonContents.state.bg),
            },
        },
    });

    const paragraphMenuHandleMobile = style("paragraphMenuHandleMobile", {
        width: unit(vars.menuButton.size),
        height: unit(vars.menuButton.size),
        maxWidth: unit(vars.menuButton.size),
        minWidth: unit(vars.menuButton.size),
    });

    const text = style("text", {
        position: "relative",
        whiteSpace: important("pre-wrap"),
        outline: 0,
        $nest: {
            // When the editor is empty we should be displaying a placeholder.
            "&.ql-blank::before": {
                content: `attr(placeholder)`,
                display: "block",
                color: colorOut(vars.text.placeholder.color),
                position: "absolute",
                top: vars.text.offset,
                left: 0,
                cursor: "text",
            },
        },
    });

    const menuItems = style("menuItems", {
        "-ms-overflow-style": "-ms-autohiding-scrollbar",
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "nowrap",
        listStyle: "none",
        padding: 0,
        margin: 0,
        zIndex: 1,
        overflow: "visible",
        $nest: {
            ".richEditor-menuItem": {
                display: "block",
                padding: 0,
                margin: 0,
                $nest: {
                    ".richEditor-button, &.richEditor-button": {
                        width: unit(vars.menuButton.size),
                        fontSize: unit((vars.menuButton.size * 24) / 39),
                        lineHeight: unit(vars.menuButton.size),
                        $nest: {
                            "&.emojiChar-🇺🇳": {
                                fontSize: unit(10),
                            },
                        },
                    },
                    "&:first-child .richEditor-embedButton": {
                        borderBottomLeftRadius: unit(globalVars.border.radius),
                    },
                    "&.isRightAligned": {
                        marginLeft: "auto",
                    },
                },
            },
        },
    });

    const button = style("button", {
        display: "block",
        ...userSelect(),
        ...appearance(),
        cursor: "pointer",
        width: unit(vars.menuButton.size),
        height: unit(vars.menuButton.size),
        border: 0,
        padding: 0,
        overflow: "hidden",
        position: "relative",
        color: colorOut(globalVars.mainColors.fg),
        outline: 0,
        $nest: {
            "&:hover": {
                color: colorOut(globalVars.mainColors.primary),
            },
            "&:focus": {
                color: colorOut(globalVars.mainColors.secondary),
            },
            "&:active": {
                color: colorOut(globalVars.mainColors.secondary),
            },
            [`&.isOpen .${iconWrap}`]: {
                backgroundColor: colorOut(vars.buttonContents.state.bg),
            },
            [`&.focus-visible .${iconWrap}`]: {
                backgroundColor: colorOut(vars.buttonContents.state.bg),
            },
            "&.richEditor-formatButton, &.richEditor-embedButton": {
                height: unit(vars.menuButton.size),
            },
            "&.emojiGroup": {
                display: "block",
                width: unit(vars.menuButton.size),
                height: unit(vars.menuButton.size),
                textAlign: "center",
            },
            "&:not(:disabled)": {
                cursor: "pointer",
            },
            [`&.isActive .${iconWrap}`]: {
                backgroundColor: colorOut(vars.buttonContents.state.bg),
            },
        },
    });

    const topLevelButtonActive = style("topLevelButtonActive", {
        color: colorOut(globalVars.mainColors.primary),
        $nest: {
            [`& .${iconWrap}`]: {
                backgroundColor: colorOut(vars.buttonContents.state.bg),
            },
        },
    });

    const menuItem = style("menuItem", {
        display: "block",
        padding: 0,
        margin: 0,
        overflow: "visible",
        $nest: {
            "& .richEditor-button, &.richEditor-button": {
                width: unit(vars.menuButton.size),
                height: unit(vars.menuButton.size),
                maxWidth: unit(vars.menuButton.size),
                fontSize: unit((vars.menuButton.size * 24) / 39),
                lineHeight: unit(vars.menuButton.size),
                $nest: {
                    "&.emojiChar-🇺🇳": {
                        fontSize: unit(14),
                    },
                },
            },
            "&.isRightAligned": {
                marginLeft: "auto",
            },
        },
    });

    const upload = style("upload", {
        display: important("none"),
    });

    const embedBar = style("embedBar", {
        display: "block",
        width: percent(100),
        padding: unit(vars.embedMenu.padding),
        background: legacyMode ? undefined : colorOut(vars.colors.bg),
    });

    const icon = style("icon", {
        display: "block",
        margin: "auto",
        height: unit(globalVars.icon.sizes.default),
        width: unit(globalVars.icon.sizes.default),
    });

    const legacyFrame = style("legacyFrame", {
        margin: "auto",
        height: "initial",
        minHeight: unit(vars.sizing.minHeight + vars.menuButton.size),
        position: "relative",
        backgroundColor: colorOut(vars.colors.bg),
        padding: 0,
        $nest: {
            "&.isMenuInset": {
                overflow: "initial",
                position: "relative",
            },
        },
    });

    const close = style("close", {
        ...absolutePosition.middleRightOfParent(),
        ...userSelect(),
        ...appearance(),
        width: unit(vars.menuButton.size),
        height: unit(vars.menuButton.size),
        lineHeight: unit(vars.menuButton.size),
        verticalAlign: "bottom",
        textAlign: "center",
        background: "transparent",
        cursor: "pointer",
        border: 0,
        outline: 0,
    });

    const flyoutDescription = style("flyoutDescription", {
        marginBottom: ".5em",
    });

    const separator = style("separator", {
        borderTop: singleBorder(),
        marginBottom: unit(8),
    });

    const position = style("position", {
        position: "absolute",
        left: calc(`50% - ${unit(vars.spacing.paddingLeft / 2)}`),
        $nest: {
            "&.isUp": {
                bottom: calc(`50% + ${unit(vars.spacing.paddingRight / 2 - formVars.border.width)}`),
            },
            "&.isDown": {
                top: calc(`50% + ${unit(vars.spacing.paddingRight / 2 - formVars.border.width)}`),
            },
        },
    });

    const paragraphMenuPanel = style("paragraphMenuPanel", {});

    const emojiGroup = style("emojiGroup", {
        $nest: {
            [`&.isSelected .${iconWrap}`]: {
                backgroundColor: colorOut(vars.buttonContents.state.bg),
            },
        },
    });

    const flyoutOffset = style("flyoutOffset", {
        marginTop: unit((vars.menuButton.size - vars.iconWrap.width) / -2 + 1),
    });

    return {
        root,
        menuBar,
        menuBarToggles,
        paragraphMenuHandle,
        paragraphMenuHandleMobile,
        text,
        menuItems,
        upload,
        embedBar,
        menuItem,
        button,
        topLevelButtonActive,
        icon,
        close,
        flyoutDescription,
        paragraphMenu,
        paragraphMenuMobile,
        separator,
        position,
        legacyFrame,
        paragraphMenuPanel,
        iconWrap,
        flyoutOffset,
        emojiGroup,
    };
});
