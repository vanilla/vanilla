/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { shadowHelper } from "@library/styles/shadowHelpers";

export const pageLocationVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("pageLocation");

    const spacer = makeThemeVars("spacing", {
        default: 15,
    });

    const picker = makeThemeVars("colors", {
        color: globalVars.mixBgAndFg(0.83),
        padding: {
            vertical: 8,
            left: spacer.default,
            right: spacer.default * 1.5,
        },
    });

    const icon = makeThemeVars("icon", {
        opacity: 0.8,
    });

    return { spacer, picker, icon };
});

export const pageLocationClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formVars = formElementsVariables();
    const vars = pageLocationVariables();
    const style = styleFactory("folderContents");

    const root = style({
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
        justifyContent: "flex-start",
        marginBottom: styleUnit(globalVars.spacer.size),
        minHeight: formVars.sizing.height,
        cursor: "pointer",
    });

    const picker = style("picker", {
        ...shadowHelper().embed(),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        ...Mixins.border({
            radius: formVars.sizing.height / 2,
        }),
        ...userSelect(),
        marginRight: styleUnit(8),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small", "semiBold"),
            color: ColorsUtils.colorOut(vars.picker.color),
        }),
        ...Mixins.padding(vars.picker.padding),
        ...{
            "&:active": {
                borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
            "&:hover": {
                borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
            "&:focus": {
                borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
            "&.focus-visible": {
                borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
            "&&:not(.focus-visible)": {
                outline: 0,
            },
        },
    });

    return {
        root,
        picker,
    };
});
