/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { bindThunkAction, useReduxActions } from "@vanilla/library/src/scripts/redux/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import { IApiError } from "@vanilla/library/src/scripts/@types/api/core";
import { IRole } from "@dashboard/roles/roleTypes";

const createAction = actionCreatorFactory("@@roles");

export class RoleActions extends ReduxActions {
    public static getAllACs = createAction.async<{}, IRole[], IApiError>("GET_ALL");

    public getAllRoles = () => {
        const thunk = bindThunkAction(RoleActions.getAllACs, async () => {
            const response = await this.api.get(`/roles`, { params: { limit: 500 } });
            return response.data;
        })({});

        return this.dispatch(thunk);
    };
}

export function useRoleActions() {
    return useReduxActions(RoleActions);
}
