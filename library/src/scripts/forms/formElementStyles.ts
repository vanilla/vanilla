/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { IIconSizes } from "@library/styles/cssUtilsTypes";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";

export const formElementsVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const vars = globalVariables(forcedVars);
    const makeThemeVars = variableFactory("formElements", forcedVars);

    const mixBgAndFg = vars.mixBgAndFg;

    const sizing = makeThemeVars("sizing", {
        height: 36,
        halfHeight: 18,
        maxWidth: 528,
    });

    const spacing = makeThemeVars("spacing", {
        margin: 12,
        verticalPadding: 6,
        horizontalPadding: 12,
        fullBorderRadius: {
            extraHorizontalPadding: 4, // Padding when you have fully rounded border radius. Will be applied based on the amount of border radius. Set to "undefined" to turn off
        },
    });

    const border = makeThemeVars("border", {
        width: vars.borderType.formElements.default.width ?? vars.border.width,
        color: vars.borderType.formElements.default.color ?? vars.border.color,
        style: vars.borderType.formElements.default.style ?? vars.border.style,
        radius: vars.borderType.formElements.default.radius ?? vars.border.radius,
    });

    const giantInput = makeThemeVars("giantInput", {
        height: 82,
        fontSize: 24,
    });

    const largeInput = makeThemeVars("largeInput", {
        height: 48,
        fontSize: 16,
    });

    const colors = makeThemeVars("colors", {
        fg: vars.mainColors.fg,
        bg: vars.mainColors.bg,
    });

    const errorSpacing = makeThemeVars("errorSpacing", {
        horizontalPadding: vars.constants.fullGutter,
        verticalPadding: vars.constants.fullGutter,
        verticalMargin: vars.constants.fullGutter / 2,
    });

    const placeholder = makeThemeVars("placeholder", {
        color: mixBgAndFg(0.83),
    });

    const disabled = makeThemeVars("disabled", {
        opacity: 0.5,
    });

    const buttonMarginAlignment = (size: IIconSizes = IIconSizes.DEFAULT) => {
        const globalVars = globalVariables();
        let iconSize = globalVars.icon.sizes.default;

        switch (size) {
            case IIconSizes.SMALL:
                iconSize = globalVars.icon.sizes.small;
                break;
            case IIconSizes.LARGE:
                iconSize = globalVars.icon.sizes.small;
                break;
        }

        return (sizing.height - iconSize) / 2;
    };

    return {
        sizing,
        errorSpacing,
        spacing,
        border,
        giantInput,
        largeInput,
        colors,
        placeholder,
        disabled,
        buttonMarginAlignment,
    };
});

export const accessibleErrorClasses = useThemeCache(() => {
    const style = styleFactory("accessibleError");
    const globalVars = globalVariables();
    const root = style({
        display: "flex",
        alignItems: "center",
    });
    const paragraph = style("paragraph", {
        color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
        fontSize: styleUnit(globalVars.fonts.size.small),
    });

    return {
        root,
        paragraph,
    };
});

export const formErrorClasses = useThemeCache(() => {
    const style = styleFactory("formError");
    const varsGlobal = globalVariables();
    const vars = formElementsVariables();

    const root = style({
        backgroundColor: ColorsUtils.colorOut(varsGlobal.messageColors.error.fg),
        color: ColorsUtils.colorOut(varsGlobal.messageColors.error.fg),
        marginBottom: styleUnit(16),
        paddingLeft: vars.errorSpacing.horizontalPadding,
        paddingRight: vars.errorSpacing.horizontalPadding,
        paddingTop: vars.errorSpacing.verticalPadding,
        paddingBottom: vars.errorSpacing.verticalPadding,
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
    });

    const actions = style("actions", {
        display: "flex",
        alignItems: "center",
    });

    const actionButton = style("button", {
        marginLeft: styleUnit(12),
    });

    const activeButton = style("activeButton", {
        fontWeight: "bold",
    });

    return {
        actionButton,
        root,
        activeButton,
        actions,
    };
});
