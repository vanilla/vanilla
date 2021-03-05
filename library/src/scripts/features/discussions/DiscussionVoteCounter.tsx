/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { useReactToDiscussion, useRemoveDiscussionReaction } from "@library/features/discussions/discussionHooks";
import VoteCounter from "@library/voteCounter/VoteCounter";
import { Reaction } from "@dashboard/@types/api/reaction";

interface IProps {
    discussion: IDiscussion;
    className?: string;
}

const DiscussionVoteCounter: FunctionComponent<IProps> = ({ discussion, className }) => {
    const reactToDiscussion = useReactToDiscussion(discussion.discussionID);
    const removeDiscussionReaction = useRemoveDiscussionReaction(discussion.discussionID);

    const [score, setScore] = useState(discussion.score ?? 0);

    // FIXME: get current user's current reaction from hook / store state
    const [reaction, setReaction] = useState<Reaction | undefined>(undefined);
    const upvoted = reaction === Reaction.UP;

    async function handleToggleUpvoted() {
        if (upvoted) {
            await removeDiscussionReaction();
            setReaction(undefined); //fixme: read user's current reaction from store state
            setScore(score - 1); //fixme: read score from store state
        } else {
            await reactToDiscussion(Reaction.UP);
            setReaction(Reaction.UP); //fixme: read user's current reaction from store state
            setScore(score + 1); //fixme: read score from store state
        }
    }

    return <VoteCounter className={className} onToggleUpvoted={handleToggleUpvoted} score={score} upvoted={upvoted} />;
};

export default DiscussionVoteCounter;
