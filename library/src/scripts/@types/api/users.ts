/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILoadable } from "@library/@types/api/core";

export interface IUserFragment {
    userID: number;
    name: string;
    url?: string;
    photoUrl: string;
    dateLastActive: string | null;
    label?: string;
}

export interface IUserFragmentAndRoles extends IUserFragment, IUserRoles {}

export interface IMe extends IUserFragment {
    permissions: string[];
    countUnreadNotifications: number;
    countUnreadConversations: number;
    isAdmin: boolean;
}

export interface ICount {
    name: string;
    count: number;
}

export type IMeCounts = ICount[];

export interface IUserRoles {
    roles: [
        {
            roleID: number;
            name: string;
        },
    ];
}

export interface IUser extends IUserFragment, IUserRoles {
    email: string;
    emailConfirmed: boolean;
    showEmail: boolean;
    bypassSpam: boolean;
    banned: number;
    dateInserted: string;
    dateUpdated?: string;
    hidden: boolean;
    title?: string;
    rankID?: number;
    rank?: {
        rankID: number;
        name: string;
        userTitle: string;
    };
    countDiscussions?: number;
    countComments?: number;
}

export interface IInvitees {
    dateInserted?: string;
    email?: string;
    insertUserID?: number;
    userID?: number;
}
