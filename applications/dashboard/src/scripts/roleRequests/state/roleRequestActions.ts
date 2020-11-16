import actionCreatorFactory from "typescript-fsa";
import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import {
    IRoleRequest,
    IRoleRequestMeta,
    RoleRequestStatus,
    RoleRequestType,
} from "@dashboard/roleRequests/state/roleRequestTypes";
import { IApiError } from "@library/@types/api/core";
import { useDispatch } from "react-redux";
import apiv2 from "@library/apiv2";
import { useMemo } from "react";

const actionCreator = actionCreatorFactory("@@roleRequests");

export interface IListRoleRequestsParams {
    type: RoleRequestType;
    status: RoleRequestStatus;
    roleID?: number;
    page?: number;
    limit?: number;
    offset?: number;
    reloading?: boolean;
    sort?: string;
}

export interface IListRoleRequestMetasParams {
    type: RoleRequestType;
}

export interface IPatchRoleRequestParams {
    roleRequestID: number;
    status: RoleRequestStatus;
}

export class RoleRequestActions extends ReduxActions {
    public static readonly listRoleRequestsAC = actionCreator.async<IListRoleRequestsParams, IRoleRequest[], IApiError>(
        "LIST",
    );
    public static patchRoleRequestAC = actionCreator.async<IPatchRoleRequestParams, IRoleRequest, IApiError>("PATCH");
    public static listRoleRequestMetasAC = actionCreator.async<{}, IRoleRequestMeta[], IApiError>("LIST_METAS");

    public listRoleRequests = (params: IListRoleRequestsParams) => {
        const thunk = bindThunkAction(RoleRequestActions.listRoleRequestsAC, async () => {
            const response = await this.api.get("/role-requests", { params: { ...params, expand: "all" } });
            return response.data;
        })(params);
        return this.dispatch(thunk);
    };

    public patchRoleRequest = (params: IPatchRoleRequestParams): Promise<IRoleRequest> => {
        const thunk = bindThunkAction(RoleRequestActions.patchRoleRequestAC, async () => {
            const response = await this.api.patch(`/role-requests/applications/${params.roleRequestID}`, params, {
                params: { expand: "all" },
            });
            return response.data;
        })(params);
        return this.dispatch(thunk);
    };

    public listRoleRequestMetas = (params: IListRoleRequestMetasParams) => {
        const thunk = bindThunkAction(RoleRequestActions.listRoleRequestMetasAC, async () => {
            const response = await this.api.get("/role-requests/metas", { params: { ...params, expand: "all" } });
            return response.data;
        })(params);
        return this.dispatch(thunk);
    };
}

export function useRoleRequestActions() {
    const dispatch = useDispatch();
    return useMemo(() => new RoleRequestActions(dispatch, apiv2), [dispatch]);
}
