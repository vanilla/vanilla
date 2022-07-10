import { ILoadable, LoadStatus } from "@library/@types/api/core";
import {
    ILoadableWithCount,
    IRoleRequest,
    IRoleRequestMeta,
    IRoleRequestStore,
} from "@dashboard/roleRequests/state/roleRequestTypes";
import { useSelector } from "react-redux";
import {
    IListRoleRequestMetasParams,
    IListRoleRequestsParams,
    useRoleRequestActions,
} from "@dashboard/roleRequests/state/roleRequestActions";
import { useEffect } from "react";
import { useLastValue } from "@vanilla/react-utils/src";

export function useRoleRequestsList(
    params: IListRoleRequestsParams,
    reloadOn: number = 0,
): ILoadableWithCount<IRoleRequest[]> {
    const roleRequests = useSelector((state: IRoleRequestStore) => state.roleRequests.roleRequests);
    const { listRoleRequests } = useRoleRequestActions();

    const lastParams = useLastValue(params);

    useEffect(() => {
        if (
            roleRequests.status === LoadStatus.PENDING ||
            lastParams?.roleID !== params.roleID ||
            lastParams?.status !== params.status ||
            lastParams?.offset !== params.offset
        ) {
            listRoleRequests(params);
        } else if (
            roleRequests.status === LoadStatus.SUCCESS &&
            roleRequests.data &&
            roleRequests.data.length <= reloadOn &&
            roleRequests.count &&
            params.limit &&
            roleRequests.count >= params.limit
        ) {
            listRoleRequests({ ...params, reloading: true });
        }
    }, [listRoleRequests, roleRequests, params.roleID, params.status, params.offset]);

    return roleRequests;
}

export function useRoleRequestsState() {
    return useSelector((state: IRoleRequestStore) => state.roleRequests);
}

export function useRoleRequestMetasList(
    params: IListRoleRequestMetasParams,
): ILoadable<Record<number, IRoleRequestMeta>> {
    const metas = useSelector((state: IRoleRequestStore) => state.roleRequests.roleRequestMetasByID);
    const { listRoleRequestMetas } = useRoleRequestActions();

    useEffect(() => {
        if (metas.status === LoadStatus.PENDING) {
            listRoleRequestMetas(params);
        }
    }, [listRoleRequestMetas, metas, params.type]);

    return metas;
}
