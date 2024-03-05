/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import { IUserFragment } from "@library/@types/api/users";
import { RecordID } from "@vanilla/utils";

export enum PostReactionIconType {
    PROMOTE = "reaction-fire",
    OFF_TOPIC = "reaction-off-topic",
    INSIGHTFUL = "reaction-insightful",
    DISAGREE = "reaction-dislike",
    AGREE = "reaction-like",
    DISLIKE = "reaction-thumbs-down",
    LIKE = "reaction-thumbs-up",
    UP = "reaction-arrow-up",
    DOWN = "reaction-arrow-down",
    SUPPORT = "reaction-support",
    AWESOME = "reaction-love",
    LOL = "reaction-funny",
    WTF = "reaction-wtf",
}

export interface IPostReaction {
    recordType: "Discussion" | "Comment";
    recordID: RecordID;
    tagID: number;
    userID: RecordID;
    dateInserted: string;
    user: IUserFragment;
    reactionType: Partial<IReaction>;
}

export interface IPostRecord {
    recordType?: "discussion" | "comment";
    recordID?: RecordID;
}
