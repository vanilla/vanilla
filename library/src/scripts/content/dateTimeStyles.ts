import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";

export const dateTimeVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("dateTime", forcedVars);
    const globalVars = globalVariables();

    const compact = makeVars("compact", {
        container: {
            size: 38, // cheated to align with text
            bg: globalVars.mixBgAndFg(0.1),
            border: {
                radius: 8,
            },
        },
        month: {
            font: Variables.font({
                size: 10,
                transform: "uppercase",
                lineHeight: 1,
            }),
        },
        day: {
            font: Variables.font({
                size: 16,
                weight: globalVars.fonts.weights.bold,
                lineHeight: 1,
            }),
        },
    });

    return { compact };
});

export const dateTimeClasses = useThemeCache(() => {
    const style = styleFactory("dateTime");
    const vars = dateTimeVariables();
    const compactVars = vars.compact;

    const compactRoot = style("compactRoot", {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        backgroundColor: ColorsUtils.colorOut(compactVars.container.bg),
        minWidth: styleUnit(compactVars.container.size),
        minHeight: styleUnit(compactVars.container.size),
        ...userSelect(),
        ...{
            "&&": {
                borderRadius: styleUnit(compactVars.container.border.radius),
            },
        },
    });

    const compactDay = style("compactDay", {
        ...Mixins.font(compactVars.day.font),
    });

    const compactMonth = style("compactMonth", {
        ...Mixins.font(compactVars.month.font),
    });

    return { compactRoot, compactDay, compactMonth };
});
