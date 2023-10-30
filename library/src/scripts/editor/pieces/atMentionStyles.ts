/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssRule } from "@library/styles/styleShim";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { singleLineEllipsis, userSelect } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { calc, percent } from "csx";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { css } from "@emotion/css";

export const atMentionVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("atMention");

    const font = makeThemeVars(
        "font",
        Variables.font({
            ...globalVars.fontSizeAndWeightVars("large"),
        }),
    );

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

export const mentionListClasses = useThemeCache(() => {
    const vars = atMentionVariables();

    const listWrapper = css({
        position: "absolute",
        width: styleUnit(vars.sizing.width),
        transform: `translateY(${styleUnit(vars.positioning.offset)})`,
    });

    const list = css({
        display: "block",
        ...Mixins.padding(vars.user.padding),
        overflow: "auto",
        maxHeight: styleUnit(vars.sizing.maxHeight),

        "&.isHidden": {
            display: "none",
        },
    });

    return { listWrapper, list };
});

export const mentionClasses = useThemeCache(() => {
    const vars = atMentionVariables();

    const suggestion = css({
        ...singleLineEllipsis(),
        position: "relative",
        appearance: "none",
        border: 0,
        padding: 0,
        background: "none",
        width: percent(100),
        textAlign: "left",
        cursor: "pointer",
    });

    const user = css({
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

    const userName = css({
        display: "block",
        flexGrow: 1,
        overflow: "hidden",
        whiteSpace: "nowrap",
        textOverflow: "ellipsis",
        maxWidth: calc(`100% - ${styleUnit(vars.avatar.margin + vars.avatar.width)}`),
    });

    const photoWrap = css({
        marginRight: styleUnit(vars.avatar.margin),
    });

    const photo = css({
        width: styleUnit(vars.avatar.width),
        height: styleUnit(vars.avatar.width),
        display: "block",
    });

    const mark = css({
        padding: 0,
        fontWeight: vars.mark.weight,
    });

    return { suggestion, user, userName, photoWrap, photo, mark };
});

export const mentionListItemClasses = useThemeCache((active = false) => {
    const globalVars = globalVariables();

    const listItem = css({
        marginBottom: 0,
        ...(active && {
            backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
        }),
    });

    return { listItem };
});

export const atMentionCSS = useThemeCache(() => {
    const vars = atMentionVariables();
    // Don't make this class dynamic. a lot seems to depend on it
    cssRule(".atMention", {
        color: "inherit",
        fontWeight: vars.link.weight,
        userSelect: "all",
    });
});
