/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useContext } from "react";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { buttonVariables, buttonGlobalVariables } from "@library/forms/buttonStyles";

///
/// Types
///
interface IThemeBuilderContext {
    rawThemeVariables: IThemeVariables;
    defaultThemeVariables: IThemeVariables;
    initialThemeVariables: IThemeVariables;
    generatedThemeVariables: IThemeVariables;
}

type IProps = Pick<IThemeBuilderContext, "rawThemeVariables">;

///
/// Context and Hook
///

const context = React.createContext<IThemeBuilderContext>({
    rawThemeVariables: {},
    defaultThemeVariables: {},
    initialThemeVariables: {},
    generatedThemeVariables: {},
});

/**
 * Hook to use for variables in the theme builder form.
 */
export function useThemeBuilder() {
    return useContext(context);
}

///
/// Provider Implementation
///

export function ThemeBuilderContextProvider(props: IProps) {
    const { rawThemeVariables } = props;
    const defaultThemeVariables = useMemo(() => variableGenerator({}), []);
    const generatedThemeVariables = variableGenerator(rawThemeVariables);
    const initialThemeVariables = useMemo(() => generatedThemeVariables, []);

    return (
        <context.Provider
            value={{ rawThemeVariables, defaultThemeVariables, generatedThemeVariables, initialThemeVariables }}
        ></context.Provider>
    );
}

///
/// Generated variable calculation.
///
/// Addons may hook into this to apply additional generated variables.
///

type IVariableGenerator = (variables: IThemeVariables) => IThemeVariables;

let addonVariableGenerators: Record<string, IVariableGenerator> = {};

/**
 * Use this method to register additional variable generators for the theme builder.
 * This should be used anytime an addon is hooking into the theme builder.
 *
 * @param generators
 */
export function registerAddonVariableGenerators(generators: typeof addonVariableGenerators) {
    addonVariableGenerators = {
        ...addonVariableGenerators,
        ...generators,
    };
}

/**
 * Generate "default" values to use in the theme forms.
 * In order for this to work, the variable method must accept "forced" variables.
 * Just taking them from the global store will not work.
 * @param variables
 */
function variableGenerator(variables: IThemeVariables) {
    let result: IThemeVariables = {
        global: globalVariables(variables),
        buttonGlobals: buttonGlobalVariables(variables),
        button: buttonVariables(variables),
    };

    // Mix in the addons generator variables.

    for (const [key, generator] of Object.entries(addonVariableGenerators)) {
        result[key] = generator(variables);
    }

    return result;
}
