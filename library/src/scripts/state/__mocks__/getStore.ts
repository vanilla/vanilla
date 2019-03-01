/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { createMockStore } from "redux-test-utils";

let storeState = {};

export function __mockStore(state: any) {
    storeState = state;
}

export default function getStore() {
    return createMockStore(storeState);
}
