/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import {
    paddings,
    placeholderStyles,
    textInputSizing,
    colorOut,
    unit,
    absolutePosition,
    pointerEvents,
    margins,
    negative,
    sticky,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { calc, percent, px, viewHeight } from "csx";
import { vanillaHeaderVariables } from "@library/headers/vanillaHeaderStyles";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";

export const richEditorFormClasses = useThemeCache((legacyMode: boolean = false) => {
    const globalVars = globalVariables();
    const headerVars = vanillaHeaderVariables();
    const vars = richEditorVariables();
    const formElementVars = formElementsVariables();
    const style = styleFactory("richEditorForm");
    const overshoot = legacyMode ? 0 : vars.scrollContainer.overshoot;
    const root = style({
        height: viewHeight(100),
        maxHeight: viewHeight(100),
        overflow: "auto",
    });

    const textWrap = style("textWrap", {
        ...paddings({
            top: 0,
            bottom: 0,
            right: unit(globalVars.gutter.quarter),
            left: unit(globalVars.gutter.quarter),
        }),
    });

    const title = style("title", {
        $nest: {
            "&.inputText, &&": {
                ...textInputSizing(
                    vars.title.height,
                    vars.title.fontSize,
                    globalVars.gutter.half,
                    formElementVars.border.fullWidth,
                ),
                color: colorOut(formElementVars.colors.fg),
                backgroundColor: colorOut(formElementVars.colors.bg),
                position: "relative",
                fontWeight: globalVars.fonts.weights.semiBold,
                border: 0,
                borderRadius: 0,
                marginBottom: unit(globalVars.spacer.size),
                ...paddings({
                    left: 0,
                    right: 0,
                }),
            },
            "&:not(.focus-visible)": {
                outline: "none",
            },
            ...placeholderStyles({
                lineHeight: "inherit",
                padding: "inherit",
                color: colorOut(formElementVars.placeholder.color),
            }),
        },
    });

    const editor = style("editor", {
        borderTopLeftRadius: 0,
        borderTopRightRadius: 0,
        marginTop: unit(-formElementVars.border.width),
        display: "flex",
        flexGrow: 1,
        flexDirection: "column",
        width: percent(100),
        maxWidth: 672,
        ...paddings({ horizontal: 12 }),
        ...margins({
            horizontal: "auto",
        }),
        $nest: {
            "& .richEditor-text": {},
        },
    });

    const body = style("body", {
        display: "contents",
        flexDirection: "column",
        // alignItems: "center",
        // justifyContent: "flex-start",
        // flex: "1 0 auto",
        paddingTop: unit(globalVars.overlay.fullPageHeadingSpacer),
        marginBottom: px(12),
        overflow: "hidden",
    });

    const stickyBar = style("stickyBar", sticky(), {
        top: headerVars.sizing.height,
        zIndex: 1,
    });

    const inlineMenuItems = style("inlineMenuItems", {
        borderBottom: `${unit(formElementVars.border.width)} solid ${colorOut(formElementVars.border.color)}`,
    });

    const formContent = style("formContent", {
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
    });

    const scrollFrame = style("scrollFrame", {
        margin: "auto",
        height: "initial",
        minHeight: unit(vars.sizing.minHeight + vars.menuButton.size),
        position: "relative",
        backgroundColor: colorOut(vars.colors.bg),
        display: !legacyMode ? "flex" : undefined,
        flexDirection: !legacyMode ? "column" : undefined,
        flexGrow: !legacyMode ? 1 : undefined,
        padding: 0,
        width: legacyMode ? percent(100) : calc(`100% + ${unit(overshoot * 2)}`),
        marginLeft: legacyMode ? undefined : unit(-overshoot),
        paddingLeft: legacyMode ? undefined : unit(overshoot),
        paddingRight: legacyMode ? undefined : unit(overshoot),
        $nest: {
            "&.isMenuInset": {
                overflow: "initial",
                position: "relative",
            },
        },
    });

    const modernFrame = style("modernFrame", {
        position: "relative",
        ...paddings({
            top: globalVars.gutter.half,
            bottom: globalVars.gutter.size,
        }),
    });

    const bodyErrorMessage = style("bodyErrorMessage", {
        ...absolutePosition.topLeft(),
    });

    const titleErrorMessage = style("titleErrorMessage", {
        ...pointerEvents(),
        ...margins({
            top: unit(negative(globalVars.spacer.size)),
            bottom: globalVars.spacer.size,
        }),
    });

    const categoryErrorParagraph = style("categoryErrorParagraph", {
        ...margins({
            vertical: 8,
        }),
    });

    const titleErrorParagraph = style("titleErrorParagraph", {
        lineHeight: unit(globalVars.lineHeights.base * globalVars.fonts.size.large + 2),
    });

    return {
        root,
        textWrap,
        title,
        editor,
        scrollFrame,
        modernFrame,
        body,
        inlineMenuItems,
        formContent,
        bodyErrorMessage,
        titleErrorMessage,
        categoryErrorParagraph,
        titleErrorParagraph,
        stickyBar,
    };
});
