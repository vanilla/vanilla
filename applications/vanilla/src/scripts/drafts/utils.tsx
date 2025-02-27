/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITag } from "@library/features/tags/TagsReducer";
import { safelyParseJSON, safelySerializeJSON } from "@library/utility/appUtils";
import { MyValue } from "@library/vanilla-editor/typescript";
import { isMyValue } from "@library/vanilla-editor/utils/isMyValue";
import { CommentEditor } from "@vanilla/addon-vanilla/comments/CommentEditor";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import {
    CommentDraftMeta,
    CreatePostParams,
    DraftAttributes,
    EditExistingPostParams,
    IDraftProps,
    PostDraftMeta,
    PostPageParams,
} from "@vanilla/addon-vanilla/drafts/types";
import { DeserializedPostBody } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset";
import { ICreatePostForm } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.hooks";
import { logError, RecordID } from "@vanilla/utils";
import { ComponentProps } from "react";

export const EMPTY_DRAFT: MyValue = [{ type: "p", children: [{ text: "" }] }];

/**
 * Will match a path and extract the parameters from it.
 *
 * The following URL patterns:
 * - /post/:postType?/:parentRecordID?
 * - /post/:postType?/social-groups?groupid=:groupID
 * - /post/editdiscussion/:discussionID?/:draftID?
 */
export const getParamsFromPath = (path: string, search: string): CreatePostParams | EditExistingPostParams | null => {
    const urlParts = path.split("/").filter((part) => part.length > 0);
    const isPost = urlParts?.[0].includes("post");
    const isEdit = urlParts?.[1].includes("editdiscussion");

    let params: any = {};
    if (isPost) {
        if (isEdit) {
            params.type = "edit";
            params.recordID = urlParts?.[2] == "0" ? null : urlParts?.[2];
            params.draftID = urlParts?.[3];
        } else {
            params.type = "create";
            params.postType = urlParts?.[1];
            params.parentRecordType = "category";
            params.parentRecordID = urlParts?.[2] ?? null;

            if (urlParts?.[2]?.includes("social-groups")) {
                if (search && search.length > 0) {
                    params.parentRecordType = "group";
                    try {
                        const searchParams = new URLSearchParams(search);
                        params.parentRecordID = searchParams.get("groupid");
                    } catch (error) {
                        logError("Error parsing search params", error);
                    }
                }
            }
        }
    }

    if (isCreatePostParams(params) || isEditExistingPostParams(params)) {
        return params;
    }

    return null;
};

export function isCreatePostParams(
    params: CreatePostParams | EditExistingPostParams | PostPageParams,
): params is CreatePostParams {
    if (isPostPageParams(params)) {
        return false;
    }
    const isCorrectType = params.type === "create";
    const hasPostType = "postType" in params;
    const hasParentRecordType = "parentRecordType" in params;
    const hasParentRecordID = "parentRecordID" in params;
    return isCorrectType && hasPostType && hasParentRecordType && hasParentRecordID;
}

export function isEditExistingPostParams(
    params: CreatePostParams | EditExistingPostParams | PostPageParams,
): params is EditExistingPostParams {
    if (isPostPageParams(params)) {
        return false;
    }
    const isCorrectType = params?.type === "edit";
    const hasRecordID = "recordID" in params;
    return isCorrectType && hasRecordID;
}

export function isPostPageParams(
    params: CreatePostParams | EditExistingPostParams | PostPageParams,
): params is PostPageParams {
    const hasDiscussionID = "discussionID" in params;
    const hasCommentID = "commentID" in params;
    const hasType = "type" in params;
    return (hasDiscussionID || hasCommentID) && !hasType;
}

export function isPostDraftMeta(
    meta: Partial<PostDraftMeta> | Partial<CommentDraftMeta>,
): meta is Partial<PostDraftMeta> {
    const potentialFields = ["title", "postFields", "tags", "pinLocation"];
    return potentialFields.some((field) => field in meta);
}

export function isCommentDraftMeta(meta: Partial<PostDraftMeta> | Partial<CommentDraftMeta>): meta is CommentDraftMeta {
    const potentialFields = ["format"];
    return potentialFields.every((field) => field in meta);
}

export interface MakePostDraftParams {
    body: string | MyValue;
    format: string | null;
    name: string;
    postFields?: Record<string, any>;
    tags?: Array<ITag["tagID"]>;
    pinLocation?: ICreatePostForm["pinLocation"];
    categoryID?: ICreatePostForm["categoryID"];
    postTypeID?: ICreatePostForm["postTypeID"];
    /** Used if we are to create drafts for edited posts */
    discussionID?: RecordID;
}
export const makePostDraft = (params: Partial<MakePostDraftParams>): DraftsApi.PostParams => {
    const { body, format, name, postFields, tags, pinLocation, discussionID, categoryID, postTypeID } = params ?? {};
    // Always serialize the body if it's rich2
    const serializedBody = format?.toLowerCase() === "rich2" || isMyValue(body) ? safelySerializeJSON(body) : body;

    const draftMeta: Partial<PostDraftMeta> = {
        name,
        postFields,
        tags: tags ?? [],
        pinLocation: pinLocation ?? "none",
        categoryID,
        postTypeID,
    };

    const attributes: DraftAttributes = {
        body: serializedBody,
        format: format ?? "rich2",
        draftType: "discussion",
        draftMeta,
        lastSaved: new Date().toISOString(),
    };

    const payload: DraftsApi.PostParams = {
        recordType: "discussion",
        attributes,
        ...(discussionID ? { parentRecordID: discussionID, parentRecordType: "category" } : {}),
    };

    return payload;
};

/**
 * Converts a draft to a new post body values
 */
export const makePostFormValues = (draft: DraftsApi.PostParams): Partial<DeserializedPostBody> | undefined => {
    const { attributes } = draft;
    const { body, format, draftMeta } = attributes;

    if (draftMeta && isPostDraftMeta(draftMeta)) {
        let safeBody: MyValue | undefined = undefined;
        if (body && format === "rich2") {
            const parsed: MyValue = safelyParseJSON(body);
            safeBody = parsed ?? body;
        } else if (isMyValue(body)) {
            safeBody = body;
        }

        if (body && format === "rich2") {
            const parsed: MyValue = safelyParseJSON(body);
            safeBody = parsed ?? body;
        }

        return {
            name: draftMeta?.name,
            body: safeBody,
            format,
            postFields: draftMeta?.postFields,
            tags: draftMeta?.tags,
            pinLocation: draftMeta?.pinLocation,
            categoryID: draftMeta?.categoryID,
            postTypeID: draftMeta?.postTypeID,
        };
    } else {
        logError("Invalid draft meta", draftMeta);
    }
};

export interface MakeCommentDraftParams extends Omit<CommentDraftMeta, "body" | "commentParentID" | "commentPath"> {
    parentRecordID: RecordID;
    parentRecordType: string;
    body: MyValue | string;
    commentParentID?: RecordID;
    commentPath?: string;
}

export const makeCommentDraft = (params: MakeCommentDraftParams): DraftsApi.PostParams | null => {
    const { body, format, parentRecordID, parentRecordType, commentParentID, commentPath } = params ?? {};

    if (!body) {
        logError("Invalid draft, no body content", params);
        return null;
    }

    // Always serialize the body if it's rich2
    const serializedBody: string =
        format?.toLowerCase() === "rich2" || isMyValue(body) ? safelySerializeJSON(body) ?? "" : (body as string);

    const draftMeta: CommentDraftMeta = {
        ...(commentPath && { commentPath }),
        ...(commentParentID && { commentParentID }),
        format: format ?? "rich2",
    };

    const attributes: DraftAttributes = {
        body: serializedBody,
        format: format ?? "rich2",
        draftType: "comment",
        draftMeta,
        lastSaved: new Date().toISOString(),
    };

    const payload: DraftsApi.PostParams = {
        recordType: "comment",
        attributes,
        ...(parentRecordType && { parentRecordType }),
        ...(parentRecordID && { parentRecordID }),
    };

    return payload;
};

export interface CommentEditorDraftProps
    extends Pick<ComponentProps<typeof CommentEditor>, "draft" | "draftLastSaved"> {}
export const makeCommentDraftProps = (
    draftID: RecordID | null,
    draft: DraftsApi.PostParams,
): IDraftProps | undefined => {
    if (draftID && draft && isCommentDraftMeta(draft?.attributes?.draftMeta ?? {})) {
        return {
            draft: {
                draftID,
                body: draft.attributes.body,
                dateUpdated: draft.attributes.lastSaved,
                format: draft.attributes.format,
            },
            ...(draft.attributes?.lastSaved && { draftLastSaved: new Date(draft.attributes.lastSaved) }),
        };
    }
    return undefined;
};
