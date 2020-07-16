/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IBorderStyles } from "@library/styles/styleHelpersBorders";
import { IFont } from "@library/styles/styleHelpersTypography";
import { ColorValues } from "@library/styles/styleHelpersColors";
import { NestedCSSSelectors, TLength } from "typestyle/lib/types";
import { ButtonPreset } from "@library/forms/buttonStyles";
import { ButtonTypes } from "@library/forms/buttonTypes";

export interface IButtonType {
    name: ButtonTypes | string;
    preset?: {
        style: ButtonPreset;
    };
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
        horizontal?: TLength;
    };
    fonts?: IFont;
    state?: {
        colors?: {
            fg?: ColorValues;
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    hover?: {
        colors?: {
            fg?: ColorValues;
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    focus?: {
        colors?: {
            fg?: ColorValues;
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    active?: {
        colors?: {
            fg?: ColorValues;
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    focusAccessible?: {
        colors?: {
            fg?: ColorValues;
            bg?: ColorValues;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    skipDynamicPadding?: boolean;
    extraNested?: NestedCSSSelectors; // special case CSS
}
