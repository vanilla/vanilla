/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import getStore, { resetStore } from "@library/redux/getStore";
import { registerReducer, resetReducers } from "@library/redux/reducerRegistry";

describe.only("reducerRegistry", () => {
    afterEach(() => {
        resetStore();
        resetReducers();
    });

    it("handles custom reducers that were registered", () => {
        const reducer1 = () => 1;
        registerReducer("reducer1", reducer1);

        const store = getStore();

        const reducer2 = () => 2;
        registerReducer("reducer2", reducer2);

        const state = store.getState();
        expect(state["reducer1"]).toEqual(1);
        expect(state["reducer2"]).toEqual(2);
    });

    it.only("Can apply initial state", () => {
        window.__ACTIONS__ = [
            {
                type: "update",
            },
        ];
        const reducer1 = (state = "1before", action) => {
            if (action.type === "update") {
                return "updated1";
            } else {
                return state;
            }
        };
        registerReducer("reducer1", reducer1);

        const store = getStore<any>();

        const reducer2 = (state = "2before", action) => {
            if (action.type === "update") {
                return "updated2";
            } else {
                return state;
            }
        };
        registerReducer("reducer2", reducer2);

        const state = store.getState();
        expect(state["reducer1"]).toEqual("updated1");
        expect(state["reducer2"]).toEqual("updated2");
    });
});
