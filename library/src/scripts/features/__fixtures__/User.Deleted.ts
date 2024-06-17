/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { t } from "@vanilla/i18n";

export function deletedUserFragment(): IUserFragment {
    return {
        userID: 0,
        name: t("User Deleted"),
        photoUrl: "",
        dateLastActive: null,
    };
}
