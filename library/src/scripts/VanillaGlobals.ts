/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import { hasPermission } from "@library/features/users/Permission";
import getStore from "@library/redux/getStore";
import { getCurrentLocale, translate } from "@vanilla/i18n";

const VanillaGlobals = {
    apiv2,
    translate,
    getCurrentUser: () => {
        return getStore().getState().users.current.data;
    },
    getCurrentUserPermissions: () => {
        return getStore().getState().users.permissions.data;
    },
    currentUserHasPermission: hasPermission,
    getCurrentLocale,
};

window.__VANILLA_GLOBALS_DO_NOT_USE_DIRECTLY__ = VanillaGlobals;
