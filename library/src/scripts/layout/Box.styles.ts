/**
 * @author Alex Brohman <alex.brohman@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CSSObject } from "@emotion/css";
import { IBoxOptions } from "@library/styles/cssUtilsTypes";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { BorderType } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { Variables } from "@library/styles/Variables";
import { IThemeVariables } from "@library/theming/themeReducer";

/**
 * @varGroup box
 * @description Boxes are a layout component used to give content consitent div wrappers. It's primarily in the side panel.
 */
export const boxVariables = useThemeCache((boxOptions?: Partial<IBoxOptions>, forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory("box", forcedVars);
    const globalVars = globalVariables();

    /**
     * @varGroup box.options
     * @description Control different variants for the box appearance. These options can affect multiple parts of the box at once.
     */
    const options: IBoxOptions = makeThemeVars(
        "options",
        Variables.box({
            borderType: BorderType.NONE,
            background: Variables.background({}),
        }),
        boxOptions,
    );

    /**
     * @varGroup box.border
     * @expand border
     */
    const border = makeThemeVars("border", Variables.border({}));

    const needsPadding = [BorderType.SHADOW, BorderType.BORDER].includes(options.borderType);

    /**
     * @varGroup box.padding
     * @expand spacing
     */
    const padding = makeThemeVars(
        "padding",
        Variables.spacing({
            horizontal: needsPadding ? 16 : 0,
            vertical: needsPadding ? 16 : 8,
        }),
    );

    return {
        options,
        border,
        padding,
    };
});

export const boxClasses = useThemeCache((boxOptions?: Partial<IBoxOptions>) => {
    const vars = boxVariables(boxOptions);
    const style = styleFactory("quickLinks");

    const getBorderVars = (): CSSObject => {
        switch (vars.options.borderType) {
            case BorderType.BORDER:
                return Mixins.border(vars.border);
            case BorderType.SHADOW:
                return shadowHelper().embed();
            case BorderType.NONE:
            default:
                return {};
        }
    };

    const root = style({
        ...getBorderVars(),
        ...Mixins.background(vars.options.background),
        ...Mixins.padding(vars.padding),
    });

    return {
        root,
    };
});
