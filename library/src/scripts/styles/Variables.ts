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
    IFont,
    ISpacing,
    IStateColors,
} from "@library/styles/cssUtilsTypes";

export class Variables {
    constructor() {
        throw new Error("Not to be instantiated");
    }

    static spacing = (vars: ISpacing): ISpacing => ({ ...EMPTY_SPACING, ...vars });

    static font = (vars: IFont): IFont => ({ ...EMPTY_FONTS, ...vars });

    static border = (vars: Partial<IBorderStyles>): Partial<IBorderStyles> => ({ ...EMPTY_BORDER, ...vars });

    static background = (vars: IBackground): IBackground => ({ ...EMPTY_BACKGROUND, ...vars });

    static clickable = (vars: IStateColors): IStateColors => ({ ...EMPTY_STATE_COLORS, ...vars });
}
