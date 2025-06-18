/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import {
    IAiConversation,
    IAiConversationsApi,
    IPutMessageReactionResponse,
} from "@library/aiConversations/AiConversations.types";

const ENDPOINT = "/ai-conversations";

export const AiConversationsApi: IAiConversationsApi = {
    getConversations: async (params = {}) => {
        const response = await apiv2.get<IAiConversation[]>(ENDPOINT, { params });
        return response.data;
    },

    getConversation: async (params) => {
        const { conversationID } = params;
        const response = await apiv2.get<IAiConversation>(`${ENDPOINT}/${conversationID}`, { params });
        return response.data;
    },

    postNewConversation: async (params) => {
        const response = await apiv2.post<IAiConversation>(ENDPOINT, params);
        return response.data;
    },

    postConversationReply: async (params) => {
        const { conversationID, ...rest } = params;
        const response = await apiv2.post<IAiConversation>(`${ENDPOINT}/${conversationID}/reply`, rest);
        return response.data;
    },

    putMessageReaction: async (params) => {
        const { conversationID, ...rest } = params;
        const response = await apiv2.put<IPutMessageReactionResponse>(`${ENDPOINT}/${conversationID}/react`, rest);
        return response.data;
    },
};
