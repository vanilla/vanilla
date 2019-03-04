/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { paddings, placeholderStyles, textInputSizing, toStringColor, unit } from "@library/styles/styleHelpers";
import styleFactory from "@library/styles/styleFactory";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { calc, percent, px, viewHeight } from "csx";
import { memoize } from "lodash";
import { vanillaHeaderVariables } from "@library/styles/vanillaHeaderStyles";

export const richEditorFormClasses = memoize((theme?: object) => {
    const globalVars = globalVariables(theme);
    const headerVars = vanillaHeaderVariables(theme);
    const vars = richEditorVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const style = styleFactory("richEditorForm");

    const root = style({});

    const frame = style("frame", {
        width: percent(100),
        margin: "auto",
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
        position: "relative",
        backgroundColor: toStringColor(vars.colors.bg),
        flexGrow: 1,
        padding: 0,
        overflow: "auto",
        minHeight: percent(100),
        width: calc(`100% + ${unit(vars.scrollContainer.overshoot * 2)}`),
        marginLeft: unit(-vars.scrollContainer.overshoot),
        paddingLeft: unit(vars.scrollContainer.overshoot),
        paddingRight: unit(vars.scrollContainer.overshoot),
        $nest: {
            "&.isMenuInset": {
                overflow: "initial",
                position: "relative",
            },
        },
    });

    //marginTop: unit(globalVars.overlay.fullPageHeadingSpacer),

    const scrollContainer = style("scrollContainer", {
        position: "relative",
        display: "block",
        height: "auto",
        ...paddings({
            top: globalVars.gutter.half,
            bottom: globalVars.gutter.size,
        }),
    });

    return {
        root,
        frame,
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
