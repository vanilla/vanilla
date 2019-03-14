/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { ActionsUnion } from "@library/redux/ReduxActions";
import { IConversation, IGetConversationsRequest } from "@library/@types/api/conversations";

/**
 * Redux actions for the current user's conversations.
 */
export default class ConversationsActions extends ReduxActions {
    public static readonly GET_CONVERSATIONS_REQUEST = "@@conversations/GET_CONVERSATIONS_REQUEST";
    public static readonly GET_CONVERSATIONS_RESPONSE = "@@conversations/GET_CONVERSATIONS_RESPONSE";
    public static readonly GET_CONVERSATIONS_ERROR = "@@conversations/GET_CONVERSATIONS_ERROR";

    /**
     * Action creators for getting a paginated list of the current user's conversations.
     */
    public static getConversationsACs = ReduxActions.generateApiActionCreators(
        ConversationsActions.GET_CONVERSATIONS_REQUEST,
        ConversationsActions.GET_CONVERSATIONS_RESPONSE,
        ConversationsActions.GET_CONVERSATIONS_ERROR,
        {} as IConversation[],
        {},
    );

    /**
     * Union of all possible action types in this class.
     */
    public static readonly ACTION_TYPES: ActionsUnion<typeof ConversationsActions.getConversationsACs>;

    /**
     * Get a paginated list of conversations for the current user.
     */
    public getConversations = (params: IGetConversationsRequest = {}) => {
        return this.dispatchApi("get", "/conversations", ConversationsActions.getConversationsACs, params);
    };
}
