/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IPermissions } from "@library/features/users/userTypes";

export interface IRole {
    roleID: number;
    name: string;
    description: string;
    type: string;
    deletable: boolean;
    canSession: boolean;
    personalInfo: boolean;
    permissions?: IPermissions["permissions"];
}

export interface IRoleFragment {
    roleID: number;
    name: string;
}
