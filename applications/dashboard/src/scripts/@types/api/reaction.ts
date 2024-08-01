/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export enum ReactionUrlCode {
    UP = "Up",
    DOWN = "Down",
}

export interface IReaction {
    urlcode: ReactionUrlCode | string;
    reactionValue: number;
    hasReacted?: boolean;
    class?: string;
    count?: number;
    tagID?: number;
    name?: string;
    url?: string;
    photoUrl?: string;
}
