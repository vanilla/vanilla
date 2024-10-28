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
import { getMeta, t } from "@library/utility/appUtils";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { RecordID } from "@vanilla/utils";
import { AxiosResponse } from "axios";

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
    return useQuery<any, IApiError, IInterestResponse>({
        queryKey: ["fetch-interests", params],
        queryFn: async () => {
            const response = await apiv2.get<IInterest[]>("/interests", { params });
            const pagination = SimplePagerModel.parseHeaders(response.headers);

            return { interestsList: response.data, pagination };
        },
        enabled: getMeta("suggestedContentEnabled", false),
    });
}

/**
 * Save interest data to the API
 */
export function useSaveInterest(params?: InterestQueryParams) {
    const queryClient = useQueryClient();

    return useMutation<IInterest, IApiError, InterestFormValues>({
        mutationKey: ["saveInterest"],
        mutationFn: async (formValues: InterestFormValues): Promise<IInterest> => {
            const profileFieldMapping: Record<string, string[]> | undefined = formValues.profileFields
                ? Object.fromEntries(
                      formValues.profileFields.map((profileFieldApiName) => {
                          return [profileFieldApiName, [formValues[profileFieldApiName]]];
                      }),
                  )
                : undefined;

            const interest = {
                apiName: formValues.apiName,
                name: formValues.name,
                categoryIDs: formValues.categoryIDs,
                tagIDs: formValues.tagIDs,
                isDefault: formValues.isDefault,
                ...(!formValues.isDefault && { profileFieldMapping }),
            };

            let response: AxiosResponse<IInterest>;

            if (formValues.interestID) {
                response = await apiv2.patch<IInterest>(`/interests/${formValues.interestID}`, interest);
            } else {
                response = await apiv2.post<IInterest>("/interests", interest);
            }

            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries(["fetch-interests", params]);
        },
    });
}

/**
 * Get the form's values
 */

const INITIAL_FORM_VALUES: InterestFormValues = {
    apiName: "",
    name: "",
    profileFields: [],
    categoryIDs: [],
    tagIDs: [],
    isDefault: false,
};

export function getInterestFormValues(interest?: IInterest): InterestFormValues {
    if (!interest) {
        return INITIAL_FORM_VALUES;
    }

    const profileFieldMapping = Object.fromEntries(
        (interest.profileFields ?? []).map((field) => [field.apiName, field.mappedValue]),
    );

    return {
        interestID: interest.interestID,
        apiName: interest.apiName,
        name: interest.name,
        isDefault: interest.isDefault ?? false,
        profileFields: Object.keys(interest.profileFieldMapping ?? {}),
        categoryIDs: interest.categoryIDs ?? [],
        tagIDs: interest.tagIDs ?? [],
        ...profileFieldMapping,
    };
}

/**
 * Delete an interest
 */
export function useDeleteInterest(params?: InterestQueryParams) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationKey: ["delete-interest"],
        mutationFn: async (interestID: RecordID) => {
            const response = await apiv2.delete(`/interests/${interestID}`);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries(["fetch-interests", params]);
        },
    });
}
