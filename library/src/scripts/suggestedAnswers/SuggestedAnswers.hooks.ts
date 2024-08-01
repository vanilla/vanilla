/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { RecordID } from "@vanilla/utils";

// Toggle the visibility of the suggestions
export function useToggleSuggestionsVisibility(discussionID: RecordID) {
    const { mutateAsync } = useMutation({
        mutationKey: ["toggleSuggestionVisibility", discussionID],
        mutationFn: async (visible: boolean) => {
            const body = { discussionID, visible };
            await apiv2.post("/ai-suggestions/suggestions-visibility", body);
        },
    });
    return mutateAsync;
}

// Accept a suggestion or all suggestions
export function useAcceptSuggestion(discussionID: RecordID) {
    const queryClient = useQueryClient();

    const { mutateAsync } = useMutation({
        mutationKey: ["acceptSuggestion", discussionID],
        mutationFn: async (params: { suggestion: number | "all"; accept: boolean; commentID?: RecordID }) => {
            if (params.suggestion === -1 && !params.accept && params.commentID) {
                await apiv2.delete(`/comments/${params.commentID}`);
            } else {
                const body: any = { discussionID };
                if (params.suggestion === "all") {
                    body.allSuggestions = true;
                    body.suggestionIDs = [];
                } else {
                    body.suggestionIDs = [params.suggestion];
                    body.allSuggestions = false;
                }
                const apiUrl = params.accept ? "accept-suggestion" : "remove-accept-suggestion";
                await apiv2.post(`/ai-suggestions/${apiUrl}`, body);
            }
        },
        onSuccess: async () => {
            queryClient.invalidateQueries({
                queryKey: ["commentList", { discussionID }],
            });
        },
    });
    return mutateAsync;
}

// Dismiss a suggestion or all suggestions
export function useDismissSuggestion(discussionID: RecordID) {
    const { mutateAsync } = useMutation({
        mutationKey: ["dismissSuggestion", discussionID],
        mutationFn: async (suggestions: RecordID | RecordID[]) => {
            const body = {
                discussionID,
                suggestionIDs: Array.isArray(suggestions) ? suggestions : [suggestions],
            };
            await apiv2.post("/ai-suggestions/dismiss", body);
        },
    });
    return mutateAsync;
}

// Restore suggestions
export function useRestoreSuggestions(discussionID: RecordID) {
    const { mutateAsync } = useMutation({
        mutationKey: ["restoreSuggestions", discussionID],
        mutationFn: async () => {
            await apiv2.post("/ai-suggestions/restore", { discussionID });
        },
    });
    return mutateAsync;
}

// Generate new suggestions
export function useGenerateSuggestions(discussionID: RecordID) {
    const { mutateAsync } = useMutation({
        mutationKey: ["generateSuggestions", discussionID],
        mutationFn: async () => {
            await apiv2.put("/ai-suggestions/generate", { discussionID });
        },
    });
    return mutateAsync;
}
