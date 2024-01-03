/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { DeepPartial } from "redux";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { testStoreState } from "@library/__tests__/testStoreState";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";

/**
 * FIXME: Make this more extendable or rename
 * This is only useful for core tests. It ignores extra reducer keys
 */
export function TestReduxProvider(props: {
    state?: DeepPartial<ICoreStoreState> & Record<string, any>;
    children?: React.ReactNode;
}) {
    const store = useMemo(() => {
        const initialState = testStoreState(props.state ?? {});
        const store = getStore(initialState, true);
        return store;
    }, [props.state]);

    return <Provider store={store}>{props.children}</Provider>;
}
