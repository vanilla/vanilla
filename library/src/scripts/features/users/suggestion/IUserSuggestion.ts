/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";

export interface IUserSuggestion extends IUserFragment {
    domID: string;
}
