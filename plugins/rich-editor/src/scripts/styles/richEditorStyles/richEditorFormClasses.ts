/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import {
    absolutePosition,
    paddings,
    placeholderStyles,
    textInputSizing,
    toStringColor,
    unit,
} from "@library/styles/styleHelpers";
import styleFactory from "@library/styles/styleFactory";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { calc, percent } from "csx";
import { layoutVariables } from "@library/styles/layoutStyles";
import { memoize } from "lodash";

export const richEditorFormClasses = memoize((theme?: object) => {
    const globalVars = globalVariables(theme);
    const mediaQueries = layoutVariables(theme).mediaQueries();
    const vars = richEditorVariables(theme);
    const formElementVars = formElementsVariables(theme);
    const style = styleFactory("richEditorForm");

    const root = style({});

    const frame = style("frame", {
        width: calc(`100% + ${unit(globalVars.gutter.half)}`),
        marginLeft: unit(-globalVars.gutter.quarter),
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
        flexDirection: "column",
    });

    const scrollContainer = style("scrollContainer", {
        paddingTop: unit(globalVars.gutter.half),
    });

    const scrollFrame = style("scrollFrame", {
        ...absolutePosition.bottomLeft(),
        width: percent(100),
        height: calc(`100% - ${formElementVars.border.width + formElementVars.sizing.height}`),
    });

    const body = style("body", {
        paddingTop: unit(globalVars.overlay.fullPageHeadingSpacer),
        flexGrow: 1,
    });

    const inlineMenuItems = style("inlineMenuItems", {
        borderBottom: `${unit(formElementVars.border.width)} solid ${toStringColor(formElementVars.border.color)}`,
    });

    return {
        root,
        frame,
        textWrap,
        title,
        editor,
        scrollContainer,
        scrollFrame,
        body,
        inlineMenuItems,
    };
});
