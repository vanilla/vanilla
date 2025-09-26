/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import type { IRoleFragment } from "@dashboard/roles/roleTypes";
import { IContributionItem } from "@library/contributionItems/ContributionItem";
import type { VanillaSanitizedHtml } from "@vanilla/dom-utils";

export interface IUserFragment {
    userID: number;
    name: string;
    url?: string;
    photoUrl: string;
    dateLastActive: string | null;
    label?: string;
    labelHtml?: string;
    title?: string;
    banned?: number;
    private?: boolean;
    email?: string;
    badges?: Array<
        IContributionItem & {
            badgeID: number;
        }
    >;
    signature?: {
        body?: VanillaSanitizedHtml;
    };
}

export interface IUserFragmentAndRoles extends IUserFragment, IUserRoles {}

export interface ICount {
    name: string;
    count: number;
}

export type IMeCounts = ICount[];

export interface IUserRoles {
    roles: Array<{
        roleID: number;
        name: string;
    }>;
}

export interface IUser extends IUserFragment, IUserRoles {
    email?: string;
    emailConfirmed: boolean;
    showEmail: boolean;
    bypassSpam: boolean;
    admin: number;
    isAdmin: boolean;
    isSysAdmin: boolean;
    isSuperAdmin: boolean;
    banned: number;
    dateInserted: string;
    dateUpdated?: string;
    hidden: boolean;
    title?: string;
    rankID?: number | null;
    rank?: {
        rankID: number;
        name: string;
        userTitle: string;
    };
    label?: string;
    countDiscussions: number;
    countComments: number;
    countPosts: number;
    private: boolean;
    profileFields?: {};
    hashMethod?: string;
    lastIPAddress?: string;
    insertIPAddress?: string;
    points?: number;
    suggestAnswers?: boolean;
    pendingEmail?: string;
}

export interface IMe extends IUser {
    countUnreadNotifications: number;
    countUnreadConversations: number;
    suggestAnswers?: boolean;
    roles: IRoleFragment[];
    roleIDs: number[];
}

export interface IInvitees {
    dateInserted?: string;
    email?: string;
    insertUserID?: number;
    userID?: number;
}
