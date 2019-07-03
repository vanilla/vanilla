/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {IBorderStyles} from "@library/styles/styleHelpersBorders";
import {IFont} from "@library/styles/styleHelpersTypography";
import {ColorValues} from "@library/styles/styleHelpersColors";
import {TLength} from "typestyle/lib/types";

export interface IButtonType {
    name: string;
    colors?: {
        bg?: ColorValues;
        fg?: ColorValues;
    };
    borders?: IBorderStyles;
    sizing?: {
        minHeight?: TLength;
        minWidth?: TLength;
    };
    padding?: {
        top?: TLength;
        bottom?: TLength;
        side?: TLength;
    };
    fonts?: IFont;
    hover?: {
        fg?: ColorValues;
        colors?: {
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    focus?: {
        fg?: ColorValues;
        colors?: {
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    active?: {
        fg?: ColorValues;
        colors?: {
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    focusAccessible?: {
        fg?: ColorValues;
        colors?: {
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
}
