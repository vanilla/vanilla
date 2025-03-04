/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DeveloperProfilesApi } from "@dashboard/developer/profileViewer/DeveloperProfiles.api";
import { useToast, useToastErrorHandler } from "@library/features/toaster/ToastContext";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { downloadAsFile } from "@vanilla/dom-utils";

export type DeveloperProfileSort = "dateRecorded" | "-dateRecorded" | "requestElapsedMs" | "-requestElapsedMs";

export const DeveloperProfileSortOptions: ISelectBoxItem[] = [
    {
        value: "-dateRecorded",
        name: "Newest",
    },
    {
        value: "dateRecorded",
        name: "Oldest",
    },
    {
        value: "-requestElapsedMs",
        name: "Slowest",
    },
    {
        value: "requestElapsedMs",
        name: "Fastest",
    },
];

export function useDeveloperProfilesQuery(query: DeveloperProfilesApi.IndexQuery) {
    return useQuery({
        queryKey: ["developerProfiles", "index", query],
        queryFn: async () => {
            return await DeveloperProfilesApi.index(query);
        },
        refetchInterval: false,
        refetchOnMount: false,
    });
}

export function useDeveloperProfileDetailsQuery(profileID: number) {
    return useQuery({
        queryKey: ["developerProfiles", "byID", profileID],
        queryFn: async () => {
            return await DeveloperProfilesApi.details(profileID);
        },
        refetchInterval: false,
        refetchOnMount: false,
    });
}

export function useDownloadDetailsMutation() {
    return useMutation({
        mutationKey: ["developerProfiles", "download"],
        mutationFn: async (profileID: number) => {
            const details = await DeveloperProfilesApi.details(profileID);
            downloadAsFile(JSON.stringify(details), `developer-profile-${profileID}.trace`, {
                fileExtension: "json",
            });
        },
    });
}

export function usePatchDeveloperProfileMutation(options?: { onSuccess?: () => void }) {
    const queryClient = useQueryClient();
    const errorHandler = useToastErrorHandler();
    return useMutation({
        mutationKey: ["developerProfiles", "download"],
        mutationFn: async (params: DeveloperProfilesApi.PatchParams) => {
            const details = await DeveloperProfilesApi.patch(params);
            await queryClient.invalidateQueries(["developerProfiles", "byID"]);
            return details;
        },
        onSuccess(data) {
            queryClient.setQueriesData(
                ["developerProfiles", "index"],
                function (existing: DeveloperProfilesApi.IndexResponse) {
                    return {
                        ...existing,
                        profiles: existing.profiles.map((profile) => {
                            if (profile.developerProfileID === data.developerProfileID) {
                                return data;
                            } else {
                                return profile;
                            }
                        }),
                    };
                },
            );
            options?.onSuccess?.();
        },
        onError: errorHandler,
    });
}
