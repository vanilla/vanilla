/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import { hasPermission } from "@library/features/users/Permission";
import getStore from "@library/redux/getStore";
import { getCurrentLocale, translate } from "@vanilla/i18n";
import { getAnonymizeData, setAnonymizeData } from "@library/analytics/anonymizeData";

const VanillaGlobals: Record<string, any> = {
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
    getAnonymizeData: getAnonymizeData,
    setAnonymizeData: setAnonymizeData,
};

window.__VANILLA_GLOBALS_DO_NOT_USE_DIRECTLY__ = VanillaGlobals;
