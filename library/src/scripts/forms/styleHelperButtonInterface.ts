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

export interface ILegacyButtonPresetOptions {
    style?: ButtonPreset;
    bg?: ColorHelper | string;
    fg?: ColorHelper | string;
    fgState?: ColorHelper | string;
    bgState?: ColorHelper | string;
    borders?: ColorHelper | string;
    borderState?: ColorHelper | string;
}

export const EMPTY_LEGACY_BUTTON_PRESET: ILegacyButtonPresetOptions = {
    style: undefined,
    bg: undefined,
    fg: undefined,
    fgState: undefined,
    bgState: undefined,
    borders: undefined,
    borderState: undefined,
};
interface IButtonOptions {
    colors?: {
        fg?: ColorHelper | string;
        bg?: ColorHelper | string;
    };
    borders?: IBorderStyles;
    fonts?: IFont;
    useShadow?: boolean;
    opacity?: number;
}
export interface IButton extends IButtonOptions {
    name: ButtonTypes | string;
    preset?: ILegacyButtonPresetOptions;
    presetName?: ButtonPreset;
    sizing?: {
        minHeight?: TLength;
        minWidth?: TLength;
    };
    padding?: {
        top?: TLength;
        bottom?: TLength;
        horizontal?: TLength;
    };
    state?: IButtonOptions;
    hover?: IButtonOptions;
    focus?: IButtonOptions;
    active?: IButtonOptions;
    focusAccessible?: IButtonOptions;
    disabled?: IButtonOptions;
    skipDynamicPadding?: boolean;
    extraNested?: CSSObject; // special case CSS
}
