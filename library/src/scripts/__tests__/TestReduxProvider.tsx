/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { DeepPartial } from "redux";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { testStoreState } from "@library/__tests__/testStoreState";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";

export function TestReduxProvider(props: { state: DeepPartial<ICoreStoreState>; children?: React.ReactNode }) {
    const initialState = testStoreState(props.state);

    return <Provider store={getStore(initialState, true)}>{props.children}</Provider>;
}
