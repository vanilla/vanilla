/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { IRole } from "@dashboard/roles/roleTypes";
import { ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { RoleActions } from "@dashboard/roles/RoleActions";

export interface IRoleState {
    rolesByID: ILoadable<Record<number, IRole>>;
}

export interface IRoleStoreState {
    roles: IRoleState;
}

export const INITIAL_ROLE_STATE: IRoleState = {
    rolesByID: {
        status: LoadStatus.PENDING,
    },
};

export const roleReducer = produce(
    reducerWithInitialState<IRoleState>(INITIAL_ROLE_STATE)
        .case(RoleActions.getAllACs.started, (nextState, action) => {
            nextState.rolesByID = {
                status: LoadStatus.LOADING,
            };
            return nextState;
        })
        .case(RoleActions.getAllACs.done, (nextState, payload) => {
            const rolesByID: Record<number, IRole> = {};
            payload.result.forEach((role) => {
                rolesByID[role.roleID] = role;
            });
            nextState.rolesByID = {
                status: LoadStatus.SUCCESS,
                data: rolesByID,
            };
            return nextState;
        })
        .case(RoleActions.getAllACs.failed, (nextState, action) => {
            nextState.rolesByID.status = LoadStatus.ERROR;
            nextState.rolesByID.error = action.error;
            return nextState;
        }),
);
