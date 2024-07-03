/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface IUserFragment {
    userID: number;
    name: string;
    url?: string;
    photoUrl: string;
    dateLastActive: string | null;
    label?: string;
    title?: string;
    banned?: number;
    private?: boolean;
    email?: string;
}

export interface IUserFragmentAndRoles extends IUserFragment, IUserRoles {}

export interface IMe extends IUserFragment {
    countUnreadNotifications: number;
    countUnreadConversations: number;
    isAdmin: boolean;
    emailConfirmed: boolean;
}

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
}

export interface IInvitees {
    dateInserted?: string;
    email?: string;
    insertUserID?: number;
    userID?: number;
}
