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

export interface IUser extends IUserFragment {
    email: string;
    emailConfirmed: boolean;
    showEmail: boolean;
    bypassSpam: boolean;
    banned: number;
    dateInserted: string;
    dateUpdated: string | null;
    roles: [
        {
            roleID: number;
            name: string;
        }
    ];
    hidden: boolean;
    rankID?: number | null;
    rank?: {
        rankID: number;
        name: string;
        userTitle: string;
    };
}
