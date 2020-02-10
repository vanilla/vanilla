/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { cssRule } from "typestyle";
import { colorOut } from "@library/styles/styleHelpersColors";
import { borders } from "@library/styles/styleHelpersBorders";
import {
    absolutePosition,
    margins,
    paddings,
    singleLineEllipsis,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { calc, percent } from "csx";

export const atMentionVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("atMention");

    const font = makeThemeVars("font", {
        size: globalVars.fonts.size.large,
    });

    const avatar = makeThemeVars("avatar", {
        width: 30,
        margin: 10,
    });

    const positioning = makeThemeVars("positioning", {
        offset: 6,
    });

    // As in the <mark/> tag
    const mark = makeThemeVars("mark", {
        weight: globalVars.fonts.weights.semiBold,
    });

    const link = makeThemeVars("link", {
        weight: globalVars.fonts.weights.semiBold,
    });

    const user = makeThemeVars("user", {
        padding: {
            vertical: 3,
            horizontal: 6,
        },
    });

    const selected = makeThemeVars("selected", {
        bg: globalVars.mainColors.bg,
    });

    const sizing = makeThemeVars("sizing", {
        width: 200,
        maxHeight: (avatar.width + user.padding.vertical * 2) * 7.5,
    });

    return {
        font,
        avatar,
        mark,
        link,
        positioning,
        user,
        selected,
        sizing,
    };
});

export const atMentionCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = atMentionVariables();
    cssRule(".atMentionList", {
        position: "absolute",
        width: unit(vars.sizing.width),
        transform: `translateY(${unit(vars.positioning.offset)})`,
    });

    cssRule(".atMentionList-suggestion", {
        ...singleLineEllipsis(),
        position: "relative",
        appearance: "none",
        border: 0,
        padding: 0,
        background: "none",
        width: percent(100),
        textAlign: "left",
    });

    cssRule(".atMentionList-items", {
        $nest: {
            "&.atMentionList-items": {
                display: "block",
                ...paddings(vars.user.padding),
                overflow: "auto",
                maxHeight: unit(vars.sizing.maxHeight),
            },
            "&.isHidden": {
                display: "none",
            },
        },
    });

    cssRule(".atMentionList-item", {
        $nest: {
            "&.atMentionList-item": {
                marginBottom: 0,
            },
            "&.isActive .atMentionList-suggestion": {
                backgroundColor: colorOut(globalVars.states.hover.highlight),
            },
        },
    });

    cssRule(".atMentionList-suggestion", {
        width: percent(100),
        cursor: "pointer",
    });

    cssRule(".atMentionList-user, .atMentionList-user", {
        display: "flex",
        alignItems: "center",
        width: percent(100),
        boxSizing: "border-box",
        overflow: "hidden",
        lineHeight: unit(vars.avatar.width),
        ...paddings({
            vertical: vars.user.padding.vertical,
            horizontal: vars.user.padding.horizontal,
        }),
    });

    cssRule(".atMentionList-photoWrap", {
        marginRight: unit(vars.avatar.margin),
    });

    cssRule(".atMentionList-photo", {
        width: unit(vars.avatar.width),
        height: unit(vars.avatar.width),
    });

    cssRule(".atMentionList-userName", {
        display: "block",
        flexGrow: 1,
        overflow: "hidden",
        whiteSpace: "nowrap",
        textOverflow: "ellipsis",
        maxWidth: calc(`100% - ${unit(vars.avatar.margin + vars.avatar.width)}`),
    });

    cssRule(".atMentionList-mark", {
        padding: 0,
        fontWeight: vars.mark.weight,
    });

    cssRule(".atMentionList-photo", {
        display: "block",
    });

    cssRule(".atMention", {
        color: "inherit",
        fontWeight: vars.link.weight,
        userSelect: "all",
    });
});
