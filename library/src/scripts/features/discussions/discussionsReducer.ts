/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import DiscussionActions from "@library/features/discussions/DiscussionActions";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { RecordID, stableObjectHash } from "@vanilla/utils";
import { IReaction } from "@dashboard/@types/api/reaction";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import difference from "lodash/difference";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
export interface IDiscussionsStoreState {
    discussions: IDiscussionState;
}
interface IDiscussionState {
    discussionsByID: Record<RecordID, IDiscussion>;
    discussionIDsByParamHash: Record<
        RecordID,
        ILoadable<{
            discussions: Array<IDiscussion["discussionID"]>;
            pagination?: ILinkPages;
        }>
    >;
    fullRecordStatusesByID: Record<number, ILoadable>;
    bookmarkStatusesByID: Record<number, ILoadable>;
    changeTypeByID: Record<number, ILoadable>;
    patchStatusByPatchID: Record<string, ILoadable>;
    deleteStatusesByID: Record<number, ILoadable>;
    postReactionStatusesByID: Record<number, ILoadable<{}>>;
    deleteReactionStatusesByID: Record<number, ILoadable<{}>>;
    putTagsByID: Record<number, ILoadable<{}>>;
    categoriesStatusesByID: Record<number, ILoadable>;
    categoriesByID: Record<RecordID, ICategoryFragment>;
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
    putTagsByID: {},
    categoriesStatusesByID: {},
    categoriesByID: {},
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
            const { discussionID, bookmarked } = params;
            state.bookmarkStatusesByID[discussionID] = { status: LoadStatus.LOADING };
            // Set bookmark optimistically
            state.discussionsByID[discussionID] = {
                ...state.discussionsByID[discussionID],
                bookmarked: bookmarked,
            };
            return state;
        })
        .case(DiscussionActions.putDiscussionBookmarkedACs.failed, (state, payload) => {
            const { discussionID } = payload.params;
            state.bookmarkStatusesByID[discussionID] = { status: LoadStatus.ERROR, error: payload.error };
            state.discussionsByID[discussionID] = {
                ...state.discussionsByID[discussionID],
                bookmarked: !state.discussionsByID[discussionID].bookmarked,
            };
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
                data: {
                    discussions: payload.result.data.map(({ discussionID }) => discussionID),
                    pagination: payload.result.pagination,
                },
            };
            payload.result.data.forEach((discussion) => {
                state.discussionsByID[discussion.discussionID] = {
                    ...state.discussionsByID[discussion.discussionID],
                    ...discussion,
                };
                const categoryID = discussion.category?.categoryID;
                if (categoryID && discussion.category) {
                    state.categoriesByID = {
                        ...state.categoriesByID,
                        [categoryID]: discussion.category,
                    };
                }
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
                if (state.discussionIDsByParamHash[paramHash].data?.discussions !== undefined) {
                    state.discussionIDsByParamHash[paramHash].data!.discussions = state.discussionIDsByParamHash[
                        paramHash
                    ].data!.discussions.filter((key) => key !== discussionID);
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
        })
        .case(DiscussionActions.putDiscussionTagsACs.started, (state, params) => {
            const { discussionID } = params;
            state.putTagsByID[discussionID] = { status: LoadStatus.LOADING };
            return state;
        })
        .case(DiscussionActions.putDiscussionTagsACs.done, (state, payload) => {
            const { discussionID } = payload.params;

            state.putTagsByID[discussionID] = {
                status: LoadStatus.SUCCESS,
            };
            state.discussionsByID[discussionID] = {
                ...state.discussionsByID[discussionID],
                tags: payload.result,
            };

            return state;
        })
        .case(DiscussionActions.putDiscussionTagsACs.failed, (state, payload) => {
            const { discussionID } = payload.params;

            state.putTagsByID[discussionID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };

            return state;
        })
        .case(DiscussionActions.getDiscussionsByIDsAC.started, (state, params) => {
            const { discussionIDs } = params;
            const newState = Object.fromEntries(discussionIDs.map((ID) => [ID, { status: LoadStatus.LOADING }]));
            state.fullRecordStatusesByID = {
                ...state.fullRecordStatusesByID,
                ...newState,
            };
            return state;
        })
        .case(DiscussionActions.getDiscussionsByIDsAC.failed, (state, payload) => {
            const { discussionIDs } = payload.params;

            const newState = Object.fromEntries(
                discussionIDs.map((ID) => [
                    ID,
                    {
                        status: LoadStatus.ERROR,
                        error: payload.error,
                    },
                ]),
            );
            state.fullRecordStatusesByID = {
                ...state.fullRecordStatusesByID,
                ...newState,
            };
            return state;
        })
        .case(DiscussionActions.getDiscussionsByIDsAC.done, (state, payload) => {
            const { limit, discussionIDs } = payload.params;

            const newStatus = Object.fromEntries(discussionIDs.map((ID) => [ID, { status: LoadStatus.SUCCESS }]));
            state.fullRecordStatusesByID = {
                ...state.fullRecordStatusesByID,
                ...newStatus,
            };

            // Merge the new records into the old ones.
            payload.result.forEach((newDiscussion) => {
                const existingDiscussion = state.discussionsByID[newDiscussion.discussionID] ?? {};
                state.discussionsByID[newDiscussion.discussionID] = {
                    ...existingDiscussion,
                    ...newDiscussion,
                };
            });

            // Remove discussions that were fetched but could not be found.
            const resultIDs = payload.result.map((result) => result.discussionID);
            if (limit && resultIDs.length < limit) {
                // Some discussions were deleted.
                const missingIDs = difference(discussionIDs, resultIDs);
                missingIDs.forEach((missingID) => {
                    delete state.discussionsByID[missingID];
                });
            }

            return state;
        })
        .case(DiscussionActions.bulkDeleteDiscussionsACs.started, (state, params) => {
            const { discussionIDs } = params;
            discussionIDs.forEach((ID) => {
                state.deleteStatusesByID[ID] = { status: LoadStatus.LOADING };
            });
            return state;
        })
        .case(DiscussionActions.bulkDeleteDiscussionsACs.failed, (state, payload) => {
            const { discussionIDs } = payload.params;
            discussionIDs.forEach((ID) => {
                state.deleteStatusesByID[ID] = { status: LoadStatus.ERROR, error: payload.error };
            });
            return state;
        })
        .case(DiscussionActions.bulkDeleteDiscussionsACs.done, (state, payload) => {
            const { failedIDs, exceptionsByID, successIDs } = payload.result.progress;

            if (failedIDs && failedIDs.length > 0) {
                failedIDs.forEach((ID) => {
                    state.deleteStatusesByID[ID] = {
                        status: LoadStatus.ERROR,
                        error: exceptionsByID[ID],
                    };
                });
            }

            if (successIDs && successIDs.length > 0) {
                successIDs.forEach((ID) => {
                    state.deleteStatusesByID[ID] = {
                        status: LoadStatus.SUCCESS,
                    };
                    delete state.discussionsByID[ID];
                });

                Object.keys(state.discussionIDsByParamHash).forEach((paramHash) => {
                    if (state.discussionIDsByParamHash[paramHash].data?.discussions !== undefined) {
                        state.discussionIDsByParamHash[paramHash].data!.discussions = state.discussionIDsByParamHash[
                            paramHash
                        ].data!.discussions.filter((key) => !successIDs.includes(key));
                    }
                });
            }

            return state;
        })
        .case(DiscussionActions.bulkMoveDiscussionsACs.started, (state, params) => {
            const { discussionIDs } = params;

            discussionIDs.forEach((ID) => {
                state.patchStatusByPatchID[`${ID}-move`] = { status: LoadStatus.LOADING };
            });
            return state;
        })
        .case(DiscussionActions.bulkMoveDiscussionsACs.failed, (state, payload) => {
            const { discussionIDs } = payload.params;

            discussionIDs.forEach((ID) => {
                state.patchStatusByPatchID[`${ID}-move`] = { status: LoadStatus.ERROR, error: payload.error };
            });
            return state;
        })
        .case(DiscussionActions.bulkMoveDiscussionsACs.done, (state, payload) => {
            const { category } = payload.params;
            const { failedIDs, exceptionsByID, successIDs } = payload.result.progress;

            if (failedIDs && failedIDs.length > 0) {
                failedIDs.forEach((ID) => {
                    state.patchStatusByPatchID[`${ID}-move`] = {
                        status: LoadStatus.ERROR,
                        error: exceptionsByID[ID],
                    };
                });
            }

            if (successIDs && successIDs.length > 0) {
                successIDs.forEach((ID) => {
                    state.patchStatusByPatchID[`${ID}-move`] = {
                        status: LoadStatus.SUCCESS,
                    };
                    state.discussionsByID[ID] = {
                        ...state.discussionsByID[ID],
                        categoryID: Number(payload.params.categoryID),
                    };
                    if (category) {
                        state.discussionsByID[ID] = {
                            ...state.discussionsByID[ID],
                            category: {
                                ...state.discussionsByID[ID].category,
                                ...category,
                            },
                        };
                    }
                });
            }

            return state;
        })
        .case(DiscussionActions.bulkCloseDiscussionsACs.started, (state, params) => {
            const { discussionIDs } = params;
            discussionIDs.forEach((ID) => {
                state.patchStatusByPatchID[`${ID}-close`] = {
                    status: LoadStatus.LOADING,
                };
            });
            return state;
        })
        .case(DiscussionActions.bulkCloseDiscussionsACs.failed, (state, payload) => {
            const { discussionIDs } = payload.params;
            discussionIDs.forEach((ID) => {
                state.patchStatusByPatchID[`${ID}-close`] = {
                    status: LoadStatus.ERROR,
                    error: payload.error,
                };
            });
            return state;
        })
        .case(DiscussionActions.bulkCloseDiscussionsACs.done, (state, payload) => {
            const { failedIDs, exceptionsByID, successIDs } = payload.result.progress;

            if (failedIDs && failedIDs.length > 0) {
                failedIDs.forEach((ID) => {
                    state.patchStatusByPatchID[`${ID}-close`] = {
                        status: LoadStatus.ERROR,
                        error: exceptionsByID[ID],
                    };
                });
            }

            if (successIDs && successIDs.length > 0) {
                successIDs.forEach((ID) => {
                    state.patchStatusByPatchID[`${ID}-close`] = {
                        status: LoadStatus.SUCCESS,
                    };
                    state.discussionsByID[ID] = {
                        ...state.discussionsByID[ID],
                        closed: true,
                    };
                });
            }

            return state;
        })
        .case(DiscussionActions.getCategoryByIDACs.started, (state, params) => {
            const { categoryID } = params;
            state.categoriesStatusesByID[categoryID] = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(DiscussionActions.getCategoryByIDACs.failed, (state, payload) => {
            const { categoryID } = payload.params;
            state.categoriesStatusesByID[categoryID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(DiscussionActions.getCategoryByIDACs.done, (state, payload) => {
            const { categoryID } = payload.params;
            state.categoriesStatusesByID[categoryID] = {
                status: LoadStatus.SUCCESS,
            };
            state.categoriesByID[categoryID] = payload.result;
            return state;
        }),
);
