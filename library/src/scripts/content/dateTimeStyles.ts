import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { defaultFontFamily, globalVariables } from "@library/styles/globalStyleVars";
import { borders } from "@library/styles/styleHelpersBorders";
import { paddings } from "@library/styles/styleHelpersSpacing";
import { colorOut, fonts, unit, userSelect } from "@library/styles/styleHelpers";
import { TextTransformProperty } from "csstype";

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
            font: {
                size: 10,
                transform: "uppercase" as TextTransformProperty,
                lineHeight: 1,
            },
        },
        day: {
            font: {
                size: 16,
                weight: globalVars.fonts.weights.bold,
                lineHeight: 1,
            },
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
        backgroundColor: colorOut(compactVars.container.bg),
        minWidth: unit(compactVars.container.size),
        minHeight: unit(compactVars.container.size),
        ...userSelect(),
        borderRadius: unit(compactVars.container.border.radius),
    });

    const compactDay = style("compactDay", {
        ...fonts(compactVars.day.font),
    });

    const compactMonth = style("compactMonth", {
        ...fonts(compactVars.month.font),
    });

    return { compactRoot, compactDay, compactMonth };
});
