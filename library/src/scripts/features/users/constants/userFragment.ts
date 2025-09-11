/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@library/@types/api/users";
import { t } from "@vanilla/i18n";
import { sprintf } from "sprintf-js";

const commonUserFragment = {
    userID: 0,
    photoUrl: "",
    dateLastActive: null,
};

export function deletedUserFragment(): IUserFragment {
    return {
        ...commonUserFragment,
        name: t("User Deleted"),
    };
}

export function unknownUserFragment(): IUserFragment {
    return {
        ...commonUserFragment,
        name: t("Unknown User"),
    };
}

export function removedUserFragment(): IUserFragment {
    return {
        ...commonUserFragment,
        name: sprintf(t("Removed %s"), t("User")),
    };
}
