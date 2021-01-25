/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useContext, useState, useCallback, useDebugValue, useRef, useEffect } from "react";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { buttonVariables, buttonGlobalVariables } from "@library/forms/Button.variables";
import get from "lodash/get";
import set from "lodash/set";
import cloneDeep from "lodash/cloneDeep";
import unset from "lodash/unset";
import { bannerVariables } from "@library/banner/bannerStyles";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { contentBannerVariables } from "@library/banner/contentBannerStyles";
import { userContentVariables, userContentClasses } from "@library/content/userContentStyles";
import { all } from "q";
import { navigationVariables } from "@library/headers/navigationVariables";
import { homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import { homeWidgetContainerVariables } from "@library/homeWidget/HomeWidgetContainer.styles";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";

///
/// Types
///
interface IThemeBuilderContext {
    rawThemeVariables: IThemeVariables;
    defaultThemeVariables: IThemeVariables;
    initialThemeVariables: IThemeVariables;
    generatedThemeVariables: IThemeVariables;
    variableErrors: IThemeVariables;
    setVariableValue: (variableKey: string, value: any, allowNull?: boolean) => void;
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
        rawValue: get(context.rawThemeVariables, variableKey, undefined) as T | null | undefined,
        defaultValue: get(context.defaultThemeVariables, variableKey, undefined) as T | null | undefined,
        initialValue: get(context.initialThemeVariables, variableKey, undefined) as T | null | undefined,
        generatedValue: get(context.generatedThemeVariables, variableKey, undefined) as T | null | undefined,
        error: get(context.variableErrors, variableKey, undefined) as string | null | undefined,
        setValue: (value: T | null | undefined, allowEmpty = false) => {
            context.setVariableValue(variableKey, value, allowEmpty);
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

    const rawValueRef = useRef<IThemeVariables>(rawThemeVariables);

    // Lock the value to the one on first render.
    // eslint-disable-next-line react-hooks/exhaustive-deps
    const initialThemeVariables = useMemo(() => cloneDeep(rawThemeVariables), []);
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
        onChange(rawValueRef.current, hasErrors);
    };

    const setVariableValue = (variableKey: string, value: any, allowEmpty: boolean = false) => {
        const newErrors = calculateNewErrors(variableKey, null);
        const hasErrors = getErrorCount(newErrors) > 0;
        let cloned = cloneDeep(rawValueRef.current);
        rawValueRef.current = cloned;

        if ((value === "" || value === undefined) && !allowEmpty) {
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
                rawThemeVariables: rawValueRef.current,
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
        banner: bannerVariables(variables),
        contentBanner: contentBannerVariables(variables),
        titleBar: titleBarVariables(variables),
        userContent: userContentVariables(variables),
        navigation: navigationVariables(variables),
        homeWidgetItem: homeWidgetItemVariables({}, variables),
        homeWidgetContainer: homeWidgetContainerVariables({}, variables),
        quickLinks: quickLinksVariables(variables),
    };

    // Mix in the addons generator variables.

    for (const [key, generator] of Object.entries(addonVariableGenerators)) {
        result[key] = generator(variables);
    }

    return result;
}
