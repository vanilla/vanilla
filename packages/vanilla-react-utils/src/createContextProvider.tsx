/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";

/**
 * Create a react context, provider, and use function.
 */
export function createProvider<
    ContextValueType extends object,
    ProviderPropTypes extends { children: React.ReactNode }
>(
    defaultValue: ContextValueType,
    valueCalculator: (props: ProviderPropTypes) => ContextValueType,
    displayName?: string,
) {
    const context = React.createContext<ContextValueType>(defaultValue);
    const useContextValue = () => {
        useContext(context);
    };

    function CreatedProvider(props: ProviderPropTypes) {
        const value = valueCalculator(props);
        return <context.Provider value={value}>{props.children}</context.Provider>;
    }

    return [CreatedProvider, useContextValue];
}
