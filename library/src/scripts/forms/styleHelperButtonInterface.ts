/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IBorderStyles, IFont } from "@library/styles/cssUtilsTypes";
import { CSSObject } from "@emotion/css";
import { TLength } from "@library/styles/styleShim";
import { ButtonPreset } from "@library/forms/ButtonPreset";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ColorHelper } from "csx";

export interface IButtonType {
    name: ButtonTypes | string;
    preset?: {
        style: ButtonPreset;
    };
    colors?: {
        bg?: ColorHelper | string;
        fg?: ColorHelper | string;
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
            fg?: ColorHelper | string;
            bg?: ColorHelper | string;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    hover?: {
        colors?: {
            fg?: ColorHelper | string;
            bg?: ColorHelper | string;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    focus?: {
        colors?: {
            fg?: ColorHelper | string;
            bg?: ColorHelper | string;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    active?: {
        colors?: {
            fg?: ColorHelper | string;
            bg?: ColorHelper | string;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    focusAccessible?: {
        colors?: {
            fg?: ColorHelper | string;
            bg?: ColorHelper | string;
        };
        borders?: IBorderStyles;
        fonts?: IFont;
    };
    skipDynamicPadding?: boolean;
    extraNested?: CSSObject; // special case CSS
}
