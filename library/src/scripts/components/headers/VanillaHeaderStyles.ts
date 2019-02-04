/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent, px } from "csx";
import { globals } from "@library/styles/globals";
import { getColorDependantOnLightness } from "@library/styles/styleHelpers";
import { layoutStyles } from "@library/styles/layoutStyles";
import { style } from "typestyle";

export function vanillaHeaderVariables() {
    const globalVars = globals();

    const sizing = {
        height: px(48),
        spacer: px(12),
        mobile: {
            height: px(44),
        },
    };

    const colors = {
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.primary,
    };

    const guest = {
        spacer: px(8),
    };

    const buttonSize = 40;
    const button = {
        borderRadius: 3,
        size: px(buttonSize),
        mobile: {
            fontSize: 16,
        },
    };

    const count = {
        size: 18,
        fontSize: 10,
        fg: globalVars.mainColors.bg,
        bg: globalVars.mainColors.primary,
    };

    const dropDownContents = {
        minWidth: px(350),
    };

    const endElements = {
        flexBasis: px(buttonSize * 4),
        mobile: {
            flexBasis: px(buttonSize * 2),
        },
    };

    const compactSearch = {
        maxWidth: px(672),
    };

    const buttonContents = {
        hover: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 10),
        active: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 10, true),
    };

    const signIn = {
        bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 10),
        hover: {
            bg: getColorDependantOnLightness(globalVars.mainColors.fg, globalVars.mainColors.primary, 20),
        },
    };
    const resister = {
        bg: globalVars.mainColors.bg,
        hover: {
            bg: globalVars.mainColors.bg.fade(0.9),
        },
    };

    return {
        sizing,
        colors,
        signIn,
        resister,
        guest,
        button,
        count,
        dropDownContents,
        endElements,
        compactSearch,
        buttonContents,
    };
}

export default function vanillaHeaderClasses() {
    const headerStyles = vanillaHeaderVariables();
    const headerColors = headerStyles.colors;
    const mediaQueries = layoutStyles().mediaQueries();

    const root = style(
        {
            backgroundColor: headerColors.bg.toString(),
            color: headerColors.fg.toString(),
            $nest: {
                "&isFixed": {
                    position: "fixed",
                    top: 0,
                    left: 0,
                    right: 0,
                    zIndex: 1,
                },
            },
        },
        mediaQueries.oneColumn({
            height: headerStyles.sizing.height.toString(),
        }),
    );

    const spacer = style({ height: headerStyles.sizing.height });

    const bar = style(
        {
            display: "flex",
            justifyContent: "space-between",
            flexWrap: "nowrap",
            alignItems: "center",
            height: headerStyles.sizing.height,
            width: percent(100),
            $nest: {
                "&.isHome": {
                    justifyContent: "space-between",
                },
            },
        },
        mediaQueries.oneColumn({ height: headerStyles.sizing.mobile.height }),
    );

    return { root, spacer, bar };
}
