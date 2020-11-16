/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IGetDiscussionByID, useDiscussionActions } from "@library/features/discussions/DiscussionActions";
import { IDiscussionsStoreState } from "@library/features/discussions/discussionModel";
import { useSelector } from "react-redux";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
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
