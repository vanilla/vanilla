/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import DiscussionActions, { IPutDiscussionBookmarkedResult } from "@library/features/discussions/DiscussionActions";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IDiscussion } from "@dashboard/@types/api/discussion";

export interface IDiscussionsStoreState {
    discussions: IDiscussionState;
}

interface IDiscussionState {
    discussionsByID: Record<number, ILoadable<IDiscussion>>;
    bookmarkedByID: Record<number, ILoadable<IPutDiscussionBookmarkedResult>>;
    // TODO: add reactedByID?
}

export const INITIAL_DISCUSSIONS_STATE: IDiscussionState = {
    discussionsByID: {},
    bookmarkedByID: {},
};

/**
 * Reducer for discussion related data.
 */
export const discussionsReducer = produce(
    reducerWithInitialState(INITIAL_DISCUSSIONS_STATE)
        .case(DiscussionActions.getDiscussionByIDACs.started, (state, params) => {
            const { discussionID } = params;
            state.discussionsByID[discussionID] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(DiscussionActions.getDiscussionByIDACs.done, (state, payload) => {
            const { discussionID } = payload.params;
            state.discussionsByID[discussionID] = {
                data: payload.result,
                status: LoadStatus.SUCCESS,
            };

            return state;
        })
        .case(DiscussionActions.getDiscussionByIDACs.failed, (state, payload) => {
            const { discussionID } = payload.params;
            state.discussionsByID[discussionID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(DiscussionActions.putDiscussionBookmarkedACs.started, (state, params) => {
            const { discussionID } = params;
            state.bookmarkedByID[discussionID] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(DiscussionActions.putDiscussionBookmarkedACs.done, (state, payload) => {
            const { discussionID } = payload.params;
            state.bookmarkedByID[discussionID] = {
                data: payload.result,
                status: LoadStatus.SUCCESS,
            };
            return state;
        })
        .case(DiscussionActions.putDiscussionBookmarkedACs.failed, (state, payload) => {
            const { discussionID } = payload.params;
            state.bookmarkedByID[discussionID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        }),
);
