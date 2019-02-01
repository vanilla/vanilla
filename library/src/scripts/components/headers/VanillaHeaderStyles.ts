/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { style } from "typestyle";
import { color, px } from "csx";
import { globals } from "@library/styles/globals";
import { getColorDependantOnLightness } from "@library/styles/styleHelpers";
import { getColorDependantOnLightness } from "@library/styles/styleHelpers";

export default function vanillaHeaderStyles(theme: any) {
    const globalVars = globals();

    // const header = {
    //     fg: "",
    //     bg: "",
    // };
    //
    // const headerSpacer = {
    //     width: px(12),
    // };

    //
    // const variables {
    //     $vanillaHeader_fg                                   : $global-color_bg !default;
    //     $vanillaHeader_bg                                   : $global-color_primary !default;
    //     $vanillaHeader-spacer_width                         : 12px;
    //     $vanillaMenu-guest_spacer                           : 8px !default;
    //     $vanillaMenu-signIn_bg                              : getBgDependingOnContrastColor($vanillaHeader_fg, $global-color_primary, 10%) !default;
    //     $vanillaMenu-signIn_bg_hover                        : getBgDependingOnContrastColor($vanillaHeader_fg, $global-color_primary, 20%) !default;
    //     $vanillaMenu-register_bg                            : $global-color_bg !default;
    //     $vanillaMenu-register_bg_hover                      : rgba($global-color_bg, .9) !default;
    // };

    // const header = {
    //     height: "48px",
    //     "mobile.height": "44px",
    //     spacer: "",
    // };
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

    //     $vanillaHeader-endElements_flexBasis:
    //     $vanillaHeader-endElements_mobile_flexBasis: $vanillaHeader-button_size * 2;
    //
    //     $vanillaHeader-compactSearch_maxWidth: 672px;
    //
    //     $vanillaHeader-buttonContents_hover_bg: getBgDependingOnContrastColor($vanillaHeader_fg, $global-color_primary, 10%) !default;
    //     $vanillaHeader-buttonContents_active_bg: getBgDependingOnContrastColor($vanillaHeader_fg, $global-color_primary, 10%, true) !default;
    //
    // }

    return { button, count, dropDownContents, endElements, compactSearch, buttonContents };
}
