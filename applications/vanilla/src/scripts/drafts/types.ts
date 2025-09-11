/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ICrumb } from "@library/navigation/Breadcrumbs";
import { PostField } from "@dashboard/postTypes/postType.types";
import { ITag } from "@library/features/tags/TagsReducer";
import { RecordID } from "@vanilla/utils";
import { ICreatePostForm } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.hooks";

// Find DraftsApi namespace in DraftsApi.tsx

export enum DraftRecordType {
    DISCUSSION = "discussion",
    COMMENT = "comment",
    ARTICLE = "article",
    EVENT = "event",
}
/**
 * The shape of the draft object returned from the drafts API.
 */
export interface IDraft {
    draftID: RecordID;
    draftStatus: DraftStatus;
    breadCrumbs: ICrumb[];
    recordType: DraftRecordType;
    insertUserID: RecordID;
    dateInserted: string;
    updateUserID: RecordID;
    dateUpdated: string;
    attributes: DraftAttributes;
    editUrl: string;
    dateScheduled?: string;
    permaLink?: string;
    parentRecordType?: string;
    parentRecordID?: number;
    failedReason?: string;
    excerpt?: string;
    name?: string;
    recordID?: RecordID;
}

export interface DraftAttributes {
    /** Text content of the draft in whatever format it was saved*/
    body: string | undefined;
    /** Content format */
    format: string;
    draftType: "discussion" | "comment" | "event";

    draftMeta?: Partial<PostDraftMeta> | Partial<CommentDraftMeta>;
    lastSaved: string;
    groupID?: RecordID;
}

export interface ILegacyDraft {
    draftID: RecordID;
    recordType: DraftRecordType;
    insertUserID: RecordID;
    dateInserted: string;
    updateUserID: RecordID;
    dateUpdated: string;
    attributes: LegacyDraftAttributes;
    parentRecordID?: number;
}

/**
 * Shape of the legacy draft attributes
 */
export type LegacyDraftAttributes = {
    announce?: string;
    body?: string;
    format?: string;
    name?: string;
    tags?: string;
    type?: "Discussion";
};

export interface PostDraftMeta
    extends Partial<
        Pick<
            ICreatePostForm,
            "name" | "tagIDs" | "newTagNames" | "pinLocation" | "pinned" | "categoryID" | "postTypeID"
        >
    > {
    /** @deprecated Use `tagIDs` and `newTagNames` instead **/
    tags?: Array<ITag["tagID"]>; // @deprecated
    postMeta: Record<PostField["postFieldID"], any>;
    /** If the posting user has permission, they may publish this post without notifications */
    publishedSilently?: boolean;
}

export interface CommentDraftMeta {
    commentParentID?: RecordID;
    commentPath?: string;
    format: string;
}

export interface CreatePostParams {
    type: "create";
    /** Kind of post type */
    postType: "discussion" | "question" | "idea" | "poll" | "event";
    /** Where the post comes from (or is going) */
    parentRecordType: "category" | "group";
    /** If known, the ID of the resource the Post goes to */
    parentRecordID: RecordID | null;
}

export interface EditExistingPostParams {
    type: "edit";
    /** The ID of the post. Available when editing. */
    recordID: RecordID;
    /** The ID if the draft. Available when editing drafts specifically.  */
    draftID?: RecordID;
    /** Where the post comes from (or is going) */
    parentRecordType: "category" | "group";
    /** If known, the ID of the resource the Post goes to */
    parentRecordID: RecordID | null;
}

export enum DraftStatus {
    DRAFT = "draft",
    SCHEDULED = "scheduled",
    ERROR = "error",
}

export type DraftsSortValue = "dateScheduled" | "-dateScheduled" | "";
export interface PostPageParams {
    discussionID?: RecordID;
    commentID?: RecordID;
    page: number;
    sort: string;
}

export interface DraftLocation extends Pick<CommentDraftMeta, "commentParentID" | "commentPath"> {}

export interface IDraftProps {
    draft: {
        draftID: IDraft["draftID"];
        body: IDraft["attributes"]["body"];
        dateUpdated: IDraft["dateUpdated"];
        format: IDraft["attributes"]["format"];
    };
    draftLastSaved?: Date | null;
}
