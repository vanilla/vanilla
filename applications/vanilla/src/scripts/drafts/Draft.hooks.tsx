/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { RecordID, logError } from "@vanilla/utils";
import { useEffect, useRef } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import { IApiError } from "@library/@types/api/core";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import get from "lodash-es/get";
import { useLocalStorage } from "@vanilla/react-utils";
import { useLocation } from "react-router-dom";

interface DraftPostPatchMutationArgs {
    draftID?: IDraft["draftID"];
    body: Omit<DraftsApi.PatchParams, "draftID">;
}

export function useDraftPostPatchMutation() {
    return useMutation<IDraft, IApiError, DraftPostPatchMutationArgs>({
        mutationFn: async (mutationArgs: DraftPostPatchMutationArgs) => {
            const { draftID, body } = mutationArgs;
            if (!draftID) {
                return await DraftsApi.post(body);
            } else {
                return await DraftsApi.patch({ draftID, ...body });
            }
        },
    });
}

export function useDraftDeleteMutation() {
    const queryClient = useQueryClient();
    return useMutation<any, IApiError, RecordID>({
        mutationFn: async (draftID: RecordID) => {
            return DraftsApi.delete({ draftID });
        },
        onSuccess: async () => {
            await queryClient.invalidateQueries(["draftList"]);
        },
        onError: (error) => {
            logError("Error deleting draft", error);
        },
    });
}

export function useLocalDraftStore() {
    // Serialized object where the key refers to the the draftID and value is IDraft
    const [localDraftObject, setLocalDraftObject] = useLocalStorage<Record<IDraft["draftID"], IDraft>>(
        `draftStore`,
        {},
    );

    /**
     * Provide an object of matching key value pairs to find drafts
     * Use dot notation to access nested values
     */
    const getDraftByMatchers = (matchers: Record<string, RecordID>): Array<[RecordID, IDraft]> => {
        function getNestedValue(draft: IDraft, key: string) {
            return get(draft, key);
        }
        const payload = Object.entries(localDraftObject).filter(([draftID, draft]) => {
            return Object.keys(matchers).every((key) => {
                return getNestedValue(draft, key) === matchers[key];
            });
        });

        return payload as Array<[RecordID, IDraft]>;
    };

    return {
        localDraftObject,
        setLocalDraftObject,
        getDraftByMatchers,
    };
}

export function useLocalDraft(draftID?: IDraft["draftID"] | null) {
    const { pathname } = useLocation();
    const draftKey = useRef(draftID ?? pathname);

    useEffect(() => {
        draftKey.current = draftID && `${draftID}`.length > 0 ? draftID : pathname;
    }, [draftID]);

    const { localDraftObject, setLocalDraftObject } = useLocalDraftStore();

    /**
     * This function will update the draft key and move the local draft object to the new key
     */
    const updateUnsavedDraftID = (draftID: IDraft["draftID"]) => {
        if (draftKey.current === draftID) return;
        const prevDraftKey = draftKey.current;
        if (!localDraftObject[draftID]) {
            setLocalDraftObject((prev) => {
                let modified = { ...prev };
                modified[draftID] = prev?.[prevDraftKey];
                return modified;
            });
        }
        draftKey.current = draftID;
        removeDraftAtID(prevDraftKey);
    };

    const setLocalDraft = (draft: IDraft) => {
        setLocalDraftObject((prev) => {
            return {
                ...prev,
                [draftKey.current]: draft,
            };
        });
    };

    const removeDraftAtID = (draftID: IDraft["draftID"]) => {
        setLocalDraftObject((prev) => {
            let modified = { ...prev };
            if (modified?.[draftID]) {
                delete modified[draftID];
            }
            return modified;
        });
    };

    return {
        localDraft: localDraftObject[draftKey.current],
        setLocalDraft,
        updateUnsavedDraftID,
        removeDraftAtID,
    };
}

export function useDraftListQuery(queryParams: DraftsApi.GetParams) {
    return useQuery<any, IApiError, IWithPaging<IDraft[]>>({
        queryFn: async () => {
            return DraftsApi.index(queryParams);
        },
        queryKey: ["draftList", { ...queryParams }],
    });
}

export function useScheduleDraftMutation() {
    const queryClient = useQueryClient();
    return useMutation<any, IApiError, { draftID: RecordID; dateScheduled: string; publishedSilently?: boolean }>({
        mutationFn: async (apiParams) => {
            return DraftsApi.schedule(apiParams);
        },
        onSuccess: async () => {
            await queryClient.invalidateQueries(["draftList"]);
        },
        onError: (error) => {
            logError("Error scheduling draft", error);
        },
    });
}

export function useCancelDraftScheduleMutation() {
    const queryClient = useQueryClient();
    return useMutation<any, IApiError, RecordID>({
        mutationFn: async (draftID: RecordID) => {
            return DraftsApi.cancelShedule({ draftID });
        },
        onSuccess: async () => {
            await queryClient.invalidateQueries(["draftList"]);
        },
        onError: (error) => {
            logError("Error cancelling draft schedule", error);
        },
    });
}
