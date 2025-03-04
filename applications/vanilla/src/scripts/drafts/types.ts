/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostField, PostType } from "@dashboard/postTypes/postType.types";
import { ITag } from "@library/features/tags/TagsReducer";
import { MyValue } from "@library/vanilla-editor/typescript";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { RecordID } from "@vanilla/utils";

// Find DraftsApi namespace in DraftsApi.tsx

/**
 * The shape of the draft object returned from the drafts API.
 */
export interface IDraft {
    draftID: RecordID;
    recordType: "discussion" | "comment";
    insertUserID: RecordID;
    dateInserted: string;
    updateUserID: RecordID;
    dateUpdated: string;
    attributes: DraftAttributes;
}

export interface DraftAttributes {
    /** Text content of the draft */
    body: string | undefined;
    /** Content format */
    format: string;
    draftType: "discussion" | "comment";
    /** The parent of this draft once posted */
    parentRecordType?: null | "discussion" | "comment" | "category" | "group";
    parentRecordID?: null | RecordID;
    draftMeta?: Partial<PostDraftMeta> | Partial<CommentDraftMeta>;
    lastSaved: string;
    groupID?: RecordID;
}

export interface PostDraftMeta {
    name: string;
    postFields: Record<PostField["postFieldID"], any>;
    tags: Array<ITag["tagID"]>;
    pinLocation: "none" | "category" | "recent";
    categoryID?: ICategory["categoryID"];
    postTypeID?: PostType["postTypeID"];
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
}

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
