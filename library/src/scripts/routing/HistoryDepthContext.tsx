/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useReducer, useEffect, useContext, useDebugValue } from "react";
import { useHistory } from "react-router";
import * as H from "history";

const context = React.createContext<{ historyDepth: number; canGoBack: boolean }>({
    historyDepth: 1,
    canGoBack: false,
});

/**
 * Provider for measuring how deep in our dynamic routing history we are.
 */
export function HistoryDepthContextProvider(props: { children: React.ReactNode }) {
    const [{ historyDepth }, dispatch] = useReducer(
        (nextState: { historyDepth: number }, action: H.Action) => {
            if (action === "PUSH") {
                return { historyDepth: nextState.historyDepth + 1 };
            } else if (action === "POP") {
                return { historyDepth: nextState.historyDepth - 1 };
            }
            return nextState;
        },
        { historyDepth: 1 },
    );

    const history = useHistory();

    useEffect(() => {
        const unregister = history.listen((location: H.Location, action: H.Action) => {
            dispatch(action);
        });

        return unregister;
    }, [dispatch, history]);

    const value = {
        canGoBack: historyDepth > 1,
        historyDepth,
    };

    return <context.Provider value={value} children={props.children} />;
}

/**
 * Hook for knowing how deep in our routing history that we are.
 */
export function useHistoryDepth() {
    const value = useContext(context);
    useDebugValue(value);
    return value;
}
