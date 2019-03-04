/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { paddings, placeholderStyles, textInputSizing, toStringColor, unit } from "@library/styles/styleHelpers";
import { styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { calc, percent, px, viewHeight } from "csx";
import { memoize } from "lodash";
import { vanillaHeaderVariables } from "@library/styles/vanillaHeaderStyles";

export const richEditorFormClasses = memoize((theme?: object, legacyMode: boolean = false) => {
    const globalVars = globalVariables();
    const headerVars = vanillaHeaderVariables();
    const vars = richEditorVariables();
    const formElementVars = formElementsVariables();
    const style = styleFactory("richEditorForm");
    const overshoot = legacyMode ? 0 : vars.scrollContainer.overshoot;

    const root = style({});

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
                color: toStringColor(formElementVars.colors.fg),
                position: "relative",
                fontWeight: globalVars.fonts.weights.semiBold,
                border: 0,
                borderRadius: 0,
                marginBottom: unit(globalVars.spacer),
                ...paddings({
                    left: 0,
                    right: 0,
                }),
                $nest: {
                    "&:active, &:focus, &.focus-visible": {
                        boxShadow: "none",
                    },
                },
            },
            ...placeholderStyles({
                lineHeight: "inherit",
                padding: "inherit",
                color: toStringColor(formElementVars.colors.placeholder),
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
    });

    const body = style("body", {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "flex-start",
        flexGrow: 1,
        maxHeight: calc(`100vh - ${unit(headerVars.sizing.height)}`),
        paddingTop: unit(globalVars.overlay.fullPageHeadingSpacer),
        marginBottom: px(12),
        overflow: "hidden",
    });

    const inlineMenuItems = style("inlineMenuItems", {
        borderBottom: `${unit(formElementVars.border.width)} solid ${toStringColor(formElementVars.border.color)}`,
    });

    const formContent = style("formContent", {
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
    });

    const scrollFrame = style("scrollFrame", {
        margin: "auto",
        minHeight: unit(vars.sizing.minHeight + vars.menuButton.size),
        position: "relative",
        backgroundColor: toStringColor(vars.colors.bg),
        display: !legacyMode ? "flex" : undefined,
        flexDirection: !legacyMode ? "column" : undefined,
        flexGrow: !legacyMode ? 1 : undefined,
        padding: 0,
        overflow: !legacyMode ? "auto" : undefined,
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

    const scrollContainer = style("scrollContainer", {
        position: "relative",
        height: "auto",
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
        ...paddings({
            top: globalVars.gutter.half,
            bottom: globalVars.gutter.size,
        }),
    });

    return {
        root,
        textWrap,
        title,
        editor,
        scrollFrame,
        scrollContainer,
        body,
        inlineMenuItems,
        formContent,
    };
});
