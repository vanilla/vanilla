/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";
import { IThemeVariables } from "@library/theming/themeReducer";

export const userCardVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("userCard", forcedVars);
    const globalVars = globalVariables();

    const container = makeVars("container", {
        spacing: globalVars.gutter.size / 2,
    });

    const actionContainer = makeVars("actionContainer", {
        spacing: 12,
    });

    const button = makeVars("button", {
        minWidth: 120,
        mobile: {
            minWidth: 0,
        },
    });

    const buttonContainer = makeVars("buttonContainer", {
        padding: globalVars.gutter.half,
    });

    const name = makeVars("name", {
        ...globalVars.fontSizeAndWeightVars("large", "bold"),
    });

    const label = makeVars("label", {
        border: Variables.border({
            color: globalVars.mainColors.primary,
            radius: 3,
        }),
        padding: Variables.spacing({
            vertical: 2,
            horizontal: 10,
        }),
        font: Variables.font({
            color: globalVars.mainColors.primary,
            size: 10,
            transform: "uppercase",
        }),
    });

    const containerWithBorder = makeVars("containerWithBorder", {
        color: ColorsUtils.colorOut(globalVars.border.color),
        ...Mixins.padding({
            horizontal: container.spacing * 2,
            vertical: container.spacing * 4,
        }),
    });

    const count = makeVars("count", {
        size: 28,
    });

    const header = makeVars("header", {
        height: 32,
    });

    const date = makeVars("date", {
        padding: globalVars.gutter.size,
    });

    const email = makeVars("email", {
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    return {
        container,
        button,
        buttonContainer,
        actionContainer,
        name,
        label,
        containerWithBorder,
        count,
        header,
        date,
        email,
    };
});
