/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import getStore from "@library/redux/getStore";
import { isComponentThemingEnabled } from "@library/utility/componentRegistry";

export function getThemeVariables() {
    const state = getStore().getState();
    if (state !== null) {
        if (state.theme.forcedVariables) {
            return state.theme.forcedVariables;
        }
        if (!isComponentThemingEnabled()) {
            return {};
        }
        const assets = state.theme.assets.data || {};
        const variables = assets.variables ? assets.variables.data : {};
        return variables;
    } else {
        return {};
    }
}
