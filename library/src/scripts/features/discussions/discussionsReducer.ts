/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILoadable, Loadable, LoadStatus } from "@library/@types/api/core";
import DiscussionActions from "@library/features/discussions/DiscussionActions";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { stableObjectHash } from "@vanilla/utils";
import { IReaction } from "@dashboard/@types/api/reaction";
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
    postReactionStatusesByID: Record<number, ILoadable<{}>>;
    deleteReactionStatusesByID: Record<number, ILoadable<{}>>;
}

export const INITIAL_DISCUSSIONS_STATE: IDiscussionState = {
    discussionsByID: {},
    discussionIDsByParamHash: {},

    fullRecordStatusesByID: {},
    bookmarkStatusesByID: {},
    patchStatusByPatchID: {},
    deleteStatusesByID: {},
    changeTypeByID: {},
    postReactionStatusesByID: {},
    deleteReactionStatusesByID: {},
};

function setDiscussionReaction(
    state: IDiscussionState,
    discussionID: IDiscussion["discussionID"],
    params: {
        removeReaction?: IReaction;
        addReaction?: IReaction;
    },
): IDiscussionState {
    const decrementBy = params.removeReaction?.reactionValue ?? 0;
    const incrementBy = params.addReaction?.reactionValue ?? 0;
    const newScore = state.discussionsByID[discussionID]!.score - decrementBy + incrementBy;
    state.discussionsByID[discussionID].score = newScore;
    state.discussionsByID[discussionID]!.reactions = state.discussionsByID[discussionID]!.reactions!.map(
        (reaction) => ({
            ...reaction,
            //assumes the user can only have one reaction to a discussion
            hasReacted: reaction.urlcode === params.addReaction?.urlcode ?? false,
        }),
    );
    return state;
}

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
        })
        .case(DiscussionActions.postDiscussionReactionACs.started, (state, params) => {
            const { discussionID, reaction: newReaction, currentReaction } = params;
            state.postReactionStatusesByID[discussionID] = { status: LoadStatus.PENDING };

            setDiscussionReaction(state, discussionID, {
                removeReaction: currentReaction,
                addReaction: newReaction,
            });

            return state;
        })
        .case(DiscussionActions.postDiscussionReactionACs.done, (state, payload) => {
            const { discussionID } = payload.params;

            state.postReactionStatusesByID[discussionID] = { status: LoadStatus.SUCCESS };

            return state;
        })
        .case(DiscussionActions.postDiscussionReactionACs.failed, (state, payload) => {
            const { discussionID, reaction, currentReaction } = payload.params;

            state.postReactionStatusesByID[discussionID] = { status: LoadStatus.ERROR, error: payload.error };

            setDiscussionReaction(state, discussionID, {
                removeReaction: reaction,
                addReaction: currentReaction,
            });

            return state;
        })
        .case(DiscussionActions.deleteDiscussionReactionACs.started, (state, params) => {
            const { discussionID, currentReaction } = params;

            state.deleteReactionStatusesByID[discussionID] = { status: LoadStatus.PENDING };

            setDiscussionReaction(state, discussionID, {
                removeReaction: currentReaction,
            });

            return state;
        })

        .case(DiscussionActions.deleteDiscussionReactionACs.done, (state, payload) => {
            const { discussionID } = payload.params;

            state.deleteReactionStatusesByID[discussionID] = { status: LoadStatus.SUCCESS };

            return state;
        })
        .case(DiscussionActions.deleteDiscussionReactionACs.failed, (state, payload) => {
            const { discussionID, currentReaction } = payload.params;

            state.deleteReactionStatusesByID[discussionID] = { status: LoadStatus.ERROR, error: payload.error };

            setDiscussionReaction(state, discussionID, {
                addReaction: currentReaction,
            });

            return state;
        }),
);
