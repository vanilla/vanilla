/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface IMemberSearchTypes {
    username?: string;
    email?: string;
    roleIDs?: number[];
    profileFields?: {
        [key: string]: any;
    };
}
