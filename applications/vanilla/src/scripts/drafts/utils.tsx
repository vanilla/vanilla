/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    CommentDraftMeta,
    CreatePostParams,
    DraftAttributes,
    DraftRecordType,
    EditExistingPostParams,
    IDraft,
    IDraftProps,
    ILegacyDraft,
    LegacyDraftAttributes,
    PostDraftMeta,
    PostPageParams,
} from "@vanilla/addon-vanilla/drafts/types";
import { RecordID, logError } from "@vanilla/utils";
import { getSiteSection, safelyParseJSON, safelySerializeJSON } from "@library/utility/appUtils";

import { CommentEditor } from "@vanilla/addon-vanilla/comments/CommentEditor";
import { ComponentProps } from "react";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import { ICreatePostForm } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.hooks";
import { MyValue } from "@library/vanilla-editor/typescript";
import { getJSLocaleKey } from "@vanilla/i18n";
import { isMyValue } from "@library/vanilla-editor/utils/isMyValue";

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
    const siteSection = getSiteSection();

    const pathWithoutSiteSection = siteSection?.basePath ? path.replace(siteSection.basePath, "") : path;
    const urlParts = pathWithoutSiteSection.split("/").filter((part) => part.length > 0);

    const isPost = urlParts?.[0]?.includes("post");
    const isEdit = urlParts?.[1]?.includes("editdiscussion");

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
            params.parentRecordID = urlParts?.[2] ?? -1;

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

//DELETE ME
const isLegacyPostDraftAttributes = (attributes: any): attributes is LegacyDraftAttributes => {
    return attributes && typeof attributes === "object" && "type" in attributes && attributes["type"] === "Discussion";
};

export const isLegacyDraft = (draft: IDraft | ILegacyDraft | DraftsApi.PostParams): draft is ILegacyDraft => {
    const { attributes } = draft;
    return (
        !!attributes && typeof attributes === "object" && "type" in attributes && attributes["type"] === "Discussion"
    );
};

export const isNotLegacyDraft = (draft: IDraft | ILegacyDraft | DraftsApi.PostParams): draft is IDraft => {
    return !isLegacyDraft(draft);
};

export function isPostDraftMeta(
    meta: Partial<PostDraftMeta> | Partial<CommentDraftMeta>,
): meta is Partial<PostDraftMeta> {
    const potentialFields = ["name", "postMeta", "tags", "tagIDs", "pinLocation", "publishedSilently"];
    return potentialFields.some((field) => field in meta);
}

export function isCommentDraftMeta(meta: Partial<PostDraftMeta> | Partial<CommentDraftMeta>): meta is CommentDraftMeta {
    const potentialFields = ["format"];
    return potentialFields.every((field) => field in meta);
}

export interface MakePostDraftParams
    extends Pick<
            ICreatePostForm,
            "body" | "format" | "name" | "tagIDs" | "newTagNames" | "pinLocation" | "pinned" | "categoryID"
        >,
        Partial<Pick<ICreatePostForm, "postMeta" | "postTypeID">>,
        Partial<Pick<IDraft, "dateScheduled" | "draftStatus">> {
    /** Used if we are to create drafts for edited posts */
    recordID?: RecordID;
    groupID?: RecordID;
    /** If the posting user has permission, they may publish this post without notifications */
    publishedSilently?: boolean;
}

export const makePostDraft = (params: Partial<MakePostDraftParams>): DraftsApi.PostParams => {
    const {
        body,
        format,
        name,
        postMeta,
        tagIDs,
        newTagNames,
        pinLocation,
        categoryID,
        postTypeID,
        groupID,
        publishedSilently,
    } = params ?? {};
    // Always serialize the body if it's rich2
    const serializedBody = format?.toLowerCase() === "rich2" || isMyValue(body) ? safelySerializeJSON(body) : body;

    const shouldIncludePinnedParams = !!pinLocation;
    const pinned = pinLocation !== "none";

    const draftMeta: Partial<PostDraftMeta> = {
        name,
        postMeta,
        tagIDs,
        newTagNames,
        categoryID,
        postTypeID,
        ...(shouldIncludePinnedParams && {
            pinLocation,
            pinned,
        }),
        ...(publishedSilently !== undefined && { publishedSilently }),
    };

    const attributes: DraftAttributes = {
        body: serializedBody,
        format: format ?? "rich2",
        draftType: "discussion",
        draftMeta,
        lastSaved: new Date().toISOString(),
        groupID: groupID,
        // Used for displaying new drafts in legacy drafts page
        ...(name && { name }),
    };

    const payload = {
        recordType: DraftRecordType.DISCUSSION,
        attributes,
        ...(categoryID ? { parentRecordID: categoryID, parentRecordType: "category" } : {}),
        ...(params.draftStatus && { draftStatus: params.draftStatus, dateScheduled: params.dateScheduled }),
        ...(params.recordID && { recordID: params.recordID }),
    };

    return payload;
};

export function groupDraftsByDateScheduled(
    drafts: IDraft[],
    localeKey?: string, // test purposes
    options?: any, // test purposes
): Record<string, IDraft[]> {
    const dateFormat = {
        year: "numeric",
        month: "numeric",
        day: "numeric",
    } as Intl.DateTimeFormatOptions;

    const today = new Date().toLocaleString(getJSLocaleKey(), dateFormat);

    const draftsByScheduledDate = drafts.reduce((groups, draft) => {
        if (!draft.dateScheduled) {
            return groups;
        }

        let dateScheduledLocalDate = new Date(draft.dateScheduled ?? "").toLocaleString(
            localeKey ?? getJSLocaleKey(),
            options ?? dateFormat,
        );

        if (dateScheduledLocalDate === today) {
            dateScheduledLocalDate = new Date(draft.dateScheduled ?? "").toLocaleString(
                localeKey ?? getJSLocaleKey(),
                options ?? { ...dateFormat, hour: "numeric", minute: "numeric" },
            );
        }

        if (!groups[dateScheduledLocalDate]) {
            groups[dateScheduledLocalDate] = [];
        }
        groups[dateScheduledLocalDate].push(draft);
        return groups;
    }, {});
    return Object.keys(draftsByScheduledDate).length ? draftsByScheduledDate : {};
}
/**
 * Converts a draft to a new post body values
 */
export const mapDraftToPostFormValues = (draft: DraftsApi.PostParams): Partial<ICreatePostForm> | undefined => {
    if (isLegacyDraft(draft)) {
        return convertLegacyPostDraft(draft);
    }

    if (isNotLegacyDraft(draft)) {
        const { attributes } = draft;
        const { body, format, draftMeta, groupID } = attributes ?? {};

        if (draftMeta && isPostDraftMeta(draftMeta)) {
            let safeBody: MyValue | undefined = EMPTY_DRAFT;
            if (body && format === "rich2") {
                const parsed: MyValue = safelyParseJSON(body);
                safeBody = parsed ?? body;
            } else if (isMyValue(body)) {
                safeBody = body;
            }

            return {
                name: draftMeta?.name,
                body: safeBody,
                format,
                postMeta: draftMeta?.postMeta,
                tagIDs:
                    !!draftMeta.tags || !!draftMeta.tagIDs
                        ? // some drafts may still have the `tags` property -- if present, combine them with tagIDs
                          Array.from(new Set((draftMeta.tags ?? []).concat(draftMeta?.tagIDs ?? [])))
                        : undefined,
                newTagNames: draftMeta?.newTagNames,
                pinLocation: draftMeta?.pinLocation ?? "none",
                pinned: draftMeta?.pinned ?? false,
                categoryID: draftMeta?.categoryID,
                postTypeID: draftMeta?.postTypeID,
                groupID: groupID,
            };
        } else {
            logError("Invalid draft meta", draft);
        }
    }
};

function convertLegacyPostDraft(draft: any): Partial<ICreatePostForm> | undefined {
    if (!isLegacyDraft(draft)) {
        logError("Draft attributes contain unknown structure", draft);
        return undefined;
    }

    if (isLegacyDraft(draft)) {
        const attributes = draft?.attributes;

        const { body, format, name, announce } = attributes;

        let safeBody: MyValue | undefined = EMPTY_DRAFT;
        if (body && format?.toLowerCase() === "rich2") {
            const parsed: MyValue = safelyParseJSON(body);
            safeBody = parsed ?? body;
        } else if (isMyValue(body)) {
            safeBody = body;
        }

        const pinLocation = announce === "0" ? "none" : announce === "1" ? "category" : "recent";

        return {
            name,
            body: safeBody,
            format: format?.toLowerCase(),
            pinLocation,
            pinned: false,
            categoryID: draft.parentRecordID,
            postTypeID: draft.recordType,
        };
    }
}

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

    const payload = {
        recordType: DraftRecordType.COMMENT,
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
    if (isLegacyPostDraftAttributes(draft.attributes)) {
        return undefined;
    }

    if (draftID && draft && isCommentDraftMeta(draft.attributes ?? {})) {
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
