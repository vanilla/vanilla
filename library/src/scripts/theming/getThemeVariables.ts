/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import getStore from "@library/redux/getStore";

export function getThemeVariables() {
    const state = getStore().getState();
    if (state !== null) {
        const assets = state.theme.assets.data || {};
        const variables = assets.variables ? assets.variables.data : {};
        return variables;
    } else {
        return {};
    }
}
