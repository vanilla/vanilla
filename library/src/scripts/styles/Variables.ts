/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    EMPTY_BACKGROUND,
    EMPTY_BORDER,
    EMPTY_FONTS,
    EMPTY_SPACING,
    EMPTY_STATE_COLORS,
    IBackground,
    IBorderStyles,
    IBoxOptions,
    IFont,
    IContentBoxes,
    ISpacing,
    IStateColors,
} from "@library/styles/cssUtilsTypes";
import { BorderType } from "@library/styles/styleHelpers";
import { DeepPartial } from "redux";

export class Variables {
    constructor() {
        throw new Error("Not to be instantiated");
    }

    static spacing = (vars: ISpacing): ISpacing => ({ ...EMPTY_SPACING, ...vars });

    static font = (vars: IFont): IFont => ({ ...EMPTY_FONTS, ...vars });

    static border = (vars: Partial<IBorderStyles>): Partial<IBorderStyles> => ({ ...EMPTY_BORDER, ...vars });

    static background = (vars: IBackground): IBackground => ({ ...EMPTY_BACKGROUND, ...vars });

    static clickable = (vars: IStateColors): IStateColors => ({ ...EMPTY_STATE_COLORS, ...vars });

    static box = (vars: Partial<IBoxOptions>): IBoxOptions => {
        return {
            borderType: BorderType.NONE,
            background: Variables.background(vars.background ?? {}),
            spacing: Variables.spacing(vars.spacing ?? {}),
            border: Variables.border(vars.border ?? {}),
            itemSpacing: vars.itemSpacing ? vars.itemSpacing : 0,
            itemSpacingOnAllItems: vars.itemSpacingOnAllItems ?? false,
            ...vars,
        };
    };

    static contentBoxes = (vars?: DeepPartial<IContentBoxes>): IContentBoxes => {
        return {
            depth1: Variables.box(vars?.depth1 ?? {}),
            depth2: Variables.box(vars?.depth2 ?? {}),
            depth3: Variables.box(vars?.depth3 ?? {}),
        };
    };
}
