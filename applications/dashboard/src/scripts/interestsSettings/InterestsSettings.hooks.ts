/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    IInterest,
    IInterestResponse,
    InterestFormValues,
    InterestQueryParams,
} from "@dashboard/interestsSettings/Interests.types";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import { queryResultToILoadable } from "@library/ReactQueryUtils";
import { getMeta } from "@library/utility/appUtils";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { RecordID } from "@vanilla/utils";

/**
 * Toggle suggested content and interest mapping in the API
 */
export function useToggleSuggestedContent() {
    return useMutation<boolean, IApiError, boolean>({
        mutationKey: ["enableSuggestedContent"],
        mutationFn: async (enabled: boolean): Promise<boolean> => {
            const response = await apiv2.put<{ enabled: boolean }>("/interests/toggle-suggested-content", { enabled });
            return response.data.enabled ?? false;
        },
    });
}

/**
 * Get a list of interests
 */
export function useInterests(params?: InterestQueryParams) {
    const queryClient = useQueryClient();

    const queryKey = ["fetch-interests", params];

    const query = useQuery<any, IApiError, IInterestResponse>({
        queryKey,
        queryFn: async () => {
            const response = await apiv2.get<IInterest[]>("/interests", { params });
            const pagination = SimplePagerModel.parseHeaders(response.headers);

            return { interestsList: response.data, pagination };
        },
        enabled: getMeta("suggestedContentEnabled", false),
    });

    return {
        query: queryResultToILoadable(query),
        invalidate: async function () {
            await queryClient.invalidateQueries(["fetch-interests"]);
        },
    };
}

/**
 * Save interest data to the API
 */
export function useSaveInterest() {
    return useMutation<IInterest, IApiError, InterestFormValues>({
        mutationKey: ["saveInterest"],
        mutationFn: async (formValues: InterestFormValues): Promise<IInterest> => {
            const interest = {
                apiName: formValues.apiName,
                name: formValues.name,
                categoryIDs: formValues.categoryIDs,
                tagIDs: formValues.tagIDs,
                isDefault: formValues.isDefault,
                ...(!formValues.isDefault && { profileFieldMapping: formValues.profileFieldMapping }),
            };

            if (formValues.interestID) {
                const response = await apiv2.patch<IInterest>(`/interests/${formValues.interestID}`, interest);
                return response.data;
            } else {
                const response = await apiv2.post<IInterest>("/interests", interest);
                return response.data;
            }
        },
    });
}

/**
 * Delete an interest
 */
export function useDeleteInterest(interestID: RecordID) {
    return useMutation({
        mutationKey: ["delete-interest"],
        mutationFn: async () => {
            const response = await apiv2.delete(`/interests/${interestID}`);
            return response.data;
        },
    });
}
