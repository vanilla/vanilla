/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { percent, px } from "csx";
import { unit } from "@library/styles/styleHelpers";

export const selectBoxClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("selectBox");

    const toggle = style("toggle", {
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
    });

    const buttonItem = style("buttonItem", {
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
    });

    const buttonIcon = style("buttonIcon", {
        marginLeft: px(6),
        marginRight: "auto",
    });

    const outdated = style("outdated", {
        marginLeft: "auto",
        lineHeight: "inherit",
        whiteSpace: "nowrap",
    });

    const dropDownContents = style("dropDownContents", {
        paddingTop: px(6),
        paddingBottom: px(6),
    });

    const checkContainer = style("checkContainer", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        color: globalVars.mainColors.primary.toString(),
        width: percent(100),
        height: px(18),
        flexBasis: px(18),
        marginLeft: "auto",
    });

    const spacer = style("spacer", {
        display: "block",
        width: px(18),
        height: px(18),
    });

    const itemLabel = style("itemLabel", {
        display: "block",
        flexGrow: 1,
    });

    const noTopPadding = style("noTopPadding", {
        paddingTop: unit(0),
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
        noTopPadding,
    };
});
