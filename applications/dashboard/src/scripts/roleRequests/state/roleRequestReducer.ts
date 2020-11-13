import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { INITIAL_ROLE_REQUEST_STATE, IRoleRequestState } from "@dashboard/roleRequests/state/roleRequestTypes";
import { RoleRequestActions } from "@dashboard/roleRequests/state/roleRequestActions";
import { LoadStatus } from "@library/@types/api/core";
import keyBy from "lodash/keyBy";
import { concatNewRows } from "@vanilla/utils/src/apiUtils";

export const RoleRequestReducer = produce(
    reducerWithInitialState<IRoleRequestState>(INITIAL_ROLE_REQUEST_STATE)
        // LIST
        .case(RoleRequestActions.listRoleRequestsAC.started, (state, action) => {
            if (action.reloading) {
                // If we are reloading data on top of the current data then don't clear it out.
                state.roleRequests.status = LoadStatus.LOADING;
            } else {
                state.roleRequests = {
                    status: LoadStatus.LOADING,
                };
            }
            return state;
        })
        .case(RoleRequestActions.listRoleRequestsAC.failed, (state, action) => {
            state.roleRequests = {
                status: LoadStatus.ERROR,
                error: action.error,
            };
            return state;
        })
        .case(RoleRequestActions.listRoleRequestsAC.done, (state, payload) => {
            if (state.roleRequests.data) {
                concatNewRows(state.roleRequests.data, payload.result, (o) => o.roleRequestID);
                state.roleRequests.status = LoadStatus.SUCCESS;
                state.roleRequests.count = payload.result.length;
            } else {
                state.roleRequests = {
                    status: LoadStatus.SUCCESS,
                    data: payload.result,
                    count: payload.result.length,
                };
            }
            return state;
        })
        // PATCH
        .case(RoleRequestActions.patchRoleRequestAC.started, (state, action) => {
            state.roleRequestPatchingByID[action.roleRequestID] = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(RoleRequestActions.patchRoleRequestAC.failed, (state, action) => {
            state.roleRequestPatchingByID[action.params.roleRequestID] = {
                status: LoadStatus.ERROR,
                error: action.error,
            };
            return state;
        })
        .case(RoleRequestActions.patchRoleRequestAC.done, (state, payload) => {
            if (state.roleRequests.data) {
                let i = state.roleRequests.data.findIndex(
                    (rr) => rr.roleRequestID === payload.result.roleRequestID && rr.status !== payload.result.status,
                );
                state.roleRequests.data.splice(i, 1);
            }
            delete state.roleRequestPatchingByID[payload.params.roleRequestID];

            return state;
        })
        // LIST_METAS
        .case(RoleRequestActions.listRoleRequestMetasAC.started, (state, action) => {
            state.roleRequestMetasByID = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(RoleRequestActions.listRoleRequestMetasAC.failed, (state, action) => {
            state.roleRequestMetasByID = {
                status: LoadStatus.ERROR,
                error: action.error,
            };
            return state;
        })
        .case(RoleRequestActions.listRoleRequestMetasAC.done, (state, payload) => {
            state.roleRequestMetasByID = {
                status: LoadStatus.SUCCESS,
                data: keyBy(payload.result, (o) => o.roleID),
            };
            return state;
        }),
);
