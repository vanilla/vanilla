/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { expect } from "chai";
import instanceReducer, { initialState, defaultInstance } from "@rich-editor/state/instance/instanceReducer";
import { actions as instanceActions } from "./instanceActions";

describe("instanceReducer", () => {
    it("should return the initial state", () => {
        expect(instanceReducer(undefined, {} as any)).deep.equals(initialState);
    });

    describe("CREATE_INSTANCE", () => {
        it("creates an instance with the default value", () => {
            const action = instanceActions.createInstance("123");
            expect(instanceReducer(undefined, action)).deep.equals({ 123: defaultInstance });
        });

        it("throws an error if the instance is already created", () => {
            const init = { 123: defaultInstance };
            const action = instanceActions.createInstance("123");
            expect(() => instanceReducer(init, action)).to.throw();
        });
    });

    describe("SET_SELECTION", () => {
        it("always sets the passed selection as the currentSelection", () => {
            const init = { 123: defaultInstance };
            const selections = [null, { index: 0, length: 0 }];

            selections.forEach(selection => {
                const action = instanceActions.setSelection(123, selection);
                expect(instanceReducer(init, action)[123].currentSelection).deep.equals(selection);
            });
        });
        it("overrides the last good selection with non-null values", () => {
            const init = { 123: defaultInstance };
            const goodSelections = [{ index: 5, length: 93 }, { index: 0, length: 0 }];

            goodSelections.forEach(selection => {
                const action = instanceActions.setSelection(123, selection);
                expect(instanceReducer(init, action)[123].lastGoodSelection).deep.equals(selection);
            });
        });
        it("does not override the last good selection with null value", () => {
            const lastGoodSelection = { index: 5, length: 93 };
            const init = {
                123: {
                    ...defaultInstance,
                    lastGoodSelection,
                },
            };

            const action = instanceActions.setSelection(123, null);
            expect(instanceReducer(init, action)[123].lastGoodSelection).deep.equals(lastGoodSelection);
        });
    });
});
