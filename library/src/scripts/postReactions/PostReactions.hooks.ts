/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import { IUserFragment } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { useCurrentUser } from "@library/features/users/userHooks";
import { IPostReaction, IPostRecord } from "@library/postReactions/PostReactions.types";
import { useMutation, useQuery } from "@tanstack/react-query";
import { indexArrayByKey } from "@vanilla/utils";

// Toggle reaction selection for the record
export function useToggleReaction(props: IPostRecord) {
    const { recordType, recordID } = props;
    const currentUser = useCurrentUser();
    const apiUrl = `/${recordType}s/${recordID}/reactions`;

    const { data, mutateAsync } = useMutation({
        mutationKey: ["toggle-reaction", recordType, recordID],
        mutationFn: async (props: { reaction?: IReaction; user?: IUserFragment; deleteOnly?: boolean }) => {
            const { reaction, user, deleteOnly } = props;
            const reactionUser = user ?? currentUser;

            // remove any existing reaction
            await apiv2.delete(`${apiUrl}/${reactionUser?.userID}`);

            // if a new reaction is selected, or it's not a delete only, then post it
            if (reaction && !reaction.hasReacted && !deleteOnly) {
                const response = await apiv2.post<IReaction[]>(apiUrl, { reactionType: reaction.urlcode });
                return response.data.map(({ tagID, count }) => {
                    return {
                        tagID,
                        count,
                        hasReacted: tagID === reaction.tagID,
                    };
                });
            }
            // we only deleted, get updated numbers and return them
            const response = await apiv2.get<IPostReaction[]>(apiUrl);

            return Object.entries(indexArrayByKey(response.data, "tagID")).map(([id, records]) => {
                const tagID = parseInt(id);
                return {
                    tagID,
                    count: records.length,
                    hasReacted: Boolean(
                        response.data.find((item) => item.tagID === tagID && item.userID === currentUser?.userID),
                    ),
                };
            });
        },
    });

    return { toggleResponse: data, toggleReaction: mutateAsync };
}

// Get a list of users for the reaction on the current record
export function useReactionLog(props: IPostRecord) {
    const { recordType, recordID } = props;
    const { data, refetch } = useQuery({
        queryKey: ["reaction-log", recordType, recordID],
        queryFn: async ({ queryKey }) => {
            const [_, recordType, recordID] = queryKey;
            const response = await apiv2.get<IPostReaction[]>(`/${recordType}s/${recordID}/reactions`);
            return response.data;
        },
    });

    return { reactionLog: data ?? [], refetchLog: refetch };
}
