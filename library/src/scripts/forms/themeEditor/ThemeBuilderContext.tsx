/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useContext, useState, useCallback } from "react";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { buttonVariables, buttonGlobalVariables } from "@library/forms/buttonStyles";
import get from "lodash/get";
import set from "lodash/set";

///
/// Types
///
interface IThemeBuilderContext {
    rawThemeVariables: IThemeVariables;
    defaultThemeVariables: IThemeVariables;
    initialThemeVariables: IThemeVariables;
    generatedThemeVariables: IThemeVariables;
    variableErrors: IThemeVariables;
    setVariableValue: (variableKey: string, value: any) => void;
    setVariableError: (variableKey: string, value: any) => void;
}

type IProps = Pick<IThemeBuilderContext, "rawThemeVariables"> & {
    onChange: (variables: IThemeVariables, hasError: boolean) => void;
    children: React.ReactNode;
};

///
/// Context and Hook
///

const context = React.createContext<IThemeBuilderContext>({
    rawThemeVariables: {},
    defaultThemeVariables: {},
    initialThemeVariables: {},
    generatedThemeVariables: {},
    variableErrors: {},
    setVariableValue: () => {},
    setVariableError: () => {},
});

/**
 * Hook to use for variables in the theme builder form.
 */
export function useThemeBuilder() {
    return useContext(context);
}

export function useThemeVariableField<T>(variableKey: string) {
    const context = useThemeBuilder();

    return {
        rawValue: get(context.rawThemeVariables, variableKey, null) as T | null,
        defaultValue: get(context.defaultThemeVariables, variableKey, null) as T | null,
        initialValue: get(context.initialThemeVariables, variableKey, null) as T | null,
        generatedValue: get(context.generatedThemeVariables, variableKey, null) as T | null,
        error: get(context.variableErrors, variableKey, null) as string | null,
        setValue: (value: T | null) => {
            context.setVariableValue(variableKey, value);
        },
        setError: (value: T | null) => {
            context.setVariableError(variableKey, value);
        },
    };
}

///
/// Provider Implementation
///

export function ThemeBuilderContextProvider(props: IProps) {
    const { rawThemeVariables, onChange } = props;
    const defaultThemeVariables = useMemo(() => variableGenerator({}), []);
    const generatedThemeVariables = variableGenerator(rawThemeVariables);
    const initialThemeVariables = useMemo(() => generatedThemeVariables, []);
    const [errors, setErrors] = useState<IThemeVariables>({});

    const setVariableValue = (variableKey: string, value: any) => {
        const newVariables = set(rawThemeVariables, variableKey, value);
        onChange(newVariables, getErrorCount(errors) > 0);
    };
    const setVariableError = (variableKey: string, error: string | null) => {
        const newErrors = set(errors, variableKey, error);
        setErrors(newErrors);
    };

    return (
        <context.Provider
            value={{
                rawThemeVariables,
                defaultThemeVariables,
                generatedThemeVariables,
                initialThemeVariables,
                variableErrors: errors,
                setVariableValue,
                setVariableError,
            }}
        >
            {props.children}
        </context.Provider>
    );
}

function getErrorCount(errors: IThemeVariables): number {
    const result: any[] = [];
    function recursivelyFindError(o: object | string) {
        o &&
            Object.keys(o).forEach(key => {
                if (typeof o[key] === "object") {
                    recursivelyFindError(o[key]);
                } else {
                    if (o[key]) {
                        // Value exists if there's an error
                        result.push(o);
                    } else {
                        // Value is undefined if no error exists
                        result.pop();
                    }
                }
            });
    }
    recursivelyFindError(errors);
    console.log(result);
    return result.length;
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
