/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface IRoleFragment {
    roleID: number;
    name: string;
}

export interface IRoleSuggestion extends IRoleFragment {
    domID: string;
}
