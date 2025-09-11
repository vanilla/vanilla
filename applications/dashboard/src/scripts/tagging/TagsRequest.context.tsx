/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useTagsApiContext } from "@dashboard/tagging/TaggingSettings.context";
import { ITagsApi } from "@dashboard/tagging/Tags.api";
import { IApiError } from "@library/@types/api/core";
import { QueryKey, useQuery, useQueryClient } from "@tanstack/react-query";
import { logDebug } from "@vanilla/utils";
import isNil from "lodash-es/isNil";
import omitBy from "lodash-es/omitBy";
import { createContext, PropsWithChildren, useCallback, useContext, useState } from "react";

interface ITagsRequestContextValue {
    requestBody: Parameters<ITagsApi["getTags"]>[0];
    updateRequestBody: (updates: Partial<Parameters<ITagsApi["getTags"]>[0]>) => void;
    tagsQuery: ReturnType<typeof useQuery<Awaited<ReturnType<ITagsApi["getTags"]>>, IApiError>>;
    invalidate: () => Promise<void>;
}

export const TagsRequestContext = createContext<ITagsRequestContextValue | undefined>(undefined);

export function useTagsRequestContext(): ITagsRequestContextValue {
    const context = useContext(TagsRequestContext);
    if (!context) {
        logDebug("useTagsRequestContext must be used within a TagsRequestContextProvider");
        // AIDEV-NOTE: Fallback state to prevent undefined returns and allow graceful degradation
        return {
            requestBody: DEFAULT_REQUEST_BODY,
            updateRequestBody: () => {},
            tagsQuery: {
                data: undefined,
                error: null,
                isLoading: false,
                isError: false,
                isSuccess: false,
                isFetching: false,
                refetch: async () =>
                    ({
                        data: undefined,
                        error: null,
                        isLoading: false,
                        isError: false,
                        isSuccess: false,
                        isFetching: false,
                    } as any),
            } as any,
            invalidate: async () => {},
        };
    }
    return context;
}

const DEFAULT_REQUEST_BODY: ITagsRequestContextValue["requestBody"] = {
    page: 1,
    sort: "name",
};

interface ITagsRequestContextProviderProps {
    initialRequestBody?: Partial<ITagsRequestContextValue["requestBody"]>;
}

export function TagsRequestContextProvider({
    children,
    initialRequestBody = {},
}: PropsWithChildren<ITagsRequestContextProviderProps>) {
    const { api } = useTagsApiContext();
    const queryClient = useQueryClient();

    const [requestBody, setRequestBody] = useState<ITagsRequestContextValue["requestBody"]>({
        ...DEFAULT_REQUEST_BODY,
        ...omitBy(initialRequestBody, isNil),
    });

    const queryKey: QueryKey = ["getTags", requestBody];

    const tagsQuery = useQuery<Awaited<ReturnType<ITagsApi["getTags"]>>, IApiError>({
        queryKey,
        queryFn: async () => await api.getTags(requestBody),
        keepPreviousData: true,
    });

    const invalidate = useCallback(async () => {
        await queryClient.invalidateQueries({ queryKey });
    }, [queryClient, queryKey]);

    const updateRequestBody = useCallback((updates: Partial<typeof requestBody>) => {
        setRequestBody((prev) => {
            const shouldResetPage = Object.keys(updates).some((key) => key !== "page");
            const newRequestBody = { ...prev, ...updates };

            // Reset to page 1 if any field other than 'page' is being updated
            if (shouldResetPage && !updates.hasOwnProperty("page")) {
                newRequestBody.page = 1;
            }

            return newRequestBody;
        });
    }, []);

    const contextValue: ITagsRequestContextValue = {
        requestBody,
        updateRequestBody,

        tagsQuery,
        invalidate,
    };

    return <TagsRequestContext.Provider value={contextValue}>{children}</TagsRequestContext.Provider>;
}
