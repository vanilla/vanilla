/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent, px } from "csx";
import { styleUnit } from "@library/styles/styleUnit";

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
        ...{
            "&.minimalStyles": {
                justifyContent: "center",
                ...{
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
        ...{
            "&[disabled]": {
                opacity: 1,
            },
        },
    });

    const buttonIcon = style("buttonIcon", {
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

    const offsetPadding = style("offsetPadding", {
        paddingTop: styleUnit(0),
        paddingBottom: styleUnit(0),
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
        offsetPadding,
    };
});
