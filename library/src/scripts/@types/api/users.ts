/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface IUserFragment {
    userID: number;
    name: string;
    photoUrl: string;
    dateLastActive: string | null;
}

export interface IUserFragmentAndRoles extends IUserFragment, IUserRoles {}

export interface IMe extends IUserFragment {
    permissions: string[];
    countUnreadNotifications: number;
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
        }
    ];
}

export interface IUser extends IUserFragment, IUserRoles {
    email: string;
    emailConfirmed: boolean;
    showEmail: boolean;
    bypassSpam: boolean;
    banned: number;
    dateInserted: string;
    dateUpdated: string | null;
    hidden: boolean;
    rankID?: number | null;
    rank?: {
        rankID: number;
        name: string;
        userTitle: string;
    };
}
