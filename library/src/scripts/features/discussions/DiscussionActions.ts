/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IApiError } from "@library/@types/api/core";
import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IDiscussion, IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { IUser } from "@library/@types/api/users";
import { IReaction } from "@dashboard/@types/api/reaction";

const createAction = actionCreatorFactory("@@discussions");

export interface IGetDiscussionByID {
    discussionID: number;
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

export interface IPutDiscussionType {
    discussionID: IDiscussion["discussionID"];
    type: string;
}

export default class DiscussionActions extends ReduxActions {
    public static getDiscussionListACs = createAction.async<IGetDiscussionListParams, IDiscussion[], IApiError>(
        "GET_DISCUSSION_LIST",
    );

    public getDiscussionList = (params: IGetDiscussionListParams) => {
        const thunk = bindThunkAction(DiscussionActions.getDiscussionListACs, async () => {
            const reponse = await this.api.get(`/discussions`, {
                params: {
                    ...params,
                    expand: ["insertUser", "breadcrumbs"],
                },
            });
            return reponse.data;
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
}

export function useDiscussionActions() {
    return useReduxActions(DiscussionActions);
}
