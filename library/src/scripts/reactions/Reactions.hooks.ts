/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import { IUserFragment } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { useCurrentUser } from "@library/features/users/userHooks";
import { IReactionsProps, IRecordReaction } from "@library/reactions/Reactions.types";
import { useMutation, useQuery } from "@tanstack/react-query";

export type ReactionRecord = Pick<IReactionsProps, "recordType" | "recordID">;

// Toggle reaction selection for the record
export function useToggleReaction(props: ReactionRecord) {
    const { recordType, recordID } = props;
    const currentUser = useCurrentUser();
    const apiUrl = `/${recordType}s/${recordID}/reactions`;

    const { data, mutateAsync } = useMutation({
        mutationKey: ["toggle-reaction", recordType, recordID],
        mutationFn: async (reaction?: IReaction) => {
            // indicate if this is a new reaction
            let newReaction = !reaction?.hasReacted;

            // if this is a comment, we need to look up if the user has already used the reaction
            if (recordType === "comment") {
                const reactionResponse = await apiv2.get<IRecordReaction[]>(apiUrl, {
                    params: { type: reaction?.urlcode },
                });
                newReaction = !reactionResponse.data.find(({ userID }) => currentUser?.userID === userID);
            }

            // remove any existing reaction
            await apiv2.delete(`${apiUrl}/${currentUser?.userID}`);
            // if a new reaction is selected, then post it
            if (reaction && newReaction) {
                const response = await apiv2.post<IReaction[]>(apiUrl, { reactionType: reaction.urlcode });
                return response.data.map(({ tagID, count }) => {
                    return {
                        tagID,
                        count,
                        hasReacted: recordType === "discussion" && tagID === reaction.tagID,
                    };
                });
            }
            // we only deleted, get updated numbers and return them
            const response = await apiv2.get<IRecordReaction[]>(apiUrl);
            const counts: Record<string, number> = {};
            response.data.forEach(({ reactionType }) => {
                if (reactionType && reactionType.tagID) {
                    if (!counts[reactionType.tagID]) {
                        counts[reactionType.tagID] = 0;
                    }
                    counts[reactionType.tagID] += 1;
                }
            });
            return Object.entries(counts).map(([tagID, count]) => ({
                tagID: parseInt(tagID),
                count,
                hasReacted: false,
            }));
        },
    });

    return { toggleResponse: data, toggleReaction: mutateAsync };
}

// Get a list of users for the reaction on the current record
export function useReactionUsers(props: ReactionRecord & { urlCode: string }): IUserFragment[] {
    const { recordType, recordID, urlCode } = props;

    const { data } = useQuery({
        queryKey: ["reaction-users", recordType, recordID, urlCode],
        queryFn: async ({ queryKey }) => {
            const [_, recordType, recordID, type] = queryKey;
            const response = await apiv2.get<IRecordReaction[]>(`/${recordType}s/${recordID}/reactions`, {
                params: { type },
            });
            return response.data.map(({ user }) => user);
        },
    });

    return data ?? [];
}
