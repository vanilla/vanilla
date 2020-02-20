/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { percent, px } from "csx";

export const dropdownSwitchButtonClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("buttonSwitch");

    const container = style("button", {
        display: "flex",
        lineHeight: unit(1.25),
        minHeight: unit(30),
        padding: unit(0),
        paddingBottom: unit(4),
        paddingLeft: unit(14),
        paddingRight: unit(14),
        paddingTop: unit(4),
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
