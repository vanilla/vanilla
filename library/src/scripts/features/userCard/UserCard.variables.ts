/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleUnit } from "@library/styles/styleUnit";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";
import { IThemeVariables } from "@library/theming/themeReducer";

export const userCardVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("userCard", forcedVars);
    const globalVars = globalVariables();

    const container = makeVars("container", {
        spacing: globalVars.gutter.size,
    });

    const button = makeVars("button", {
        minWidth: 120,
        mobile: {
            minWidth: 0,
        },
    });

    const name = makeVars("name", {
        font: Variables.font({
            ...globalVars.fontSizeAndWeightVars("large", "bold"),
        }),
        margin: Variables.spacing({
            top: 9,
        }),
    });

    const label = makeVars("label", {
        border: Variables.border({
            color: globalVars.mainColors.primary,
            radius: 3,
        }),
        padding: Variables.spacing({
            horizontal: 10,
        }),
        margin: Variables.spacing({
            top: 12,
        }),
        font: Variables.font({
            color: globalVars.mainColors.primary,
            size: 10,
            lineHeight: 15 / 10,
            transform: "uppercase",
        }),
    });

    const containerWithBorder = makeVars("containerWithBorder", {
        color: ColorsUtils.colorOut(globalVars.border.color),
    });

    const message = makeVars("message", {
        font: Variables.font({
            size: globalVars.fonts.size.small,
        }),
        margin: Variables.spacing({
            top: 12,
        }),
    });

    const headerLink = makeVars("headerLink", {
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        font: Variables.font({ ...globalVars.fontSizeAndWeightVars("small"), align: "center" }),
        margin: Variables.spacing({
            top: 8,
        }),
        minHeight: styleUnit(24),
    });

    return {
        container,
        button,
        name,
        label,
        containerWithBorder,
        message,
        headerLink,
    };
});
