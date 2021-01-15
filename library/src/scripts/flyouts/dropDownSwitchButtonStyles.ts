/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent, px } from "csx";

export const dropdownSwitchButtonClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("buttonSwitch");

    const container = style("button", {
        display: "flex",
        lineHeight: styleUnit(1.25),
        minHeight: styleUnit(30),
        padding: styleUnit(0),
        paddingBottom: styleUnit(4),
        paddingLeft: styleUnit(14),
        paddingRight: styleUnit(14),
        paddingTop: styleUnit(4),
        textAlign: "left",
        textDecoration: "none",
        userSelect: "none",
        width: percent(100),
    });

    const itemLabel = style("checkbox", {
        flexGrow: 1,
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

    return {
        container,
        itemLabel,
        checkContainer,
    };
});
