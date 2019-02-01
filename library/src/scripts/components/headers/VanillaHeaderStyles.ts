/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {style} from "typestyle";
import { color } from "csx";

export default function vanillaHeaderStyles(theme: any) {

    const variables {
        $vanillaHeader_fg                                   : $global-color_bg !default;
        $vanillaHeader_bg                                   : $global-color_primary !default;
        $vanillaHeader-spacer_width                         : 12px;
        $vanillaHeader-button_borderRadius                  : 3px;
        $vanillaHeader-button_size                          : 40px;
        $vanillaMenu-guest_spacer                           : 8px !default;
        $vanillaMenu-signIn_bg                              : getBgDependingOnContrastColor($vanillaHeader_fg, $global-color_primary, 10%) !default;
        $vanillaMenu-signIn_bg_hover                        : getBgDependingOnContrastColor($vanillaHeader_fg, $global-color_primary, 20%) !default;
        $vanillaMenu-register_bg                            : $global-color_bg !default;
        $vanillaMenu-register_bg_hover                      : rgba($global-color_bg, .9) !default;
    };

    const header = {
        height: "48px",
        "mobile.height": "44px",
        fg: "",
        bg: "",
        spacer: "",

    };

    const count = {
        height: "18px",
        fontSize: "18px",
        bg: "18px",
        fg: "18px",
    };

    const dropDownContents = {
        minWidth: "350px",
    };

    const button = {
        fontSize: "16px",
        borderRadius: ""
    };

    const endElements: {
        flexBasis: $vanillaHeader-button_size * 4
    };

    const compactSearch: {};

    const buttonContents: {};





    $vanillaHeader-endElements_flexBasis:
    $vanillaHeader-endElements_mobile_flexBasis: $vanillaHeader-button_size * 2;

    $vanillaHeader-compactSearch_maxWidth: 672px;

    $vanillaHeader-buttonContents_hover_bg: getBgDependingOnContrastColor($vanillaHeader_fg, $global-color_primary, 10%) !default;
    $vanillaHeader-buttonContents_active_bg: getBgDependingOnContrastColor($vanillaHeader_fg, $global-color_primary, 10%, true) !default;

}
