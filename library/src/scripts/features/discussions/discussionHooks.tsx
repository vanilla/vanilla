/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    IDeleteDiscussionReaction,
    IGetDiscussionByID,
    IPostDiscussionReaction,
    IPutDiscussionBookmarked,
    useDiscussionActions,
} from "@library/features/discussions/DiscussionActions";
import { IDiscussionsStoreState } from "@library/features/discussions/discussionModel";
import { useSelector } from "react-redux";
import { LoadStatus } from "@library/@types/api/core";
import { useEffect } from "react";

export function useDiscussion(query: IGetDiscussionByID) {
    const actions = useDiscussionActions();
    const { discussionID } = query;

    const existingResult = useSelector((state: IDiscussionsStoreState) => {
        return (
            state.discussions.discussionsByID[discussionID] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    const { status } = existingResult;

    useEffect(() => {
        if (LoadStatus.PENDING.includes(status)) {
            actions.getDiscussionByID(query);
        }
    }, [status, actions, query]);

    return existingResult;
}

export function useToggleDiscussionBookmarked(discussionID: IPutDiscussionBookmarked["discussionID"]) {
    const { putDiscussionBookmarked } = useDiscussionActions();

    async function toggleDiscussionBookmarked(bookmarked: IPutDiscussionBookmarked["bookmarked"]) {
        return await putDiscussionBookmarked({
            discussionID,
            bookmarked,
        });
    }

    return toggleDiscussionBookmarked;
}

export function useReactToDiscussion(discussionID: IPostDiscussionReaction["discussionID"]) {
    const { postDiscussionReaction } = useDiscussionActions();

    async function reactToDiscussion(reactionType: IPostDiscussionReaction["reactionType"]) {
        return await postDiscussionReaction({
            discussionID,
            reactionType,
        });
    }

    return reactToDiscussion;
}

export function useRemoveDiscussionReaction(discussionID: IDeleteDiscussionReaction["discussionID"]) {
    const { deleteDiscussionReaction } = useDiscussionActions();

    async function removeDiscussionReaction() {
        return await deleteDiscussionReaction({
            discussionID,
        });
    }

    return removeDiscussionReaction;
}
