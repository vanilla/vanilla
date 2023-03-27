/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IApiError } from "@library/@types/api/core";
import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IDiscussion, IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { IReaction } from "@dashboard/@types/api/reaction";
import { ITag } from "@library/features/tags/TagsReducer";
import { RecordID } from "@vanilla/utils";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import intersection from "lodash/intersection";
import { IForumStoreState } from "@vanilla/addon-vanilla/redux/state";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { IDiscussionsStoreState } from "@library/features/discussions/discussionsReducer";
import SimplePagerModel, { ILinkPages } from "@library/navigation/SimplePagerModel";

const createAction = actionCreatorFactory("@@discussions");

export interface IGetDiscussionByID {
    discussionID: IDiscussion["discussionID"];
}
export interface IGetCategoryByID {
    categoryID: RecordID;
}
export interface IGetDiscussionsByIDs {
    discussionIDs: Array<IDiscussion["discussionID"]>;
    limit?: number;
    expand?: string | string[];
}

export type IPutDiscussionBookmarkedResult = Required<Pick<IDiscussion, "bookmarked">>;

export interface IPutDiscussionBookmarked extends IPutDiscussionBookmarkedResult {
    discussionID: IDiscussion["discussionID"];
}

export interface IAnnounceDiscussionParams {
    pinned: IDiscussion["pinned"];
    pinLocation: IDiscussion["pinLocation"];
}
export interface IMoveDiscussionParams {
    categoryID: IDiscussion["categoryID"];
}

export interface ICloseDiscussionParams {
    closed?: IDiscussion["closed"];
}

export interface ISinkDiscussionParams {
    sink?: IDiscussion["sink"];
}

export interface IResolveDiscussionParams {
    resolved?: IDiscussion["resolved"];
}

// this interface should extend every set of parameters that is accepted in the patch discussion endpoint
interface IPatchDiscussionRequest
    extends Partial<
        IAnnounceDiscussionParams &
            IMoveDiscussionParams &
            ICloseDiscussionParams &
            ISinkDiscussionParams &
            IResolveDiscussionParams
    > {
    patchStatusID?: string;
    discussionID: IDiscussion["discussionID"];
}

export type IPatchDiscussionResult = IDiscussion;

export interface IPostDiscussionReaction {
    discussionID: IDiscussion["discussionID"];
    reaction: IReaction;
    currentReaction?: IReaction;
}

type IPostDiscussionReactionResult = IReaction[];

export interface IDeleteDiscussionReaction {
    discussionID: IDiscussion["discussionID"];
    currentReaction: IReaction;
}

export interface IDeleteDiscussion {
    discussionID: IDiscussion["discussionID"];
}
export interface IBulkDeleteDiscussion {
    discussionIDs: Array<IDiscussion["discussionID"]>;
}

export interface IBulkMoveDiscussions {
    discussionIDs: Array<IDiscussion["discussionID"]>;
    categoryID: RecordID;
    addRedirects: boolean;
    category?: ICategoryFragment;
}

export interface IBulkCloseDiscussions {
    discussionIDs: Array<IDiscussion["discussionID"]>;
    closed: boolean;
}

export interface IBulkActionSyncResult {
    callbackPayload: string | null;
    progress: {
        countTotalIDs: number;
        exceptionsByID: Record<string | number, any>;
        failedIDs: RecordID[];
        successIDs: RecordID[];
    };
}

export interface IPutDiscussionType {
    discussionID: IDiscussion["discussionID"];
    type: string;
}
export interface IPutDiscussionTags {
    discussionID: IDiscussion["discussionID"];
    tagIDs: number[];
}

export interface IGetDiscussionsListResponse {
    data: IDiscussion[];
    pagination?: ILinkPages;
}

export default class DiscussionActions extends ReduxActions<
    IForumStoreState & ICoreStoreState & IDiscussionsStoreState
> {
    public static getDiscussionListACs = createAction.async<
        IGetDiscussionListParams,
        IGetDiscussionsListResponse,
        IApiError
    >("GET_DISCUSSION_LIST");

    public getDiscussionList = (params: IGetDiscussionListParams) => {
        const thunk = bindThunkAction(DiscussionActions.getDiscussionListACs, async () => {
            const defaultExpand = ["insertUser", "breadcrumbs"];
            const response = await this.api.get(`/discussions`, {
                params: {
                    ...params,
                    expand:
                        params.expand && params.expand.length ? [...defaultExpand, ...params.expand] : defaultExpand,
                },
            });
            const pagination = SimplePagerModel.parseHeaders(response.headers);
            return {
                data: response.data,
                pagination,
            };
        })(params);
        return this.dispatch(thunk);
    };

    public static getDiscussionByIDACs = createAction.async<IGetDiscussionByID, IDiscussion, IApiError>(
        "GET_DISCUSSION",
    );

    public getDiscussionByID = (query: IGetDiscussionByID) => {
        const { discussionID } = query;
        const thunk = bindThunkAction(DiscussionActions.getDiscussionByIDACs, async () => {
            const reponse = await this.api.get(`/discussions/${discussionID}`, {
                params: {
                    expand: ["insertUser", "breadcrumbs"],
                },
            });
            return reponse.data;
        })({ discussionID });
        return this.dispatch(thunk);
    };

    public static patchDiscussionACs = createAction.async<IPatchDiscussionRequest, IPatchDiscussionResult, IApiError>(
        "PATCH_DISCUSSION",
    );

    public patchDiscussion = (_query: IPatchDiscussionRequest) => {
        const { discussionID, patchStatusID, ...query } = _query;
        const requestConfig = {
            params: {
                expand: ["category"],
            },
        };
        const thunk = bindThunkAction(DiscussionActions.patchDiscussionACs, async () => {
            const reponse = await this.api.patch(`/discussions/${discussionID}`, query, requestConfig);
            return reponse.data;
        })(_query);
        return this.dispatch(thunk);
    };

    public static putDiscussionTypeACs = createAction.async<IPutDiscussionType, IDiscussion, IApiError>(
        "PUT_DISCUSSION_TYPE",
    );

    public putDiscussionType = (query: IPutDiscussionType) => {
        const { discussionID, type } = query;
        const thunk = bindThunkAction(DiscussionActions.putDiscussionTypeACs, async () => {
            const reponse = await this.api.put(`/discussions/${discussionID}/type`, {
                type,
            });
            return reponse.data;
        })({ discussionID, type });
        return this.dispatch(thunk);
    };

    public static putDiscussionBookmarkedACs = createAction.async<
        IPutDiscussionBookmarked,
        IPutDiscussionBookmarkedResult,
        IApiError
    >("PUT_DISCUSSION_BOOKMARKED");

    public putDiscussionBookmarked = (query: IPutDiscussionBookmarked) => {
        const { discussionID, bookmarked } = query;
        const thunk = bindThunkAction(DiscussionActions.putDiscussionBookmarkedACs, async () => {
            const reponse = await this.api.put(`/discussions/${discussionID}/bookmark`, {
                bookmarked,
            });
            return reponse.data;
        })({ discussionID, bookmarked });
        return this.dispatch(thunk);
    };

    public static postDiscussionReactionACs = createAction.async<
        IPostDiscussionReaction,
        IPostDiscussionReactionResult,
        IApiError
    >("POST_DISCUSSION_REACTION");

    public postDiscussionReaction = (query: IPostDiscussionReaction) => {
        const { discussionID, reaction } = query;
        const thunk = bindThunkAction(DiscussionActions.postDiscussionReactionACs, async () => {
            const reponse = await this.api.post(`/discussions/${discussionID}/reactions`, {
                reactionType: reaction.urlcode,
            });
            return reponse.data;
        })(query);
        return this.dispatch(thunk);
    };

    public static deleteDiscussionReactionACs = createAction.async<IDeleteDiscussionReaction, {}, IApiError>(
        "DELETE_DISCUSSION_REACTION",
    );

    public deleteDiscussionReaction = (query: IDeleteDiscussionReaction) => {
        const { discussionID } = query;
        const thunk = bindThunkAction(DiscussionActions.deleteDiscussionReactionACs, async () => {
            const reponse = await this.api.delete(`/discussions/${discussionID}/reactions`);
            return reponse.data;
        })(query);
        return this.dispatch(thunk);
    };

    public static deleteDiscussionACs = createAction.async<IDeleteDiscussion, {}, IApiError>("DELETE_DISCUSSION");

    public deleteDiscussion = (query: IDeleteDiscussion) => {
        const { discussionID } = query;

        const deleteDiscussionApi = async () => {
            const reponse = await this.api.delete(`/discussions/${discussionID}`);
            return reponse.data;
        };

        const thunk = bindThunkAction(DiscussionActions.deleteDiscussionACs, deleteDiscussionApi)({ discussionID });
        return this.dispatch(thunk);
    };

    public static bulkDeleteDiscussionsACs = createAction.async<
        IBulkDeleteDiscussion,
        IBulkActionSyncResult,
        IApiError
    >("BULK_DELETE_DISCUSSIONS");

    public bulkDeleteDiscussion = (query: IBulkDeleteDiscussion) => {
        const { discussionIDs } = query;

        const deleteDiscussionListApi = async () => {
            const reponse = await this.api.delete(`/discussions/list`, {
                params: { longRunnerMode: "sync" },
                data: { discussionIDs },
            });
            return reponse.data;
        };

        const thunk = bindThunkAction(
            DiscussionActions.bulkDeleteDiscussionsACs,
            deleteDiscussionListApi,
        )({ discussionIDs });
        return this.dispatch(thunk);
    };

    public static putDiscussionTagsACs = createAction.async<IPutDiscussionTags, ITag[], IApiError>(
        "PUT_DISCUSSION_TAGS",
    );

    public putDiscussionTags = (query: IPutDiscussionTags) => {
        const { discussionID, tagIDs } = query;
        const thunk = bindThunkAction(DiscussionActions.putDiscussionTagsACs, async () => {
            const reponse = await this.api.put(`/discussions/${discussionID}/tags`, {
                tagIDs,
            });
            return reponse.data;
        })({ discussionID, tagIDs });
        return this.dispatch(thunk);
    };

    public static getDiscussionsByIDsAC = createAction.async<IGetDiscussionsByIDs, IDiscussion[], IApiError>(
        "GET_DISCUSSIONS_BY_ID",
    );

    public getDiscussionByIDs = (query: IGetDiscussionsByIDs, onlyExisting: boolean = false) => {
        let { discussionIDs } = query;
        if (!query.limit) {
            query.limit = query.discussionIDs.length;
        }
        if (onlyExisting) {
            const existingIDs = Object.keys(this.getState().discussions.discussionsByID).map((key) => parseInt(key));
            discussionIDs = intersection(discussionIDs, existingIDs);
        }
        // No discussions to fetch.
        if (discussionIDs.length === 0) {
            return Promise.resolve([]);
        }
        const thunk = bindThunkAction(DiscussionActions.getDiscussionsByIDsAC, async () => {
            const response = await this.api.get(`/discussions`, {
                params: {
                    ...query,
                    // Name improperly up until this point.
                    discussionID: query.discussionIDs,
                },
            });
            return response.data;
        })(query);
        return this.dispatch(thunk);
    };

    public static bulkMoveDiscussionsACs = createAction.async<IBulkMoveDiscussions, IBulkActionSyncResult, IApiError>(
        "BULK_MOVE_DISCUSSIONS",
    );

    public bulkMoveDiscussions = (query: IBulkMoveDiscussions) => {
        const { discussionIDs, categoryID, addRedirects, category } = query;

        const moveDiscussionsApi = async () => {
            const reponse = await this.api.patch(
                `discussions/move`,
                {
                    discussionIDs,
                    categoryID,
                    addRedirects,
                },
                {
                    params: { longRunnerMode: "sync" },
                },
            );
            return reponse.data;
        };

        const thunk = bindThunkAction(
            DiscussionActions.bulkMoveDiscussionsACs,
            moveDiscussionsApi,
        )({ discussionIDs, categoryID, addRedirects, category });
        return this.dispatch(thunk);
    };

    public static bulkCloseDiscussionsACs = createAction.async<IBulkCloseDiscussions, IBulkActionSyncResult, IApiError>(
        "BULK_CLOSE_DISCUSSIONS",
    );

    public bulkCloseDiscussions = (query: IBulkCloseDiscussions) => {
        const { discussionIDs, closed } = query;

        const closeDiscussionsApi = async () => {
            const response = await this.api.patch(
                `discussions/close`,
                { discussionIDs, closed },
                { params: { longRunnerMode: "sync" } },
            );
            return response.data;
        };

        const thunk = bindThunkAction(
            DiscussionActions.bulkCloseDiscussionsACs,
            closeDiscussionsApi,
        )({ discussionIDs, closed });
        return this.dispatch(thunk);
    };

    public static getCategoryByIDACs = createAction.async<IGetCategoryByID, ICategoryFragment, IApiError>(
        "GET_CATEGORY",
    );

    public getCategoryByID = (query: IGetCategoryByID) => {
        const { categoryID } = query;
        const thunk = bindThunkAction(DiscussionActions.getCategoryByIDACs, async () => {
            const reponse = await this.api.get(`/categories/${categoryID}`);
            return reponse.data;
        })({ categoryID });
        return this.dispatch(thunk);
    };
}

export function useDiscussionActions() {
    return useReduxActions(DiscussionActions);
}
