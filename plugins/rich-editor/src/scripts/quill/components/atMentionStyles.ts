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
import { absolutePosition, margins, paddings, unit, userSelect } from "@library/styles/styleHelpers";
import { percent } from "csx";

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
        user,
        selected,
        sizing,
    };
});

export const atMentionCSS = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = atMentionVariables();
    cssRule(".atMentionList", {});
    cssRule(".atMentionList-suggestion", {});
    cssRule(".atMentionList-items", {
        $nest: {
            "&.atMentionList-items": {},
            "&.isHidden": {},
        },
    });
    cssRule(".atMentionList-item", {
        $nest: {
            "&.atMentionList-item": {},
            "&.isActive .atMentionList-suggestion": {},
        },
    );
    cssRule(".atMentionList-suggestion", {});
    cssRule(".atMentionList-user, .atMentionList-user", {});
    cssRule(".atMentionList-photoWrap", {});
    cssRule(".atMentionList-photo", {});
    cssRule(".atMentionList-userName", {});
    cssRule(".atMentionList-mark", {});
    cssRule(".atMentionList-photo", {});
    cssRule(".atMention", {});
});
