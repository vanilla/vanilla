/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import { percent, px } from "csx";

export function selectBoxClasses(theme?: object) {
    const globalVars = globalVariables(theme);
    const debug = debugHelper("selectBox");

    const toggle = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        textAlign: "left",
        maxWidth: percent(100),
        border: 0,
        $nest: {
            "&.minimalStyles": {
                justifyContent: "center",
                $nest: {
                    ".selectBox-buttonIcon": {
                        marginRight: 0,
                    },
                },
            },
        },
        ...debug.name("toggle"),
    });

    const buttonItem = style({
        display: "flex",
        overflow: "hidden",
        alignItems: "center",
        justifyContent: "flex-start",
        textAlign: "left",
        maxWidth: percent(100),
        lineHeight: globalVars.lineHeights.condensed,
        paddingLeft: px(13.5),
        $nest: {
            "&[disabled]": {
                opacity: 1,
            },
        },
        ...debug.name("buttonItem"),
    });

    const buttonIcon = style({
        marginLeft: px(6),
        marginRight: "auto",
        ...debug.name("buttonIcon"),
    });

    const outdated = style({
        marginLeft: "auto",
        lineHeight: "inherit",
        whiteSpace: "nowrap",
        ...debug.name("outdated"),
    });

    const dropDownContents = style({
        paddingTop: px(6),
        paddingBottom: px(6),
        ...debug.name("dropDownContents"),
    });

    const checkContainer = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        color: globalVars.mainColors.primary.toString(),
        width: px(18),
        height: px(18),
        flexBasis: px(18),
        marginRight: px(9),
        ...debug.name("checkContainer"),
    });

    const spacer = style({
        display: "block",
        width: px(18),
        height: px(18),
        ...debug.name("spacer"),
    });

    const itemLabel = style({
        width: percent(100),
        ...debug.name("itemLabel"),
    });

    return {
        toggle,
        buttonItem,
        buttonIcon,
        outdated,
        dropDownContents,
        checkContainer,
        spacer,
        itemLabel,
    };
}
