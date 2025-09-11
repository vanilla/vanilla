/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { IApiError } from "@library/@types/api/core";
import { useAiConversationsApi } from "@library/aiConversations/AiConversations.context";
import {
    IGetConversationsParams,
    IGetConversationParams,
    IAiConversation,
    IPostNewConversationParams,
    IPostConversationReplyParams,
    IPutMessageReactionParams,
    IAskCommunityParams,
} from "@library/aiConversations/AiConversations.types";

export function useStartConversation() {
    const { postNewConversation } = useAiConversationsApi();

    const mutation = useMutation<IAiConversation, IApiError, IPostNewConversationParams>({
        mutationKey: ["postNewAiConversation"],
        mutationFn: async (params) => {
            return await postNewConversation(params);
        },
    });

    return mutation.mutateAsync;
}

export function useGetConversation(params: IGetConversationParams, shouldFetch: boolean = true) {
    const { getConversation } = useAiConversationsApi();

    const queryClient = useQueryClient();

    const query = useQuery<IAiConversation, IApiError>({
        queryKey: ["getAiConversation", params, params.conversationID],
        queryFn: async () => {
            const data = await getConversation(params);
            return data;
        },
        keepPreviousData: true,
        enabled: shouldFetch,
    });

    return {
        query,
        invalidate: async function () {
            await queryClient.invalidateQueries({ queryKey: ["getAiConversation", params, params.conversationID] });
        },
    };
}

export function useGetConversations(params: IGetConversationsParams, shouldFetch: boolean = true) {
    const { getConversations } = useAiConversationsApi();

    const query = useQuery<IAiConversation[], IApiError>({
        queryKey: ["getAiConversations", params],
        queryFn: async () => {
            const data = await getConversations(params);
            return data;
        },
        keepPreviousData: false,
        enabled: shouldFetch,
    });

    return query;
}

export function usePostReply() {
    const { postConversationReply } = useAiConversationsApi();

    const mutation = useMutation<IAiConversation, IApiError, IPostConversationReplyParams>({
        mutationKey: ["postReply"],
        mutationFn: async (params) => {
            return await postConversationReply(params);
        },
    });

    return mutation;
}

export function usePutMessageReaction() {
    const { putMessageReaction } = useAiConversationsApi();

    const mutation = useMutation({
        mutationKey: ["trackKeyword"],
        mutationFn: async (params: IPutMessageReactionParams) => {
            return await putMessageReaction(params);
        },
    });

    return mutation.mutateAsync;
}

export function useAskCommunity() {
    const { postAskCommunity } = useAiConversationsApi();

    const mutation = useMutation({
        mutationKey: ["askCommunity"],
        mutationFn: async (params: IAskCommunityParams) => {
            return await postAskCommunity(params);
        },
    });

    return mutation;
}
