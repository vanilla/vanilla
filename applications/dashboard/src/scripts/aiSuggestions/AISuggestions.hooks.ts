/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AISuggestionsSettings, AISuggestionsSettingsForm } from "@dashboard/aiSuggestions/AISuggestions.types";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { getMeta } from "@library/utility/appUtils";
import { UseMutationResult, UseQueryResult, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import set from "lodash-es/set";
import { useMemo } from "react";

const SETTINGS_ENDPOINT = "/ai-suggestions/settings";

/**
 * Check that required dependencies have been enabled
 */
export function useDependenciesEnabled(): boolean {
    const allEnabled = useMemo<boolean>(() => {
        // Check that `question` is a valid post type
        const qnaEnabled = getMeta("postTypes", []).includes("question");

        return qnaEnabled;
    }, []);

    return allEnabled;
}

/**
 * Get suggestions settings from configuration
 */
export function useAISuggestionsSettings(): UseQueryResult<AISuggestionsSettings, IApiError> {
    return useQuery({
        queryKey: ["aiSuggestedAnswerSettings"],
        queryFn: async () => {
            const response = await apiv2.get<AISuggestionsSettings>(SETTINGS_ENDPOINT);
            return response.data;
        },
    });
}

/**
 * Save suggestions settings to configuration
 */
export function useSaveAISuggestionsSettings(): UseMutationResult<AISuggestionsSettings, IApiError> {
    const queryClient = useQueryClient();

    return useMutation({
        mutationKey: ["saveAISuggestedAnswerSettings"],
        mutationFn: async (formValues: AISuggestionsSettingsForm): Promise<AISuggestionsSettings> => {
            const { sources: formSources, ...values } = formValues;
            const sources: AISuggestionsSettings["sources"] = {};

            formSources.enabled.forEach((sourceID) => {
                set(sources, `${sourceID}.enabled`, true);
            });

            Object.entries(formSources.exclusions).forEach(([sourceID, exclusionIDs]) => {
                set(sources, `${sourceID}`, {
                    enabled: formSources.enabled.includes(sourceID),
                    exclusionIDs,
                });
            });

            const params = {
                ...values,
                sources,
            };

            const response = await apiv2.patch<AISuggestionsSettings>(SETTINGS_ENDPOINT, params);
            return response.data;
        },
        onSuccess: (data: AISuggestionsSettings) => {
            queryClient.setQueryData(["aiSuggestedAnswerSettings"], data);
        },
    });
}
