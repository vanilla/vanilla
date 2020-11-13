/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPLv2-only
 */

import { Format, ILoadable, LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { IUserFragment } from "@library/@types/api/users";
import { IRoleFragment } from "@dashboard/roles/roleTypes";

/**
 * A role request role.
 */
export interface IRoleRequest {
    roleRequestID: number;
    type: RoleRequestType;
    roleID: number;
    role: IRoleFragment;
    userID: number;
    user: IUserFragment;
    status: RoleRequestStatus;
    dateOfStatus?: string;
    statusUserID?: number;
    dateExpires: string | null;
    attributes: BasicAttributes;
    dateInserted: string;
}

export type BasicType = string | number | boolean | null;
export type BasicAttributes = { string: BasicType };

/**
 * A role request meta row.
 */
export interface IRoleRequestMeta {
    roleID: number;
    role: IRoleFragment;
    type: RoleRequestType;
    name: string;
    body: string;
    format: Format;
    attributesSchema: AnyObject;
}

/**
 * The type of role request.
 */
export enum RoleRequestType {
    APPLICATION = "application",
    INVITATION = "invitation",
}

/**
 * The status of a role request.
 */
export enum RoleRequestStatus {
    PENDING = "pending",
    APPROVED = "approved",
    DENIED = "denied",
}

export interface IRoleRequestStore {
    roleRequests: IRoleRequestState;
}

export interface ILoadableWithCount<T> extends ILoadable<T> {
    count?: number;
}

export interface IRoleRequestState {
    roleRequests: ILoadableWithCount<IRoleRequest[]>;
    roleRequestMetasByID: ILoadable<{
        [id: number]: IRoleRequestMeta;
    }>;
    roleRequestPatchingByID: Record<number, ILoadable<null>>;
}

export const INITIAL_ROLE_REQUEST_STATE: IRoleRequestState = {
    roleRequests: {
        status: LoadStatus.PENDING,
    },
    roleRequestMetasByID: {
        status: LoadStatus.PENDING,
    },
    roleRequestPatchingByID: {},
};
