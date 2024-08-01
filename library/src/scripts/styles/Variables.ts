/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { buttonGlobalVariables } from "@library/forms/Button.variables";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IButton } from "@library/forms/styleHelperButtonInterface";
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
    IPartialBoxOptions,
} from "@library/styles/cssUtilsTypes";
import { BorderType } from "@library/styles/styleHelpers";
import { DeepPartial } from "redux";

export class Variables {
    constructor() {
        throw new Error("Not to be instantiated");
    }

    static button = (vars: IButton): IButton => {
        const buttonGlobalVars = buttonGlobalVariables();

        const colors: IButton["colors"] = {
            ...{
                fg: buttonGlobalVars.colors.fg,
                bg: buttonGlobalVars.colors.bg,
            },
            ...vars.colors,
        };

        const fonts: IButton["fonts"] = Variables.font({
            ...buttonGlobalVars.font,
            ...vars.fonts,
            color: vars.fonts?.color ?? colors.fg,
        });

        const sizing: IButton["sizing"] = {
            minHeight: undefined,
            minWidth: undefined,
            ...vars.sizing,
        };

        const hover: IButton["hover"] = vars.state ?? vars.hover ?? {};
        const focus: IButton["focus"] = vars.state ?? vars.focus ?? {};
        const active: IButton["active"] = vars.state ?? vars.active ?? {};
        const focusAccessible: IButton["focusAccessible"] = vars.state ?? vars.focusAccessible ?? {};

        const disabled: IButton["disabled"] = vars.disabled ?? {};

        const borders: IButton["borders"] = Variables.border({
            ...buttonGlobalVars.border,
            ...vars.borders,
        });

        const padding: IButton["padding"] = {
            horizontal: buttonGlobalVars.padding.horizontal,
            ...vars.padding,
        };

        return {
            ...vars,
            name: vars.name ?? ButtonTypes.STANDARD,
            useShadow: vars.useShadow ?? false,
            skipDynamicPadding: vars.skipDynamicPadding ?? false,
            opacity: vars.opacity ?? undefined,
            extraNested: vars.extraNested ?? undefined,
            colors,
            fonts,
            borders,
            sizing,
            padding,
            hover,
            focus,
            active,
            disabled,
            focusAccessible,
        };
    };

    static spacing = (vars: ISpacing): ISpacing => ({ ...EMPTY_SPACING, ...vars });

    static font = (vars: IFont): IFont => ({ ...EMPTY_FONTS, ...vars });

    static border = (vars: Partial<IBorderStyles>): Partial<IBorderStyles> => ({ ...EMPTY_BORDER, ...vars });

    static background = (vars: IBackground): IBackground => ({ ...EMPTY_BACKGROUND, ...vars });

    static clickable = (vars: IStateColors): IStateColors => ({ ...EMPTY_STATE_COLORS, ...vars });

    static box = (vars: IPartialBoxOptions): IBoxOptions => {
        return {
            borderType: vars?.borderType ?? BorderType.NONE,
            background: Variables.background(vars?.background ?? {}),
            spacing: Variables.spacing(vars?.spacing ?? {}),
            border: Variables.border(vars?.border ?? {}),
            itemSpacing: vars?.itemSpacing ?? 0,
            itemSpacingOnAllItems: vars?.itemSpacingOnAllItems ?? false,
        };
    };

    static boxHasBackground(box: IBoxOptions): boolean {
        const hasBackground = (box.background.color || box.background.image) && !box.background.unsetBackground;
        return !!hasBackground;
    }

    static boxHasOutline(box: IBoxOptions): boolean {
        const hasBackground = Variables.boxHasBackground(box);

        // We have a clearly defined box of sometype.
        // Anything that makes the box stand out from the background on all side
        // Means we should apply some default behaviours, like paddings, and borderRadius.
        const hasFullOutline = [BorderType.BORDER, BorderType.SHADOW].includes(box.borderType) || hasBackground;
        return hasFullOutline;
    }

    static contentBoxes = (vars?: DeepPartial<IContentBoxes>): IContentBoxes => {
        return {
            depth1: Variables.box(vars?.depth1 ?? {}),
            depth2: Variables.box(vars?.depth2 ?? {}),
            depth3: Variables.box(vars?.depth3 ?? {}),
        };
    };
}
