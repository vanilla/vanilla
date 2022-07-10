/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface IRole {
    roleID: number;
    name: string;
    description: string;
    type: string;
    deletable: boolean;
    canSession: boolean;
    personalInfo: boolean;
}

export interface IRoleFragment {
    roleID: number;
    name: string;
}
