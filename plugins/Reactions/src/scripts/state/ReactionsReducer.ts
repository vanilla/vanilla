/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Loadable, LoadStatus } from "@library/@types/api/core";
import { stableObjectHash } from "@vanilla/utils";
import { IUser } from "@library/@types/api/users";
import { configureStore, createSlice } from "@reduxjs/toolkit";
import { getUserReactions } from "@Reactions/state/ReactionsActions";
import { IReaction } from "@Reactions/types/Reaction";
import { TypedUseSelectorHook, useDispatch, useSelector } from "react-redux";

export interface IReactionsState {
    reactionsByID: Record<IReaction["tagID"], IReaction>;
    reactionIDsByUserID: Record<IUser["userID"], Array<IReaction["tagID"]>>;
    reactionIDsByParamHash: Record<string, Loadable<Array<IReaction["tagID"]>>>;
}

export const INITIAL_REACTIONS_STATE: IReactionsState = {
    reactionsByID: {},
    reactionIDsByUserID: {},
    reactionIDsByParamHash: {},
};

// FIXME: when https://github.com/reduxjs/redux-toolkit/issues/743 is released,
// we can set requestId to stableObjectHash(apiParams).toString() in the actionCreator, and avoid re-defining paramHash in each reducer function

export const reactionsSlice = createSlice({
    name: "reactions",
    initialState: INITIAL_REACTIONS_STATE,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(getUserReactions.pending, (state, action) => {
                const paramHash = stableObjectHash(action.meta.arg);
                state.reactionIDsByParamHash[paramHash] = { status: LoadStatus.PENDING };
            })
            .addCase(getUserReactions.fulfilled, (state, action) => {
                const paramHash = stableObjectHash(action.meta.arg);
                // Only need reactions count > 0
                const reactions = action.payload.filter((reaction) => reaction.count > 0);
                reactions.forEach((reaction) => {
                    state.reactionsByID[reaction.tagID] = {
                        ...state.reactionsByID[reaction.tagID],
                        ...reaction,
                    };
                });
                const reactionIDs = reactions.map(({ tagID }) => tagID);
                state.reactionIDsByParamHash[paramHash] = {
                    status: LoadStatus.SUCCESS,
                    data: reactionIDs,
                };
                state.reactionIDsByUserID[action.meta.arg.userID] = [
                    ...new Set((state.reactionIDsByUserID[action.meta.arg.userID] ?? []).concat(reactionIDs)),
                ];
            })
            .addCase(getUserReactions.rejected, (state, action) => {
                const paramHash = stableObjectHash(action.meta.arg);
                state.reactionIDsByParamHash[paramHash] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            });
    },
});

export function getLoadStatusByParamHash(reationsState: IReactionsState, paramHash: number): LoadStatus | undefined {
    return reationsState.reactionIDsByParamHash[paramHash]?.status;
}

export function getReactionsByUserID(reationsState: IReactionsState, userID: IUser["userID"]): IReaction[] | undefined {
    return reationsState.reactionIDsByUserID[userID]?.map((reactionID) => reationsState.reactionsByID[reactionID]);
}

export function getReactionByID(reationsState: IReactionsState, reactionID: IReaction["tagID"]): IReaction | undefined {
    return reationsState.reactionsByID[reactionID];
}

const dispatch = configureStore(reactionsSlice).dispatch;
export const useReactionsDispatch = () => useDispatch<typeof dispatch>();
export const useReactionsSelector: TypedUseSelectorHook<{ reactions: IReactionsState }> = useSelector;
