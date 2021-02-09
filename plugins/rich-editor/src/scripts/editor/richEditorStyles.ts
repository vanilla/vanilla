/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ColorsUtils } from "@library/styles/ColorsUtils";
import {
    absolutePosition,
    appearance,
    singleBorder,
    singleLineEllipsis,
    userSelect,
    pointerEvents,
    importantUnit,
} from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, important, percent, quote, translateY } from "csx";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { CSSObject } from "@emotion/css";
import { buttonResetMixin } from "@vanilla/library/src/scripts/forms/buttonMixins";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const richEditorClasses = useThemeCache((legacyMode: boolean) => {
    const globalVars = globalVariables();
    const style = styleFactory("richEditor");
    const vars = richEditorVariables();
    const formVars = formElementsVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style({
        position: "relative",
        display: "block",
        ...{
            "&.isDisabled": {
                ...{
                    "&, &.richEditor-button": {
                        cursor: important("progress"),
                    },
                },
            },
            ".richEditor-textWrap, .richEditor-frame": {
                display: "flex",
                flexDirection: "column",
                flexGrow: 1,
                position: "relative",
            },
            "& .ql-clipboard": {
                ...Mixins.absolute.srOnly(),
                position: "fixed", // Fixed https://github.com/quilljs/quill/issues/1374#issuecomment-415333651
            },
            ".richEditor-nextInput, .iconButton, .richEditor-button": {
                ...singleLineEllipsis(),
                ...appearance(),
                position: "relative",
                border: 0,
                padding: 0,
                background: "none",
                textAlign: "left",
            },
            ".Close-x": {
                display: "block",
                cursor: "pointer",
            },
            ".content-wrapper": {
                height: percent(100),
            },
            ".embedDialogue": {
                position: "relative",
            },
        },
    });

    const iconWrap = style("iconWrap", {
        ...pointerEvents(),
        content: quote(``),
        ...absolutePosition.middleOfParent(),
        width: styleUnit(vars.iconWrap.width),
        height: styleUnit(vars.iconWrap.width),
        ...Mixins.border({
            radius: 3,
            color: globalVars.elementaryColors.transparent,
        }),
    });

    const paragraphMenu = style("paragraphMenu", {
        position: "absolute",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        top: styleUnit(vars.pilcrow.offset),
        left: 0,
        marginLeft: styleUnit(-globalVars.gutter.quarter - (!legacyMode ? globalVars.gutter.size * 2 : 0)),
        transform: `translateX(-100%) translateY(-50%)`,
        height: styleUnit(vars.paragraphMenuHandle.size),
        width: styleUnit(vars.paragraphMenuHandle.size),
        animationName: vars.pilcrow.animation.name,
        animationDuration: vars.pilcrow.animation.duration,
        animationTimingFunction: vars.pilcrow.animation.timing,
        animationIterationCount: vars.pilcrow.animation.iterationCount,
        zIndex: 10,
        ...{
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
        width: styleUnit(vars.menuButton.size * 4),
        fontSize: styleUnit(globalVars.fonts.size.medium),
        overflow: "hidden",
        zIndex: 1,
    });

    const menuBarToggles = style("menuBarToggles", {
        position: "relative",
        display: "flex",
        justifyContent: "space-between",
        flexWrap: "nowrap",
        width: styleUnit(vars.menuButton.size * 4),
    });

    const paragraphMenuHandle = style("paragraphMenuHandle", {
        ...appearance(),
        ...userSelect(),
        background: "transparent",
        border: 0,
        display: "block",
        cursor: "pointer",
        width: styleUnit(formVars.sizing.height),
        height: styleUnit(formVars.sizing.height),
        padding: 0,
        maxWidth: styleUnit(formVars.sizing.height),
        minWidth: styleUnit(formVars.sizing.height),
        outline: 0,
        ...{
            "&:focus, &:hover": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
            [`&.isOpen .${iconWrap}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
        },
    });

    const paragraphMenuHandleMobile = style("paragraphMenuHandleMobile", {
        width: styleUnit(vars.menuButton.size),
        height: styleUnit(vars.menuButton.size),
        maxWidth: styleUnit(vars.menuButton.size),
        minWidth: styleUnit(vars.menuButton.size),
    });

    const text = style(
        "text",
        {
            position: "relative",
            whiteSpace: important("pre-wrap"),
            outline: 0,
            paddingBottom: 24, // So the user has room to click.

            ...{
                // When the editor is empty we should be displaying a placeholder.
                "&.ql-blank::before": {
                    content: `attr(placeholder)`,
                    display: "block",
                    color: ColorsUtils.colorOut(vars.text.placeholder.color),
                    position: "absolute",
                    top: vars.text.offset,
                    left: 0,
                    cursor: "text",
                },
            },
        },
        mediaQueries.oneColumnDown({
            fontSize: importantUnit(16), // for iOS
        }),
    );

    const menuItems = style("menuItems", {
        msOverflowStyle: "-ms-autohiding-scrollbar",
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "nowrap",
        listStyle: "none",
        padding: 0,
        margin: 0,
        zIndex: 1,
        overflow: "visible",
        ...{
            ".richEditor-menuItem": {
                display: "block",
                padding: 0,
                margin: 0,
                ...{
                    ".richEditor-button": {
                        display: "block",
                        width: styleUnit(vars.menuButton.size),
                        fontSize: styleUnit((vars.menuButton.size * 24) / 39),
                        lineHeight: styleUnit(vars.menuButton.size),
                        ...{
                            "&.emojiChar-🇺🇳": {
                                fontSize: styleUnit(10),
                            },
                        },
                    },
                    "&:first-child .richEditor-embedButton": {
                        borderBottomLeftRadius: styleUnit(globalVars.border.radius),
                    },
                    "&.isRightAligned": {
                        marginLeft: "auto",
                    },
                },
            },
        },
    });

    const button = style("button", {
        ...buttonResetMixin(),
        display: "block",
        ...userSelect(),
        ...appearance(),
        cursor: "pointer",
        width: styleUnit(vars.menuButton.size),
        height: styleUnit(vars.menuButton.size),
        border: 0,
        padding: 0,
        overflow: "hidden",
        position: "relative",
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        outline: 0,
        ...{
            "&:hover": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
            "&:focus": {
                color: ColorsUtils.colorOut(globalVars.mainColors.secondary),
            },
            "&:active": {
                color: ColorsUtils.colorOut(globalVars.mainColors.secondary),
            },
            [`&:hover .${iconWrap}`]: {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
            [`&.isOpen .${iconWrap}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
            [`&.focus-visible .${iconWrap}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
            "&.richEditor-formatButton, &.richEditor-embedButton": {
                height: styleUnit(vars.menuButton.size),
            },
            "&.emojiGroup": {
                display: "block",
                width: styleUnit(vars.menuButton.size),
                height: styleUnit(vars.menuButton.size),
                textAlign: "center",
            },
            "&:not(:disabled)": {
                cursor: "pointer",
            },
            [`&.isActive .${iconWrap}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
        },
    });

    const topLevelButtonActive = style("topLevelButtonActive", {
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        ...{
            [`.${iconWrap}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
        },
    });

    const menuItem = style("menuItem", {
        display: "block",
        padding: 0,
        margin: 0,
        overflow: "visible",
        ...{
            ".richEditor-button, &.richEditor-button": {
                width: styleUnit(vars.menuButton.size),
                height: styleUnit(vars.menuButton.size),
                maxWidth: styleUnit(vars.menuButton.size),
                fontSize: styleUnit((vars.menuButton.size * 24) / 39),
                lineHeight: styleUnit(vars.menuButton.size),
                ...{
                    "&.emojiChar-🇺🇳": {
                        fontSize: styleUnit(14),
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
        ...Mixins.padding({
            horizontal: legacyMode ? 0 : (vars.menuButton.size - globalVars.icon.sizes.small) / 2,
            vertical: vars.embedMenu.padding,
        }),
        background: legacyMode ? undefined : ColorsUtils.colorOut(vars.colors.bg),
    });

    const embedBarSeparator = style("embedBarSeparator", {
        height: styleUnit(globalVars.gutter.quarter),
        width: styleUnit(globalVars.gutter.quarter),
        borderRadius: styleUnit(globalVars.gutter.quarter),
        background: ColorsUtils.colorOut(globalVars.mainColors.fg),
        ...Mixins.margin({ horizontal: globalVars.gutter.half }),
    });

    const icon = style("icon", {
        display: "block",
        margin: "auto",
        height: styleUnit(globalVars.icon.sizes.default),
        width: styleUnit(globalVars.icon.sizes.default),
    });

    const legacyFrame = style("legacyFrame", {
        margin: "auto",
        height: "initial",
        minHeight: styleUnit(vars.sizing.minHeight + vars.menuButton.size),
        position: "relative",
        backgroundColor: ColorsUtils.colorOut(vars.colors.bg),
        padding: 0,
        ...{
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
        width: styleUnit(vars.menuButton.size),
        height: styleUnit(vars.menuButton.size),
        lineHeight: styleUnit(vars.menuButton.size),
        verticalAlign: "bottom",
        textAlign: "center",
        background: "transparent",
        cursor: "pointer",
        border: 0,
        outline: 0,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
    });

    const flyoutDescription = style("flyoutDescription", {
        marginBottom: ".5em",
    });

    const separator = style("separator", {
        borderTop: singleBorder(),
        marginBottom: styleUnit(8),
    });

    const position = style("position", {
        ...{
            "&&": {
                position: "absolute",
                left: calc(`50% - ${styleUnit(vars.spacing.paddingLeft / 2)}`),
            },
            "&.isUp": {
                bottom: calc(`50% + ${styleUnit(vars.spacing.paddingRight / 2 - formVars.border.width)}`),
            },
            "&.isDown": {
                top: calc(`50% + ${styleUnit(vars.spacing.paddingRight / 2 - formVars.border.width)}`),
            },
        },
    });

    const paragraphMenuPanel = style("paragraphMenuPanel", {});

    const emojiGroup = style("emojiGroup", {
        ...{
            [`&.isSelected .${iconWrap}`]: {
                backgroundColor: ColorsUtils.colorOut(vars.buttonContents.state.bg),
            },
        },
    });

    const flyoutOffset = style("flyoutOffset", {
        marginTop: styleUnit((vars.menuButton.size - vars.iconWrap.width) / -2 + 1),
    });

    const placeholder = style("placeholder", {
        ...{
            "&&&:before": {
                margin: 0,
            },
        },
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
        embedBarSeparator,
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
        placeholder,
    };
});
