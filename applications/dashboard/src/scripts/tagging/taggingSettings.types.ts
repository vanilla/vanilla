/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { TagScopeService } from "@dashboard/tagging/TagScopeService";
import { ITag } from "@library/features/tags/TagsReducer";
import { IWithPaging } from "@library/navigation/SimplePagerModel";

export enum ScopeType {
    GLOBAL = "global",
    SCOPED = "scoped",
}

export interface IGetTagsRequestBody {
    query?: string;
    limit?: number;
    page?: number;
    sort?: "name" | "-name" | "countDiscussions" | "-countDiscussions" | "dateInserted" | "-dateInserted";
    scopeType?: ScopeType[];
    scope?: {
        [key in keyof typeof TagScopeService.scopes]: Array<number | string>;
    };
}

export interface ITagItem extends ITag {
    dateInserted?: string;
    scopeType?: ScopeType;
    scope?: {
        [key in keyof typeof TagScopeService.scopes]: Array<number | string>;
    } & {
        allowedCategoryIDs?: Array<number | string>;
    };
}

export interface IGetTagsResponseBody extends IWithPaging<ITagItem[]> {}

export interface IPostTagRequestBody {
    name: ITagItem["name"];
    urlcode: ITagItem["urlcode"];
    scope?: ITagItem["scope"];
    scopeType?: ScopeType;
}

export interface IPostTagResponseBody extends ITagItem {}

export type TagFormValues = IPostTagRequestBody & {};

export interface IPatchTagRequestBody extends IPostTagRequestBody {
    tagID: ITagItem["tagID"];
}

export interface IPatchTagResponseBody extends ITagItem {}

export interface IDeleteTagRequestBody {
    tagID: ITagItem["tagID"];
}
