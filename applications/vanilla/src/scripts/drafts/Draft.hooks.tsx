/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError } from "@library/@types/api/core";
import { useMutation, useQuery } from "@tanstack/react-query";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";
import { useLocalStorage } from "@vanilla/react-utils";
import { logDebug, logError, RecordID } from "@vanilla/utils";
import { useEffect, useRef } from "react";
import get from "lodash-es/get";

export function useDraftQuery(draftID: IDraft["draftID"] | undefined | null, initialData?: IDraft) {
    return useQuery({
        queryKey: ["draft", draftID],
        queryFn: async () => {
            if (!draftID) return null;
            return DraftsApi.getEdit({ draftID });
        },
        initialData,
    });
}

interface DraftPostPatchMutationArgs {
    draftID?: IDraft["draftID"];
    body: DraftsApi.PostParams;
}

export function useDraftPostPatchMutation() {
    return useMutation<IDraft, IApiError, DraftPostPatchMutationArgs>({
        mutationFn: async (mutationArgs: DraftPostPatchMutationArgs) => {
            const { draftID, body } = mutationArgs;
            if (!draftID) {
                return DraftsApi.post(body);
            } else {
                const response = await DraftsApi.patch({ draftID, ...body }).catch(async (error) => {
                    logDebug("Error patching draft, retrying", error);
                    return await DraftsApi.post(body);
                });
                return response;
            }
        },
    });
}

export function useDraftDeleteMutation() {
    return useMutation<any, IApiError, RecordID>({
        mutationFn: async (draftID: RecordID) => {
            return DraftsApi.delete({ draftID });
        },
        onError: (error) => {
            logError("Error deleting draft", error);
        },
    });
}

export function useLocalDraft(draftID?: IDraft["draftID"] | null) {
    const draftKey = useRef(draftID ?? window.location.pathname);

    useEffect(() => {
        draftKey.current = draftID && `${draftID}`.length > 0 ? draftID : window.location.pathname;
    }, [draftID]);

    // Serialized object where the key refers to the the draftID and value is IDraft
    const [localDraftObject, setLocalDraftObject] = useLocalStorage<Record<IDraft["draftID"], DraftsApi.PostParams>>(
        `draftStore`,
        {},
    );

    /**
     * This function will update the draft key and move the local draft object to the new key
     */
    const updateUnsavedDraftID = (draftID: IDraft["draftID"]) => {
        if (draftKey.current === draftID) return;
        if (!localDraftObject[draftID]) {
            setLocalDraftObject((prev) => {
                let modified = { ...prev };
                modified[draftID] = prev?.[window.location.pathname];
                delete modified[window.location.pathname];
                return modified;
            });
        }
        draftKey.current = draftID;
    };

    const setLocalDraft = (draft: DraftsApi.PostParams) => {
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

    /**
     * Provide an object of matching key value pairs to find drafts
     * Use dot notation to access nested values
     */
    const getDraftByMatchers = (matchers: Record<string, RecordID>): Array<[RecordID, DraftsApi.PostParams]> => {
        function getNestedValue(draft: DraftsApi.PostParams, key: string) {
            return get(draft, key);
        }
        const payload = Object.entries(localDraftObject).filter(([draftID, draft]) => {
            return Object.keys(matchers).every((key) => {
                return getNestedValue(draft, key) === matchers[key];
            });
        });

        return payload as Array<[RecordID, DraftsApi.PostParams]>;
    };

    return {
        localDraft: localDraftObject[draftKey.current],
        setLocalDraft,
        updateUnsavedDraftID,
        removeDraftAtID,
        getDraftByMatchers,
    };
}
