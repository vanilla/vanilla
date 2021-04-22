/* eslint-disable no-console */
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import DiscussionActions, {
    IPutDiscussionBookmarkedResult,
    IPatchDiscussionResult,
} from "@library/features/discussions/DiscussionActions";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { stableObjectHash } from "@vanilla/utils";

export interface IDiscussionsStoreState {
    discussions: IDiscussionState;
}
interface IDiscussionState {
    discussionsByID: Record<number, IDiscussion>;
    discussionIDsByParamHash: Record<string, ILoadable<Array<IDiscussion["discussionID"]>>>;

    fullRecordStatusesByID: Record<number, ILoadable>;
    bookmarkStatusesByID: Record<number, ILoadable>;
    changeTypeByID: Record<number, ILoadable>;
    patchStatusByPatchID: Record<string, ILoadable>;
    deleteStatusesByID: Record<number, ILoadable>;
}

export const INITIAL_DISCUSSIONS_STATE: IDiscussionState = {
    discussionsByID: {},
    discussionIDsByParamHash: {},

    fullRecordStatusesByID: {},
    bookmarkStatusesByID: {},
    patchStatusByPatchID: {},
    deleteStatusesByID: {},
    changeTypeByID: {},
};

/**
 * Reducer for discussion related data.
 */
export const discussionsReducer = produce(
    reducerWithInitialState(INITIAL_DISCUSSIONS_STATE)
        .case(DiscussionActions.getDiscussionByIDACs.started, (state, params) => {
            const { discussionID } = params;
            state.fullRecordStatusesByID[discussionID] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(DiscussionActions.getDiscussionByIDACs.failed, (state, payload) => {
            const { discussionID } = payload.params;
            state.fullRecordStatusesByID[discussionID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(DiscussionActions.getDiscussionByIDACs.done, (state, payload) => {
            const { discussionID } = payload.params;
            state.fullRecordStatusesByID[discussionID] = { status: LoadStatus.SUCCESS };
            state.discussionsByID[discussionID] = {
                ...state.discussionsByID[discussionID],
                ...payload.result,
            };
            return state;
        })
        .case(DiscussionActions.putDiscussionBookmarkedACs.started, (state, params) => {
            const { discussionID } = params;
            state.bookmarkStatusesByID[discussionID] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(DiscussionActions.putDiscussionBookmarkedACs.done, (state, payload) => {
            const { discussionID } = payload.params;
            state.bookmarkStatusesByID[discussionID] = {
                status: LoadStatus.SUCCESS,
            };
            state.discussionsByID[discussionID] = {
                ...state.discussionsByID[discussionID],
                bookmarked: payload.result.bookmarked,
            };
            return state;
        })
        .case(DiscussionActions.putDiscussionTypeACs.started, (state, params) => {
            const { discussionID } = params;
            state.changeTypeByID[discussionID] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(DiscussionActions.putDiscussionTypeACs.done, (state, payload) => {
            const { discussionID } = payload.params;
            state.changeTypeByID[discussionID] = {
                status: LoadStatus.SUCCESS,
            };
            state.discussionsByID[discussionID] = {
                ...state.discussionsByID[discussionID],
                ...payload.result,
            };
            return state;
        })
        .case(DiscussionActions.putDiscussionTypeACs.failed, (state, payload) => {
            const { discussionID } = payload.params;
            state.changeTypeByID[discussionID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(DiscussionActions.getDiscussionListACs.started, (state, params) => {
            const paramHash = stableObjectHash(params);
            state.discussionIDsByParamHash[paramHash] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(DiscussionActions.getDiscussionListACs.done, (state, payload) => {
            const paramHash = stableObjectHash(payload.params);
            state.discussionIDsByParamHash[paramHash] = {
                status: LoadStatus.SUCCESS,
                data: payload.result.map(({ discussionID }) => discussionID),
            };
            payload.result.forEach((discussion) => {
                state.discussionsByID[discussion.discussionID] = {
                    ...state.discussionsByID[discussion.discussionID],
                    ...discussion,
                };
            });
            return state;
        })
        .case(DiscussionActions.getDiscussionListACs.failed, (state, payload) => {
            const paramHash = stableObjectHash(payload.params);
            state.discussionIDsByParamHash[paramHash] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(DiscussionActions.patchDiscussionACs.started, (state, params) => {
            const { discussionID, patchStatusID } = params;
            state.patchStatusByPatchID[`${discussionID}-${patchStatusID}`] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(DiscussionActions.patchDiscussionACs.done, (state, payload) => {
            const { discussionID, patchStatusID } = payload.params;
            state.patchStatusByPatchID[`${discussionID}-${patchStatusID}`] = {
                status: LoadStatus.SUCCESS,
            };
            state.discussionsByID[payload.result.discussionID] = {
                ...state.discussionsByID[payload.result.discussionID],
                ...payload.result,
            };
            return state;
        })
        .case(DiscussionActions.patchDiscussionACs.failed, (state, payload) => {
            const { discussionID, patchStatusID } = payload.params;
            state.patchStatusByPatchID[`${discussionID}-${patchStatusID}`] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(DiscussionActions.deleteDiscussionACs.started, (state, params) => {
            const { discussionID } = params;
            state.deleteStatusesByID[discussionID] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(DiscussionActions.deleteDiscussionACs.done, (state, payload) => {
            const { discussionID } = payload.params;

            state.deleteStatusesByID[discussionID] = {
                status: LoadStatus.SUCCESS,
            };

            delete state.discussionsByID[discussionID];

            Object.keys(state.discussionIDsByParamHash).forEach((paramHash) => {
                if (state.discussionIDsByParamHash[paramHash].data !== undefined) {
                    state.discussionIDsByParamHash[paramHash].data = state.discussionIDsByParamHash[
                        paramHash
                    ].data!.filter((key) => key !== discussionID);
                }
            });

            return state;
        })
        .case(DiscussionActions.deleteDiscussionACs.failed, (state, payload) => {
            const { discussionID } = payload.params;
            state.deleteStatusesByID[discussionID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        }),
);
