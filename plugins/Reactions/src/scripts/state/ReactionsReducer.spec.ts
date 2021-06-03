/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import { getUserReactions } from "@Reactions/state/ReactionsActions";
import { INITIAL_REACTIONS_STATE, reactionsSlice } from "@Reactions/state/ReactionsReducer";
import { dummyReactionsData } from "@Reactions/storybook/dummyReactions";
import { IReaction } from "@Reactions/types/Reaction";
import { stableObjectHash } from "@vanilla/utils";
import { expect } from "chai";

describe("ReactionsReducer", () => {
    it("Get user reactions", () => {
        // Test we don't get dummyAgreeCount to the state.
        const dummyAgreeCount0 = {
            ...dummyReactionsData["Agree"],
            count: 0,
        };
        const fakeReactions: IReaction[] = [dummyReactionsData["LOL"], dummyAgreeCount0, dummyReactionsData["Like"]];

        const action = {
            //"@@reactions/getUserReactions/fulfilled"
            type: getUserReactions.fulfilled,
            payload: fakeReactions,
            meta: { arg: { userID: 1 } },
        };
        const reactionsByID = { 14: dummyReactionsData["LOL"], 9: dummyReactionsData["Like"] };
        let reactionIDsByParamHash = {};
        const paramHash = stableObjectHash({ userID: 1 });
        reactionIDsByParamHash[paramHash] = { status: LoadStatus.SUCCESS, data: [14, 9] };
        const state = reactionsSlice.reducer(INITIAL_REACTIONS_STATE, action);

        const expectedState = {
            reactionsByID,
            reactionIDsByUserID: { 1: [14, 9] },
            reactionIDsByParamHash,
        };
        expect(state).deep.equals(expectedState);
    });
});
