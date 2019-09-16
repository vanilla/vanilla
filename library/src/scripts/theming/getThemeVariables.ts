/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getDeferredStoreState } from "@library/redux/getStore";
import { ICoreStoreState } from "@library/redux/reducerRegistry";

export function getThemeVariables() {
    const state = getDeferredStoreState<ICoreStoreState, null>(null);
    if (state !== null) {
        const assets = state.theme.assets.data || {};
        const variables = assets.variables ? assets.variables.data : {};
        return variables;
    } else {
        return {};
    }
}
