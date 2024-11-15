/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { originalPostTypes } from "@dashboard/postTypes/utils";
import { IRole } from "@dashboard/roles/roleTypes";
import {
    CreatableFieldDataType,
    CreatableFieldFormType,
    CreatableFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { RecordID } from "@vanilla/utils";

export interface PostType {
    postTypeID: string;
    name: string;
    parentPostTypeID: string | null;
    isOriginal: boolean;
    isActive: boolean;
    isDeleted: boolean;
    dateInserted: string;
    dateUpdated: string;
    insertUserID: RecordID;
    updateUserID: RecordID;
    countCategories: number;
    postButtonLabel: string;
    postHelperText: string;
    roleIDs: Array<IRole["roleID"]>;
}

export interface PostTypeGetParams {
    postTypeID: string;
    parentPostTypeID: string;
    isOriginal: boolean;
    isActive: boolean;
    includeDeleted: boolean;
    page: number;
    limit: number;
    fields: string[];
}

export interface PostTypePostParams {
    postTypeID: string;
    name: string;
    parentPostTypeID: string | null;
    isActive: boolean;
    roleIDs: Array<IRole["roleID"]>;
    postButtonLabel: string;
    postHelperText: string;
    fields?: PostField[];
}

export type PostTypePatchParams = Partial<Omit<PostTypePostParams, "postTypeID" | "parentPostTypeID">>;

export type OriginalPostTypes = (typeof originalPostTypes)[number];

export interface PostField {
    postFieldID: string;
    postTypeID: PostType["postTypeID"];
    label: string;
    description: string;
    dataType: CreatableFieldDataType;
    formType: CreatableFieldFormType;
    visibility: CreatableFieldVisibility;
    dropdownOptions?: string[] | number[] | null;
    isRequired: boolean;
    isActive: boolean;
    sort: number;
    dateInserted: string;
    dateUpdated: string;
    insertUserID: RecordID;
    updateUserID: RecordID;
}

export interface PostFieldGetParams {
    postTypeID: PostField["postTypeID"];
    dataType: PostField["dataType"];
    formType: PostField["formType"];
    visibility: PostField["visibility"];
    isRequired: PostField["isRequired"];
    isActive: PostField["isActive"];
    page: number;
    limit: number;
    fields: any;
}

export interface PostFieldPostParams {
    postFieldID: string;
    postTypeID: PostType["postTypeID"];
    label: string;
    description: string;
    dataType: CreatableFieldDataType;
    formType: CreatableFieldFormType;
    visibility: CreatableFieldVisibility;
    dropdownOptions?: string[] | number[] | null;
    isRequired: boolean;
    isActive: boolean;
    sort: number;
}

export interface PostFieldPatchParams extends Partial<Omit<PostFieldPostParams, "postFieldID" | "postTypeID">> {}

export interface PostFieldPutParams {
    [postFieldID: PostField["postFieldID"]]: NonNullable<PostField["sort"]>;
}
