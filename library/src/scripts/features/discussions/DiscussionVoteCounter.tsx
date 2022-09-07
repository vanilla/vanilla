/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import {
    useCurrentDiscussionReaction,
    useReactToDiscussion,
    useRemoveDiscussionReaction,
} from "@library/features/discussions/discussionHooks";
import VoteCounter, { IProps as VoteCounterProps } from "@library/voteCounter/VoteCounter";
import { ReactionUrlCode } from "@dashboard/@types/api/reaction";
import { useToast } from "@library/features/toaster/ToastContext";

interface IProps {
    discussion: IDiscussion;
    className?: string;
}

const DiscussionVoteCounter: FunctionComponent<IProps> = ({
    discussion: { discussionID, reactions, score },
    className,
}) => {
    const reactToDiscussion = useReactToDiscussion(discussionID);
    const removeDiscussionReaction = useRemoveDiscussionReaction(discussionID);
    const toast = useToast();

    const currentReaction = useCurrentDiscussionReaction(discussionID);

    const hasUpvoted = currentReaction?.urlcode == ReactionUrlCode.UP;
    const hasDownvoted = currentReaction?.urlcode == ReactionUrlCode.DOWN;

    let handleToggleUpvoted: VoteCounterProps["onToggleUpvoted"];

    const upvoteReaction = reactions!.find((reaction) => reaction.urlcode === ReactionUrlCode.UP);
    if (upvoteReaction) {
        handleToggleUpvoted = async function () {
            const callback = hasUpvoted ? removeDiscussionReaction : async () => reactToDiscussion(upvoteReaction);
            try {
                hasUpvoted ? callback() : await callback();
            } catch (error) {
                toast.addToast({
                    autoDismiss: false,
                    dismissible: true,
                    body: <>{error.description}</>,
                });
            }
        };
    }

    let handleToggleDownvoted: VoteCounterProps["onToggleDownvoted"];
    const downvoteReaction = reactions!.find((reaction) => reaction.urlcode === ReactionUrlCode.DOWN);
    if (downvoteReaction) {
        handleToggleDownvoted = async function () {
            const callback = hasDownvoted ? removeDiscussionReaction : async () => reactToDiscussion(downvoteReaction);
            try {
                hasDownvoted ? callback() : await callback();
            } catch (error) {
                toast.addToast({
                    autoDismiss: false,
                    dismissible: true,
                    body: <>{error.description}</>,
                });
            }
        };
    }

    return (
        <VoteCounter
            className={className}
            onToggleUpvoted={handleToggleUpvoted}
            onToggleDownvoted={handleToggleDownvoted}
            score={score}
            upvoted={hasUpvoted}
            downvoted={hasDownvoted}
        />
    );
};

export default DiscussionVoteCounter;
