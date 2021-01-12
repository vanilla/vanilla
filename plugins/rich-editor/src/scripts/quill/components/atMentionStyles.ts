/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IButtonType } from "@library/forms/styleHelperButtonInterface";
import { cssRule } from "@library/styles/styleShim";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { absolutePosition, singleLineEllipsis, userSelect } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { calc, percent } from "csx";
import { Mixins } from "@library/styles/Mixins";

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
        width: styleUnit(vars.sizing.width),
        transform: `translateY(${styleUnit(vars.positioning.offset)})`,
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
        ...{
            "&.atMentionList-items": {
                display: "block",
                ...Mixins.padding(vars.user.padding),
                overflow: "auto",
                maxHeight: styleUnit(vars.sizing.maxHeight),
            },
            "&.isHidden": {
                display: "none",
            },
        },
    });

    cssRule(".atMentionList-item", {
        ...{
            "&.atMentionList-item": {
                marginBottom: 0,
            },
            "&.isActive .atMentionList-suggestion": {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
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
        lineHeight: styleUnit(vars.avatar.width),
        ...Mixins.padding({
            vertical: vars.user.padding.vertical,
            horizontal: vars.user.padding.horizontal,
        }),
    });

    cssRule(".atMentionList-photoWrap", {
        marginRight: styleUnit(vars.avatar.margin),
    });

    cssRule(".atMentionList-photo", {
        width: styleUnit(vars.avatar.width),
        height: styleUnit(vars.avatar.width),
    });

    cssRule(".atMentionList-userName", {
        display: "block",
        flexGrow: 1,
        overflow: "hidden",
        whiteSpace: "nowrap",
        textOverflow: "ellipsis",
        maxWidth: calc(`100% - ${styleUnit(vars.avatar.margin + vars.avatar.width)}`),
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
