/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PropsWithChildren, createContext, useContext } from "react";
import { IAiConversationsApi } from "@library/aiConversations/AiConversations.types";
import { AiConversationsApi } from "@library/aiConversations/AiConversations.api";

interface IAiConversationsApiContextValue {
    api: IAiConversationsApi;
}

const AiConversationsApiContext = createContext<IAiConversationsApiContextValue>({
    api: AiConversationsApi,
});

export function useAiConversationsApi() {
    return useContext(AiConversationsApiContext).api;
}

export function useAIConversations() {
    return useContext(AiConversationsApiContext);
}

export function AiConversationsApiProvider({
    api = AiConversationsApi,
    children,
}: PropsWithChildren<{
    api?: IAiConversationsApi;
}>) {
    return (
        <AiConversationsApiContext.Provider
            value={{
                ...{
                    api,
                },
            }}
        >
            {children}
        </AiConversationsApiContext.Provider>
    );
}
