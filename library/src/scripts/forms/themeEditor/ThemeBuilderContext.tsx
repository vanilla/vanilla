/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useContext, useState, useCallback, useDebugValue } from "react";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { buttonVariables, buttonGlobalVariables } from "@library/forms/buttonStyles";
import get from "lodash/get";
import set from "lodash/set";
import cloneDeep from "lodash/cloneDeep";
import unset from "lodash/unset";

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

    const value = {
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

    useDebugValue(value);
    return value;
}

///
/// Provider Implementation
///

export function ThemeBuilderContextProvider(props: IProps) {
    const { rawThemeVariables, onChange } = props;
    const defaultThemeVariables = useMemo(() => variableGenerator({}), []);
    const generatedThemeVariables = variableGenerator(rawThemeVariables);

    // Lock the value to the one on first render.
    // eslint-disable-next-line react-hooks/exhaustive-deps
    const initialThemeVariables = useMemo(() => generatedThemeVariables, []);
    const [errors, setErrors] = useState<IThemeVariables>({});

    const calculateNewErrors = (variableKey: string, error: string | null) => {
        let newErrors = cloneDeep(errors);
        if (error) {
            newErrors = set(errors, variableKey, error);
        } else {
            unset(newErrors, variableKey);
        }
        setErrors(newErrors);
        return newErrors;
    };
    const setVariableError = (variableKey: string, error: string | null, doUpdate: boolean = true) => {
        const newErrors = calculateNewErrors(variableKey, error);
        const hasErrors = getErrorCount(newErrors) > 0;
        onChange(rawThemeVariables, hasErrors);
    };

    const setVariableValue = (variableKey: string, value: any) => {
        const newErrors = calculateNewErrors(variableKey, null);
        const hasErrors = getErrorCount(newErrors) > 0;
        let cloned = cloneDeep(rawThemeVariables);
        if (value === "" || value === undefined) {
            // Null does not clear this. Null is a valid value.
            unset(cloned, variableKey);
        } else {
            cloned = set(cloned, variableKey, value);
        }
        onChange(cloned, hasErrors);
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
    function recursivelyFindError(objectOrError: object | string) {
        Object.entries(objectOrError).forEach(([key, objectOrError]) => {
            if (!objectOrError) {
                return;
            }
            if (typeof objectOrError === "object") {
                recursivelyFindError(objectOrError);
            } else {
                // Value exists if there's an error
                result.push(objectOrError);
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
