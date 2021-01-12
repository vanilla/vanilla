/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// This file is a WIP. It's currently only used for the image size, but it doesn't hurt anything to have it here.

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";

export const forumVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("forum", forcedVars);

    const modern = makeThemeVars("modern", {});
    const table = makeThemeVars("table", {});

    const lists = makeThemeVars("lists", {
        spacing: {
            padding: {
                top: 15,
                right: globalVars.gutter.half,
                bottom: 16,
                left: globalVars.gutter.half,
            },
            margin: {
                top: "initial",
                right: "initial",
                bottom: "initial",
                left: "initial",
            },
        },
        colors: {
            backgroundColor: "transparent",
            backgroundColorRead: "transparent",
        },
        text: {
            title: {
                colors: {
                    noState: "",
                    focus: "",
                    hover: "",
                    active: "",
                },
            },
            meta: {
                colors: {
                    noState: ColorsUtils.colorOut(globalVars.mainColors.fg),
                    focus: ColorsUtils.colorOut(globalVars.links.colors.focus),
                    keyboardFocus: ColorsUtils.colorOut(globalVars.links.colors.keyboardFocus),
                    hover: ColorsUtils.colorOut(globalVars.links.colors.hover),
                    active: ColorsUtils.colorOut(globalVars.links.colors.active),
                },
            },
        },
    });

    const discussions = makeThemeVars("discussions", {
        modern: {
            ...modern,
        },
        table: {
            ...table,
        },
    });

    const discussion = makeThemeVars("discussion", {});
    const categories = makeThemeVars("categories", {
        modern: {
            ...modern,
        },
        table: {
            ...table,
        },
        mixed: {},
    });

    const userPhoto = makeThemeVars("userPhoto", userPhotoVariables());

    const spacer = makeThemeVars("spacer", {
        ...globalVars.spacer,
    });

    const countBox = makeThemeVars("countBox", {
        width: 40,
        height: 40,
    });

    return {
        modern,
        table,
        lists,
        discussions,
        discussion,
        categories,
        userPhoto,
        countBox,
        spacer,
    };
});
