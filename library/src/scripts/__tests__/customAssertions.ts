/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { expect, assert } from "chai";
import { MockStore } from "redux-mock-store";

/**
 * Assert that a given mock store has recieved the given actions.
 *
 * This is not a strict equality match because we often have headers/axios config in the repsonses, which I do not want to simulate for everything test. In the case that a payload.data is provided (eg. axios response) then only the data will be compared.
 *
 * @param store - The mock store.
 * @param actions - The expected actions.
 */
export function assertStoreHasActions(store: MockStore, actions: any[]) {
    const realActions = store.getActions();

    actions.forEach((action, index) => {
        const realAction = realActions[index];
        expect(realAction.type, `expected action types to match for action #${index}.`).eq(action.type);

        if ("payload" in action) {
            assert("payload" in realAction, `expected action #${index} to have property payload, but it was not found`);

            if ("data" in action.payload) {
                assert(
                    "data" in realAction.payload,
                    `expected action #${index}'s payload to have property data, but it was not found`,
                );
                expect(
                    action.payload.data,
                    `expected action #${index}'s payload.data to be ${action.payload.data}. Got ${
                        realAction.payload.data
                    }`,
                ).deep.equals(realAction.payload.data);
            } else {
                expect(
                    action.payload,
                    `expected action #${index}'s payload to be ${action.payload}. Got ${realAction.payload}`,
                ).deep.equals(realAction.payload);
            }
        }
    });
}
