/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IApiError } from "@library/@types/api/core";
import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IUser } from "@library/@types/api/users";
import { Reaction } from "@dashboard/@types/api/reaction";

const createAction = actionCreatorFactory("@@discussions");

export interface IGetDiscussionByID {
    discussionID: number;
}

export type IPutDiscussionBookmarkedResult = Required<Pick<IDiscussion, "bookmarked">>;

export interface IPutDiscussionBookmarked extends IPutDiscussionBookmarkedResult {
    discussionID: IDiscussion["discussionID"];
}

export interface IPostDiscussionReaction {
    discussionID: IDiscussion["discussionID"];
    reactionType: Reaction;
}

export interface IPostDiscussionReactionResult {
    //fixme
}

export interface IDeleteDiscussionReaction {
    discussionID: IDiscussion["discussionID"];
}

export interface IDeleteDiscussionReactionResult {
    // fixme
}

export default class DiscussionActions extends ReduxActions {
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
        const { discussionID, reactionType } = query;
        const thunk = bindThunkAction(DiscussionActions.postDiscussionReactionACs, async () => {
            const reponse = await this.api.post(`/discussions/${discussionID}/reactions`, {
                reactionType,
            });
            return reponse.data;
        })({ discussionID, reactionType });
        return this.dispatch(thunk);
    };

    public static deleteDiscussionReactionACs = createAction.async<
        IDeleteDiscussionReaction,
        IDeleteDiscussionReactionResult,
        IApiError
    >("DELETE_DISCUSSION_REACTION");

    public deleteDiscussionReaction = (query: IDeleteDiscussionReaction) => {
        const { discussionID } = query;
        const thunk = bindThunkAction(DiscussionActions.deleteDiscussionReactionACs, async () => {
            const reponse = await this.api.delete(`/discussions/${discussionID}/reactions`);
            return reponse.data;
        })({ discussionID });
        return this.dispatch(thunk);
    };
}

export function useDiscussionActions() {
    return useReduxActions(DiscussionActions);
}
